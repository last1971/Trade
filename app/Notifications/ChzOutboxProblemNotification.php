<?php

namespace App\Notifications;

use App\ChzBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Очередь отправки в Честный знак требует человека: отказ ЧЗ, зависшая пачка,
 * расхождение количества. Успех не пишем — письма должны означать работу.
 */
class ChzOutboxProblemNotification extends Notification
{
    use Queueable;

    public function __construct(private ?ChzBatch $batch, private string $reason)
    {
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $subject = $this->batch
            ? "Честный знак: пачка №{$this->batch->ID} ({$this->kind()})"
            : 'Честный знак: очередь отправки';

        $mail = (new MailMessage())->subject($subject);
        if ($this->batch) {
            $mail->line("Пачка №{$this->batch->ID}, вид: {$this->kind()}, кодов: {$this->batch->CNT}.");
            if ($this->batch->REPORT_ID) {
                $mail->line("Отчёт в СУЗ: {$this->batch->REPORT_ID}");
            }
            if ($this->batch->DOC_UUID) {
                $mail->line("Документ ГИС МТ: {$this->batch->DOC_UUID}");
            }
            if ($this->batch->SCODE) {
                $mail->line("Счёт: {$this->batch->SCODE}");
            }
        }
        return $mail->line($this->reason)->line('Страница «Отправка в ЧЗ» — там же кнопка «Повторить».');
    }

    private function kind(): string
    {
        switch ($this->batch->KIND) {
            case ChzBatch::KIND_DIVISION:
                return 'деление';
            case ChzBatch::KIND_APPLY:
                return 'нанесение';
            case ChzBatch::KIND_INTRO:
                return 'ввод в оборот';
            case ChzBatch::KIND_RETIRE:
                return 'вывод из оборота';
            case ChzBatch::KIND_RETIRE_UPD:
                return 'вывод по УПД';
            case ChzBatch::KIND_RETIRE_ACT:
                return 'вывод по акту списания';
            default:
                return (string)$this->batch->KIND;
        }
    }

    public function toArray($notifiable)
    {
        return [];
    }
}
