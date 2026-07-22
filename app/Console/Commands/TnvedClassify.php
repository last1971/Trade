<?php

namespace App\Console\Commands;

use App\Good;
use App\Services\Marking\GoodClassifyService;
use App\Services\Marking\StockClassifService;
use App\Services\Tnved\TnvedMatchService;
use Illuminate\Console\Command;

/**
 * Авто-классификация товаров «не проверяли»: по данным товара подбираем код
 * ТН ВЭД, по справочнику маркировки решаем подлежит/не подлежит (+ ОКПД2) и
 * ставим вердикт — та же операция, что человек делает на карточке Нац Каталога.
 *
 * Своей бизнес-логики не держит: выборка — StockClassifService::uncheckedCodes(),
 * подбор — TnvedMatchService, запись — GoodClassifyService::setVerdict().
 */
class TnvedClassify extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tnved:classify
        {--limit=10 : сколько товаров «не проверяли» обработать}
        {--confidence=80 : порог уверенности; ниже — пропуск, остаётся «не проверяли»}
        {--apply : записать вердикты (без флага — только отчёт, dry-run)}';

    /**
     * @var string
     */
    protected $description = 'Авто-классификация «не проверяли»: подбор ТН ВЭД, вердикт маркировки и ОКПД2';

    public function handle(
        StockClassifService $stock,
        TnvedMatchService $matcher,
        GoodClassifyService $verdict
    ): int {
        $limit = max(1, (int) $this->option('limit'));
        $threshold = (int) $this->option('confidence');
        $apply = (bool) $this->option('apply');

        $codes = $stock->uncheckedCodes($limit);
        if (empty($codes)) {
            $this->warn('Нет товаров «не проверяли» (или снапшот пуст — запустите stock:classif).');
            return self::SUCCESS;
        }

        $marking = $this->markingDict();
        $rows = [];

        foreach ($codes as $code) {
            $good = Good::with(['name', 'category'])->find($code);
            $name = (string) optional($good?->name)->NAME;
            if ($name === '') {
                $rows[] = [$code, '—', '', '', '', '', 'нет названия'];
                continue;
            }

            $category = optional($good->category)->CATEGORY;
            $case = $good->BODY ?: null;
            $maker = $good->PRODUCER ?: null;

            $res = $matcher->match($name, $category, $case, $maker);
            if (!($res['found'] ?? false)) {
                $rows[] = [$code, $name, '', '', '', '', 'не подобрано'];
                continue;
            }

            $tnved = (string) $res['code'];
            $conf = (int) $res['confidence'];
            if ($conf < $threshold) {
                $rows[] = [$code, $name, $tnved, '', '', $conf . '%', 'пропуск (низкая)'];
                continue;
            }

            // Подлежит = код попал в справочник маркируемых (те же 180 из селекта UI).
            $entry = $marking[$tnved] ?? null;
            $markRequired = $entry ? 1 : 0;
            $okpd2 = $entry
                ? $matcher->chooseOkpd2($entry['o'], $name, $category, $case, $maker)
                : null;

            $prim = 'авто-подбор ИИ, уверенность ' . $conf . '%, модель ' . ($res['model'] ?? '');

            $action = 'dry-run';
            if ($apply) {
                try {
                    $verdict->setVerdict($code, $markRequired, $tnved, $okpd2, $prim);
                    $action = 'записано';
                } catch (\Throwable $e) {
                    $action = 'ошибка: ' . $e->getMessage();
                }
            }

            $rows[] = [
                $code,
                $name,
                $tnved,
                $markRequired ? 'да' : 'нет',
                $okpd2 ?? '',
                $conf . '%',
                $action,
            ];
        }

        $this->table(
            ['Код', 'Товар', 'ТНВЭД', 'Подлежит', 'ОКПД2', 'Увер.', 'Действие'],
            $rows
        );
        if (!$apply) {
            $this->info('Отчёт (dry-run). Для записи вердиктов добавьте --apply.');
        }

        return self::SUCCESS;
    }

    /**
     * Справочник маркировки code → запись — тот же JSON, что грузит фронт
     * (resources/js/data/tnvedMarking.json), второй копии не заводим.
     *
     * @return array<string, array{c: string, n: string, o: array<int, array{c: string, n: string}>}>
     */
    private function markingDict(): array
    {
        $path = resource_path('js/data/tnvedMarking.json');
        $data = json_decode((string) file_get_contents($path), true) ?: [];

        $out = [];
        foreach ($data as $entry) {
            $out[$entry['c']] = $entry;
        }

        return $out;
    }
}
