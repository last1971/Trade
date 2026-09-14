<?php

namespace App\Http\Controllers\Api;

use App\Interfaces\IMarkCodeDocument;
use App\Invoice;
use App\MarkCode;
use App\Services\Marking\ChzClient;
use App\Services\Marking\ChzOutboxService;
use App\Services\Marking\KmReader;
use App\Services\Marking\MarkingException;
use App\Services\MarkCodeService;
use App\Services\Marking\MarkCodeTransferService;
use App\TransferOut;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarkCodeController extends ModelController
{
    /**
     * Документы, по которым коды маркировки уезжают покупателю.
     * Вид передачи и причина вывода живут в самих моделях, не здесь.
     */
    private const DOCUMENTS = [
        'invoice' => Invoice::class,
        'transfer-out' => TransferOut::class,
    ];

    public function __construct()
    {
        parent::__construct(MarkCodeService::class);
    }

    public function markAsTransferred(Request $request, MarkCodeTransferService $service)
    {
        $count = $service->markAsTransferred($this->document($request));

        return ['count' => $count];
    }

    public function unmarkAsTransferred(Request $request, MarkCodeTransferService $service)
    {
        $count = $service->unmarkAsTransferred($this->document($request));

        return ['count' => $count];
    }

    /** Состояние пометки: какую кнопку показывать в карточке документа. */
    public function transferState(Request $request, MarkCodeTransferService $service)
    {
        return $service->state($this->document($request));
    }

    /**
     * Найти код по скану или вставленному КМ. Сканер отдаёт код целиком,
     * с криптохвостом и разделителями, а карточка живёт по MARKCODE — здесь
     * одно превращается в другое.
     *
     * Не нашли — это нормальный ответ, а не ошибка: кода может не быть в нашей
     * базе (чужой, ещё не принят приходом), и человеку надо сказать именно это.
     */
    public function find(Request $request)
    {
        $scan = (string)$request->input('scan', '');
        $ki = KmReader::ki($scan);
        if ($ki === '') {
            return [
                'found' => false,
                'message' => 'Не похоже на код маркировки. Отсканируйте код целиком '
                    . 'или вставьте его из буфера обмена.',
            ];
        }

        $code = MarkCode::where('KI', $ki)->first();
        if (!$code) {
            return [
                'found' => false,
                'ki' => $ki,
                'message' => 'Такого кода нет в базе: ' . $ki,
            ];
        }
        return ['found' => true, 'ki' => $ki, 'id' => intval($code->MARKCODE)];
    }

    /**
     * Что о коде знает ГИС МТ — для карточки кода. Наши данные и данные ЧЗ
     * расходятся штатно (у нас «выведен» значит «продан»), поэтому показываем
     * оба источника рядом, а не пытаемся их слить.
     */
    public function chzInfo(int $id, ChzClient $client)
    {
        $code = MarkCode::findOrFail($id);
        try {
            $answer = $client->post('codes/info', ['codes' => [trim((string)$code->KI)]]);
        } catch (MarkingException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
        $info = $answer['codes'][0] ?? null;
        $our = trim((string)config('marking.chz.inn'));
        $owner = $info['ownerInn'] ?? null;

        // Чужой владелец — главное, что надо увидеть в карточке: такой код мы
        // вывести не можем, ЧЗ ответит «не принадлежит участнику оборота».
        return [
            'ok' => true,
            'code' => $info,
            'alien' => (bool)($our !== '' && $owner && $owner !== $our),
        ];
    }

    /** Вернуть снятый с отправки код в очередь — прямо из его карточки. */
    public function unskip(int $id, ChzOutboxService $outbox)
    {
        $code = MarkCode::findOrFail($id);
        return ['returned' => $outbox->unskip([trim((string)$code->KI)])];
    }

    private function document(Request $request): IMarkCodeDocument
    {
        $data = $request->validate([
            'document' => ['required', Rule::in(array_keys(self::DOCUMENTS))],
            'document_id' => 'required|integer',
        ]);

        $model = self::DOCUMENTS[$data['document']];

        return $model::with('buyer')->findOrFail($data['document_id']);
    }
}
