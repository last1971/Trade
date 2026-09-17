<?php

namespace App\Services\Marking;

use App\GoodClassif;
use Carbon\Carbon;

/**
 * Карточка товара в Нацкаталоге на строке GOODS_CLASSIF: чтение, создание, публикация.
 * Единственное место, которое пишет поля NK_* (патч 53). Сама карточка (имя, вид, бренд)
 * живёт в каталоге и читается оттуда через chz-сервис — копий в базе нет, здесь только
 * ключ (NK_GOOD_ID) и снимок состояния на NK_SYNC_AT.
 *
 * Наша / чужая — по строке: SUPPLIER_INN заполнен → GTIN поставщика, в каталог не ходим
 * (по нашему токену он отдаст 404, это норма). Пустой → наша.
 */
class NkCardService
{
    public const OWN = 'own';
    public const SUPPLIER = 'supplier';

    public const STATE_CREATING = 'creating';
    public const STATE_REJECTED = 'rejected';
    public const STATE_MODERATION = 'moderation';
    public const STATE_NOTSIGNED = 'notsigned';
    public const STATE_PUBLISHED = 'published';

    /** Вид товара, при котором каталог требует вид ещё и словами (vidOther). */
    public const VID_ABSENT = 'НЕТ В СПРАВОЧНИКЕ';

    /** Статусы каталога, которые кладём в снимок как есть. */
    private const CATALOG_STATES = ['draft', 'moderation', 'errors', 'notsigned', 'published', 'archived'];

    /** creating без номера пачки дольше этого — считаем, что до каталога не дошли, и спрашиваем сервис. */
    private const STALE_CREATING_MINUTES = 5;

    public function __construct(private ChzClient $client)
    {
    }

    public static function ownership(GoodClassif $row): string
    {
        return trim((string)$row->SUPPLIER_INN) !== '' ? self::SUPPLIER : self::OWN;
    }

    /**
     * Идентификатор запроса создания — детерминированный: повтор клика, двойной клик, повтор
     * после обрыва уходят с тем же id, и сервис второй карточки не заведёт. Новая попытка
     * после отказа — следующий NK_ATTEMPT.
     */
    public static function requestId(GoodClassif $row, int $attempt): string
    {
        return sprintf('nk-card-%s-%d-%d', config('marking.chz.prefix'), $row->ID, $attempt);
    }

    /**
     * Снимок состояния из карточки каталога (feed-product). Подробный статус старше общего:
     * «draft + moderation» — это «на модерации».
     *
     * @return array{state: string, text: ?string}
     */
    public static function stateFromCard(array $card): array
    {
        $detailed = array_values(array_filter((array)($card['good_detailed_status'] ?? []), 'is_string'));
        $status = (string)($card['good_status'] ?? '');
        $state = $status;
        foreach ($detailed as $d) {
            if ($d !== $status && in_array($d, self::CATALOG_STATES, true)) {
                $state = $d;
                break;
            }
        }
        if (!in_array($state, self::CATALOG_STATES, true)) {
            $state = in_array($status, self::CATALOG_STATES, true) ? $status : 'draft';
        }
        $text = null;
        if ($state === 'errors') {
            // Где каталог держит текст претензий, живьём не видели — берём всё похожее на текст.
            $raw = $card['moderation_errors'] ?? $card['errors'] ?? $card['moderation'] ?? null;
            $text = is_string($raw) ? $raw : ($raw ? json_encode($raw, JSON_UNESCAPED_UNICODE) : null);
        }
        return ['state' => $state, 'text' => $text ? mb_substr($text, 0, 250) : null];
    }

    /**
     * Разбор ответа feed-status одной пачки на одну карточку.
     *
     * @return array{done: bool, gtin: ?string, goodId: ?int, rejected: bool, text: ?string}
     */
    public static function parseFeed(array $feedStatus): array
    {
        $none = ['done' => false, 'gtin' => null, 'goodId' => null, 'rejected' => false, 'text' => null];
        if (($feedStatus['status'] ?? '') === 'Rejected') {
            $messages = [];
            foreach ((array)($feedStatus['result'] ?? []) as $rows) {
                foreach ((array)$rows as $m) {
                    $messages[] = is_string($m) ? $m : json_encode($m, JSON_UNESCAPED_UNICODE);
                }
            }
            return ['done' => true, 'gtin' => null, 'goodId' => null, 'rejected' => true,
                'text' => mb_substr(implode('; ', $messages) ?: 'каталог отбил пачку', 0, 250)];
        }
        $item = $feedStatus['item'][0] ?? $feedStatus['items'][0] ?? null;
        if ($item && !empty($item['gtin'])) {
            return ['done' => true, 'gtin' => (string)$item['gtin'], 'goodId' => intval($item['good_id'] ?? 0) ?: null,
                'rejected' => false, 'text' => null];
        }
        return $none;
    }

    /** GTIN в базе — 14 знаков: код каталога с ведущим нулём. */
    public static function gtin14(string $gtin): string
    {
        $gtin = trim($gtin);
        return strlen($gtin) === 13 ? '0' . $gtin : $gtin;
    }

    /**
     * Карточка для диалога: чья, что в каталоге, снимок. Для своей с GTIN — читает каталог
     * и освежает снимок; без GTIN — только дочитывает незавершённое создание.
     */
    public function card(GoodClassif $row): array
    {
        if (self::ownership($row) === self::SUPPLIER) {
            return ['kind' => self::SUPPLIER, 'card' => null, 'row' => $row];
        }
        if (!$row->GTIN) {
            return ['kind' => self::OWN, 'card' => null, 'row' => $this->state($row)];
        }
        try {
            $data = $this->client->get('nk/cards/' . trim($row->GTIN), ['inn' => $this->inn()]);
        } catch (ChzNotFoundException $e) {
            return ['kind' => self::OWN, 'card' => null, 'notFound' => true, 'row' => $row];
        }
        $card = $data['card'] ?? [];
        $snap = self::stateFromCard($card);
        $row->update([
            'NK_GOOD_ID' => intval($card['good_id'] ?? 0) ?: $row->NK_GOOD_ID,
            'NK_STATE' => $snap['state'],
            'NK_STATE_TEXT' => $snap['text'],
            'NK_SYNC_AT' => Carbon::now(),
        ]);
        return ['kind' => self::OWN, 'card' => $this->summary($card), 'row' => $row->fresh()];
    }

    /** Незавершённое создание: дочитать пачку либо спросить сервис, что стало с запросом. */
    public function state(GoodClassif $row): GoodClassif
    {
        if ($row->NK_STATE !== self::STATE_CREATING) {
            return $row;
        }
        if ($row->NK_FEED_ID) {
            $data = $this->client->getLoose('nk/cards/feed/' . $row->NK_FEED_ID, ['inn' => $this->inn()]);
            $this->applyFeed($row, self::parseFeed((array)($data['feedStatus'] ?? [])));
            return $row->fresh();
        }
        $since = $row->NK_SYNC_AT ? Carbon::parse($row->NK_SYNC_AT) : null;
        if ($since && $since->diffInMinutes(Carbon::now()) < self::STALE_CREATING_MINUTES) {
            return $row;
        }
        // Ответ сервиса потерян. Повторить с тем же id нельзя (тела уже нет), с новым — заведём
        // вторую карточку. Спрашиваем сервис, что стало с запросом.
        try {
            $job = $this->client->get('jobs/' . self::requestId($row, intval($row->NK_ATTEMPT)));
        } catch (ChzNotFoundException $e) {
            $this->reject($row, 'создание не дошло до каталога — повторите');
            return $row->fresh();
        }
        if (($job['status'] ?? '') === 'DONE' && is_array($job['response'] ?? null)) {
            $this->applyCreateResponse($row, $job['response']);
        }
        return $row->fresh();
    }

    /**
     * Завести карточку ИМ. Данные формы уже проверены контроллером; ТН ВЭД/ОКПД2 — со строки.
     *
     * @param array{name: string, catId: int, vid: string, vidOther?: ?string, brand?: ?string, moderation?: bool} $input
     */
    public function create(GoodClassif $row, array $input): GoodClassif
    {
        if (self::ownership($row) === self::SUPPLIER) {
            throw new MarkingException('Это GTIN поставщика — карточку в нашем каталоге по нему не заводят');
        }
        if ($row->NK_STATE === self::STATE_CREATING && $row->NK_FEED_ID) {
            throw new MarkingException('Карточка уже создаётся — дождитесь разбора');
        }
        if ($row->GTIN && $row->markCodesCount() > 0) {
            throw new MarkingException('К GTIN привязаны коды Честного знака — новую карточку не заводим');
        }
        $tnved = trim((string)$row->TNVED);
        $okpd2 = trim((string)$row->OKPD2);
        if (!preg_match('/^\d{10}$/', $tnved) || $okpd2 === '') {
            throw new MarkingException('Сначала классифицируйте товар: нужны ТН ВЭД (10 цифр) и ОКПД2');
        }
        if ($input['vid'] === self::VID_ABSENT && trim((string)($input['vidOther'] ?? '')) === '') {
            throw new MarkingException('Для вида «' . self::VID_ABSENT . '» укажите вид товара словами');
        }

        $attempt = intval($row->NK_ATTEMPT) + 1;
        $row->update([
            'NK_STATE' => self::STATE_CREATING,
            'NK_STATE_TEXT' => null,
            'NK_ATTEMPT' => $attempt,
            'NK_FEED_ID' => null,
            'NK_SYNC_AT' => Carbon::now(),
        ]);
        $item = [
            'goodscode' => (string)$row->GOODSCODE,
            'name' => trim($input['name']),
            'tnved' => $tnved,
            'okpd2' => $okpd2,
            'catId' => intval($input['catId']),
            'vid' => $input['vid'],
        ];
        if ($input['vid'] === self::VID_ABSENT) {
            $item['vidOther'] = trim((string)$input['vidOther']);
        }
        if (trim((string)($input['brand'] ?? '')) !== '') {
            $item['brand'] = trim($input['brand']);
        }
        $response = $this->client->postLoose('nk/cards', [
            'inn' => $this->inn(),
            'moderation' => $input['moderation'] ?? true,
            'items' => [$item],
        ], ['X-Request-Id' => self::requestId($row, $attempt)]);
        $this->applyCreateResponse($row, $response);
        return $row->fresh();
    }

    /** Публикация = подпись УКЭП в сервисе. Снимок ставим сами: каталог показывает новый статус с задержкой. */
    public function sign(GoodClassif $row, bool $publication): GoodClassif
    {
        if (!$row->NK_GOOD_ID) {
            throw new MarkingException('У строки нет good_id карточки — сначала откройте карточку');
        }
        $query = http_build_query(['inn' => $this->inn(), 'publication' => $publication ? 1 : 0]);
        $this->client->post('nk/cards/' . intval($row->NK_GOOD_ID) . '/sign?' . $query, []);
        $row->update(['NK_STATE' => self::STATE_PUBLISHED, 'NK_STATE_TEXT' => null, 'NK_SYNC_AT' => Carbon::now()]);
        return $row->fresh();
    }

    /** Справочники каталога для формы создания — прокси как есть. */
    public function categories(string $tnved): array
    {
        return $this->client->get('nk/categories', ['tnved' => $tnved, 'inn' => $this->inn()])['categories'] ?? [];
    }

    public function attributes(int $catId): array
    {
        return $this->client->get('nk/attributes', ['catId' => $catId, 'inn' => $this->inn()])['attributes'] ?? [];
    }

    public function brands(string $q, int $limit = 20): array
    {
        return $this->client->get('nk/brands', ['q' => $q, 'limit' => $limit, 'inn' => $this->inn()])['brands'] ?? [];
    }

    /** Ответ POST /nk/cards либо сохранённый ответ того же запроса из jobs. */
    private function applyCreateResponse(GoodClassif $row, array $response): void
    {
        if (!empty($response['inProgress'])) {
            return;
        }
        $card = $response['cards'][0] ?? null;
        if ($card && !empty($card['gtin'])) {
            $this->applyCreated($row, (string)$card['gtin'], intval($card['goodId'] ?? 0) ?: null);
            return;
        }
        if (!empty($response['feedId'])) {
            $row->update(['NK_FEED_ID' => (string)$response['feedId'], 'NK_SYNC_AT' => Carbon::now()]);
            return;
        }
        $reason = $response['failed'][0]['reason'] ?? 'сервис не вернул ни GTIN, ни номер пачки';
        $this->reject($row, $reason);
    }

    private function applyFeed(GoodClassif $row, array $feed): void
    {
        if (!$feed['done']) {
            $row->update(['NK_SYNC_AT' => Carbon::now()]);
            return;
        }
        if ($feed['rejected']) {
            $this->reject($row, $feed['text']);
            return;
        }
        $this->applyCreated($row, $feed['gtin'], $feed['goodId']);
    }

    private function applyCreated(GoodClassif $row, string $gtin, ?int $goodId): void
    {
        $row->update([
            'GTIN' => self::gtin14($gtin),
            'MARK_REQUIRED' => 1,
            'NK_GOOD_ID' => $goodId,
            'NK_STATE' => self::STATE_MODERATION,
            'NK_STATE_TEXT' => null,
            'NK_FEED_ID' => null,
            'NK_SYNC_AT' => Carbon::now(),
            'UPDATED_AT' => Carbon::now(),
        ]);
    }

    private function reject(GoodClassif $row, ?string $text): void
    {
        $row->update([
            'NK_STATE' => self::STATE_REJECTED,
            'NK_STATE_TEXT' => mb_substr((string)$text, 0, 250) ?: null,
            'NK_FEED_ID' => null,
            'NK_SYNC_AT' => Carbon::now(),
        ]);
    }

    /** Что показываем из карточки: без 60 атрибутов, только то, что человек читает. */
    private function summary(array $card): array
    {
        $attrs = [];
        foreach ((array)($card['good_attrs'] ?? []) as $a) {
            $attrs[(string)($a['attr_id'] ?? '')] = $a['attr_value'] ?? null;
        }
        return [
            'goodId' => $card['good_id'] ?? null,
            'name' => $card['good_name'] ?? null,
            'status' => $card['good_status'] ?? null,
            'detailedStatus' => $card['good_detailed_status'] ?? [],
            'signed' => (bool)($card['good_signed'] ?? false),
            'url' => $card['good_url'] ?? null,
            'producerInn' => $card['producer_inn'] ?? null,
            // Опт видит карточки опта, магазин — магазина; чужая своя же организация — только пометка.
            'ownOrg' => (string)($card['producer_inn'] ?? '') === $this->inn(),
            'category' => $card['categories'][0]['cat_name'] ?? null,
            'vid' => $attrs['12'] ?? null,
            'vidOther' => $attrs['23828'] ?? null,
            'brand' => $attrs['2504'] ?? null,
            'tnved' => $attrs['13933'] ?? null,
            'okpd2' => $attrs['3961'] ?? null,
        ];
    }

    private function inn(): string
    {
        $inn = trim((string)config('marking.chz.inn'));
        if ($inn === '') {
            throw new MarkingException('Не задан ИНН организации для каталога (MARKING_CHZ_INN)');
        }
        return $inn;
    }
}
