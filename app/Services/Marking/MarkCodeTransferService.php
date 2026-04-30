<?php

namespace App\Services\Marking;

use App\Invoice;
use App\MarkCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MarkCodeTransferService
{
    /**
     * Помечает все коды привязанные к строкам $invoice как переданные.
     *
     * @param Invoice $invoice
     * @param int $transferType 1=УПД юрлицу, 2=УПД-2 FBO, 3=API маркета FBS
     * @param int $retireReason 1=продажа, 2=списание, 3=передача, 4=брак, 5=собств.нужды
     * @return int количество обновлённых кодов
     * @throws MarkingException
     */
    public function markAsTransferred(Invoice $invoice, int $transferType, int $retireReason): int
    {
        // Без явной DB::transaction — Firebird-драйвер проекта работает с autocommit=0
        // и не любит вложенные транзакции. UPDATE с whereIn атомарен на уровне SQL.
        return (function () use ($invoice, $transferType, $retireReason) {
            $realPriceCodes = $invoice->invoiceLines->pluck('REALPRICECODE');

            $codes = MarkCode::whereIn('REALPRICECODE', $realPriceCodes)->get();

            if ($codes->isEmpty()) {
                throw new MarkingException(
                    "Нет привязанных кодов для счёта {$invoice->NS}"
                );
            }

            $alreadyTransferred = $codes->where('TRANSFER_TYPE', '!=', 0);
            if ($alreadyTransferred->isNotEmpty()) {
                throw new MarkingException(
                    'Коды уже переданы ранее: ' . $alreadyTransferred->pluck('KI')->implode(', ')
                );
            }

            $wrongStatus = $codes->where('STATUS', '!=', 5);
            if ($wrongStatus->isNotEmpty()) {
                throw new MarkingException(
                    'Коды не в обороте (STATUS != 5): ' . $wrongStatus->pluck('KI')->implode(', ')
                );
            }

            $count = MarkCode::whereIn('MARKCODE', $codes->pluck('MARKCODE'))
                ->update([
                    'TRANSFER_TYPE' => $transferType,
                    'STATUS' => 6,
                    'RETIRE_REASON' => $retireReason,
                    'RETIRED_AT' => Carbon::now(),
                ]);

            Log::info('MarkCodeTransfer: marked as transferred', [
                'invoice_id' => $invoice->SCODE,
                'invoice_ns' => $invoice->NS,
                'transfer_type' => $transferType,
                'retire_reason' => $retireReason,
                'count' => $count,
            ]);

            return $count;
        })();
    }

    /**
     * Откатывает передачу кодов привязанных к строкам $invoice.
     * Применяется когда отправка в ЭДО была отменена/отклонена.
     *
     * @return int количество обновлённых кодов
     * @throws MarkingException
     */
    public function unmarkAsTransferred(Invoice $invoice): int
    {
        return (function () use ($invoice) {
            $realPriceCodes = $invoice->invoiceLines->pluck('REALPRICECODE');

            $codes = MarkCode::whereIn('REALPRICECODE', $realPriceCodes)->get();

            if ($codes->isEmpty()) {
                throw new MarkingException(
                    "Нет кодов привязанных к счёту {$invoice->NS}"
                );
            }

            $notTransferred = $codes->where('TRANSFER_TYPE', 0);
            if ($notTransferred->isNotEmpty()) {
                throw new MarkingException(
                    'Часть кодов не была передана: ' . $notTransferred->pluck('KI')->implode(', ')
                );
            }

            $count = MarkCode::whereIn('MARKCODE', $codes->pluck('MARKCODE'))
                ->update([
                    'TRANSFER_TYPE' => 0,
                    'STATUS' => 5,
                    'RETIRE_REASON' => null,
                    'RETIRED_AT' => null,
                ]);

            Log::info('MarkCodeTransfer: unmarked as transferred', [
                'invoice_id' => $invoice->SCODE,
                'invoice_ns' => $invoice->NS,
                'count' => $count,
            ]);

            return $count;
        })();
    }
}
