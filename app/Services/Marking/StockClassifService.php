<?php

namespace App\Services\Marking;

use App\CertificateGood;
use App\Good;
use App\GoodClassif;
use App\GoodName;
use App\MarkCode;
use App\Services\MarketplaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * «Разгребание склада»: стоимость остатка по приходным ценам партий,
 * отсортированная по деньгам, с фильтрами-«проблемами» (маркировка, сертификаты).
 *
 * Полный расчёт стоимости по складу — ~2.5 мин (580k PR_META × 1.5M FIFO_T),
 * поэтому тяжёлая часть считается командой stock:classif и лежит в кэше;
 * классификация и сертификаты читаются живьём — вердикт сразу меняет список.
 * Firebird только читается, никаких новых таблиц/процедур.
 */
class StockClassifService
{
    public const CACHE_SNAP = 'stock-classif:snap';
    public const CACHE_UPDATED_AT = 'stock-classif:updated_at';
    public const CACHE_RUNNING = 'stock-classif:running';

    /**
     * Пересчитать снапшот стоимости остатка склада (s_s=0) в кэш.
     * Зовётся только из artisan stock:classif — долгий (~2.5 мин).
     */
    public function refresh(): int
    {
        // Остаток и стоимость по приходным ценам партий (ядро из спеки S11).
        // База в 1-м диалекте Firebird — CAST AS NUMERIC(15,2) недоступен, округляем здесь.
        $values = [];
        $rows = DB::connection('firebird')->select(
            'select pm.goodscode,
                    sum(pm.quan - coalesce(f.q, 0)) as ost,
                    sum((pm.quan - coalesce(f.q, 0)) * pm.price) as val
             from pr_meta pm
             left join (select pr_meta_in_id, sum(quan) as q from fifo_t group by pr_meta_in_id) f
                    on f.pr_meta_in_id = pm.id
             where pm.p_r = 0 and pm.s_s = 0
             group by pm.goodscode
             having sum(pm.quan - coalesce(f.q, 0)) > 0'
        );
        foreach ($rows as $row) {
            $values[intval($row->GOODSCODE)] = [floatval($row->OST), round(floatval($row->VAL), 2)];
        }

        // Сверка с живым остатком SKLAD: у старых товаров расходы не покрыты FIFO_T,
        // и «приход − FIFO» даёт остаток-призрак (пример: APM-4150 — 6 шт при пустом складе).
        // Живой остаток — истина; стоимость ужимаем пропорционально (цены партионные).
        $sklad = DB::connection('firebird')
            ->select('select goodscode, quan from sklad');
        $real = [];
        foreach ($sklad as $row) {
            $real[intval($row->GOODSCODE)] = floatval($row->QUAN);
        }
        foreach ($values as $code => [$ost, $val]) {
            $quan = $real[$code] ?? 0;
            if ($quan <= 0) {
                unset($values[$code]);
            } elseif ($quan < $ost) {
                $values[$code] = [$quan, round($val * $quan / $ost, 2)];
            }
        }

        // Непокрытые кодами штуки — готовая процедура (только товары из GOODS_CLASSIF).
        $uncovered = [];
        $rows = DB::connection('firebird')->select(
            'select goodscode, uncovered from MARK_UNCOVERED_STOCK(0)'
        );
        foreach ($rows as $row) {
            $uncovered[intval($row->GOODSCODE)] = intval($row->UNCOVERED);
        }

        // Штуки под живыми свободными кодами — для сверки с остатком.
        // Именно SUM(QUANTITY), не COUNT: количественный код кроет N штук.
        $codes = MarkCode::query()
            ->free()
            ->selectRaw('GOODSCODE, SUM(QUANTITY) AS CNT')
            ->groupBy('GOODSCODE')
            ->pluck('CNT', 'GOODSCODE')
            ->map(fn($cnt) => intval($cnt))
            ->all();

        Cache::put(self::CACHE_SNAP, [
            'values' => $values,
            'uncovered' => $uncovered,
            'codes' => $codes,
            'mp' => $this->marketplaceMap(),
        ]);
        Cache::put(self::CACHE_UPDATED_AT, now()->format('Y-m-d H:i'));

        return count($values);
    }

    /**
     * Карта «товар → маркетплейсы» из Nest-сервиса (sku-list).
     * GOODSCODE = числовой префикс SKU ("498824-1000" → 498824).
     * Если сервис недоступен — его данные берутся из прошлого снапшота.
     *
     * @return array [goodscode => ['ozon', 'wb']]
     */
    private function marketplaceMap(): array
    {
        $previous = Cache::get(self::CACHE_SNAP)['mp'] ?? [];
        $map = [];
        foreach (['ozon', 'wb'] as $service) {
            try {
                $skus = app(MarketplaceService::class)->skuList($service);
                foreach ($skus as $sku) {
                    $goodscode = intval($sku);
                    if ($goodscode > 0 && !in_array($service, $map[$goodscode] ?? [])) {
                        $map[$goodscode][] = $service;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("stock:classif: sku-list {$service} недоступен, беру прошлый снапшот: " . $e->getMessage());
                foreach ($previous as $goodscode => $services) {
                    if (in_array($service, $services) && !in_array($service, $map[$goodscode] ?? [])) {
                        $map[$goodscode][] = $service;
                    }
                }
            }
        }
        return $map;
    }

    /**
     * Страница списка: фильтр «проблем», сортировка по стоимости, пагинация
     * поверх снапшота; имена и классификация — только для строк страницы.
     */
    /**
     * Коды товаров «не проверяли» (нет ни одной строки GOODS_CLASSIF) из снапшота
     * склада, по убыванию стоимости остатка — та же выборка, что даёт список на
     * экране «Разгребание склада». Источник для команды авто-классификации.
     *
     * $exclude — коды, которые уже где-то в работе (например, лежат в подборе на
     * ревью): их пропускаем, чтобы добрать следующую пачку свежих товаров.
     *
     * @param array<int, int> $exclude
     * @return array<int, int>
     */
    public function uncheckedCodes(int $limit, array $exclude = []): array
    {
        $snap = Cache::get(self::CACHE_SNAP);
        if (!$snap) {
            return [];
        }
        $values = $snap['values'];
        $classif = $this->classifMap();
        $excluded = array_flip($exclude);
        uasort($values, fn($a, $b) => $b[1] <=> $a[1]);

        $codes = [];
        foreach (array_keys($values) as $code) {
            if (!isset($classif[$code]) && !isset($excluded[$code])) {
                $codes[] = $code;
                if (count($codes) >= $limit) {
                    break;
                }
            }
        }

        return $codes;
    }

    /**
     * Классификация живьём: GOODSCODE → MAX(MARK_REQUIRED). Общий источник для
     * list() (фильтр «подлежит»/«не проверяли») и uncheckedCodes() — без дублей.
     *
     * @return array<int, int>
     */
    private function classifMap(): array
    {
        return GoodClassif::query()
            ->selectRaw('GOODSCODE, MAX(MARK_REQUIRED) AS MR')
            ->groupBy('GOODSCODE')
            ->pluck('MR', 'GOODSCODE')
            ->map(fn($mr) => intval($mr))
            ->all();
    }

    public function list(Request $request): array
    {
        $snap = Cache::get(self::CACHE_SNAP);
        if (!$snap) {
            return ['data' => [], 'total' => 0, 'updated_at' => null];
        }
        $values = $snap['values'];
        $uncovered = $snap['uncovered'];
        $codes = $snap['codes'];
        $mp = $snap['mp'] ?? [];

        // Классификация живьём: MAX(MARK_REQUIRED) по товару (строк — сотни).
        $classif = $this->classifMap();

        // Товары с действующим сертификатом (MySQL, живьём).
        $certified = CertificateGood::query()
            ->whereHas('certificate', function ($query) {
                $query->where(function ($query) {
                    $query->whereNull('date_to')->orWhere('date_to', '>=', today());
                });
            })
            ->pluck('good_id')
            ->flip()
            ->all();

        // Именованные «проблемы»; следующая (например, по качеству фото) — ещё одна строка.
        // Для «подлежит» кроме снапшотного UNCOVERED смотрим «кодов < остатка»:
        // свежий вердикт «подлежит» ещё не пересчитан процедурой (UNCOVERED=0),
        // но кодов у товара нет — он должен остаться в списке проблемным.
        $problems = [
            'marking' => fn(int $code) => !isset($classif[$code])
                || ($classif[$code] === 1
                    && (($uncovered[$code] ?? 0) > 0 || ($codes[$code] ?? 0) < $values[$code][0])),
            'noCert' => fn(int $code) => !isset($certified[$code]),
        ];
        $enabled = array_intersect($request->input('problems', []), array_keys($problems));

        // Поиск по имени/коду — тот же механизм, что на странице Товары.
        $found = null;
        if (trim($request->input('search', '')) !== '') {
            $found = GoodName::query()
                ->where('NAME', 'CONTAINING', GoodName::normalize($request->input('search')))
                ->pluck('GOODSCODE')
                ->flip()
                ->all();
        }

        // Фильтр по маркетплейсу: ozon/wb — конкретный, any — на любом, none — нет ни на одном.
        $marketplace = $request->input('marketplace', '');

        uasort($values, fn($a, $b) => $b[1] <=> $a[1]);

        $list = [];
        foreach (array_keys($values) as $code) {
            if ($found !== null && !isset($found[$code])) {
                continue;
            }
            if ($marketplace === 'any' && empty($mp[$code])) {
                continue;
            }
            if ($marketplace === 'none' && !empty($mp[$code])) {
                continue;
            }
            if (in_array($marketplace, ['ozon', 'wb']) && !in_array($marketplace, $mp[$code] ?? [])) {
                continue;
            }
            $sick = [];
            foreach ($problems as $problem => $isSick) {
                if ($isSick($code)) {
                    $sick[] = $problem;
                }
            }
            if ($enabled && !array_intersect($enabled, $sick)) {
                continue;
            }
            $list[$code] = $sick;
        }

        $total = count($list);
        $perPage = max(1, intval($request->input('itemsPerPage', 25)));
        $page = max(1, intval($request->input('page', 1)));
        $list = array_slice($list, ($page - 1) * $perPage, $perPage, true);

        return [
            'data' => $this->hydrate($list, $values, $uncovered, $codes, $mp),
            'total' => $total,
            'updated_at' => Cache::get(self::CACHE_UPDATED_AT),
        ];
    }

    /**
     * Собрать строки страницы: имена и строки GOODS_CLASSIF только по кодам страницы.
     *
     * @param array $list [goodscode => список проблем товара]
     */
    private function hydrate(array $list, array $values, array $uncovered, array $codes, array $mp): array
    {
        if (!$list) {
            return [];
        }
        $goods = Good::query()
            ->with('name')
            ->whereIn('GOODSCODE', array_keys($list))
            ->get()
            ->keyBy('GOODSCODE');
        $classifs = GoodClassif::query()
            ->whereIn('GOODSCODE', array_keys($list))
            ->orderByDesc('IS_PRIMARY')
            ->orderBy('GTIN')
            ->get()
            ->groupBy('GOODSCODE');

        $rows = [];
        foreach ($list as $code => $sick) {
            $good = $goods->get($code);
            $rows[] = [
                'GOODSCODE' => $code,
                'NAME' => $good && $good->name ? $good->name->NAME : '',
                'OST' => $values[$code][0],
                'VAL' => $values[$code][1],
                'UNCOVERED' => $uncovered[$code] ?? 0,
                'CODES' => $codes[$code] ?? 0,
                'mp' => $mp[$code] ?? [],
                'problem_marking' => in_array('marking', $sick),
                'problem_no_cert' => in_array('noCert', $sick),
                'classifs' => $classifs->get($code, collect())->values(),
            ];
        }
        return $rows;
    }
}
