<?php

namespace App\Services\Marking;

use App\MarkCode;

/**
 * Код маркировки глазами Честного знака: справка и печать этикеток.
 * Чей код — решает chz-сервис (поле ours), здесь только спрашиваем и отдаём дальше.
 */
class MarkCodeChzService
{
    /** Предел chz-сервиса на одну печать. */
    public const LABELS_MAX = 500;

    public function __construct(private ChzClient $client)
    {
    }

    /**
     * Что ГИС МТ знает о коде. Код — КИ или полный КМ: в нашей базе его может не быть
     * (отсканировали чужой по происхождению товар), справка от этого не зависит.
     */
    public function info(string $code): array
    {
        try {
            $answer = $this->client->post('codes/info', ['inn' => $this->client->inn(), 'codes' => [trim($code)]]);
        } catch (MarkingException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
        $info = $answer['codes'][0] ?? null;

        // Чужой владелец — главное, что надо увидеть: такой код мы не выведем
        // и не напечатаем, ЧЗ ответит «не принадлежит участнику оборота».
        return [
            'ok' => true,
            'code' => $info,
            'alien' => !empty($info['ownerInn']) && empty($info['ours']),
        ];
    }

    /**
     * Этикетки: наши коды по MARKCODE и отсканированные КМ одним файлом, в порядке
     * «сначала наши». Полный КМ из базы, без него — КИ (chz найдёт свой заказ).
     * Ответ chz-сервиса как есть: PDF в base64 и отказы по кодам словами ЧЗ.
     */
    public function labels(array $ids, array $codes): array
    {
        $ours = MarkCode::whereIn('MARKCODE', $ids)
            ->get(['MARKCODE', 'KI', 'KM_FULL'])
            ->map(fn (MarkCode $code) => trim((string)($code->KM_FULL ?: $code->KI)))
            ->all();
        $all = array_values(array_filter(array_merge($ours, array_map('trim', $codes)), 'strlen'));
        if (!$all) {
            throw new MarkingException('Не выбрано ни одного кода');
        }
        if (count($all) > self::LABELS_MAX) {
            throw new MarkingException('За раз не больше ' . self::LABELS_MAX . ' этикеток');
        }
        return $this->client->postLoose('labels/pdf', ['inn' => $this->client->inn(), 'codes' => $all]);
    }
}
