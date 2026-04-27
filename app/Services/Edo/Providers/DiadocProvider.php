<?php

namespace App\Services\Edo\Providers;

use App\Services\Edo\Contracts\EdoException;
use App\Services\Edo\Contracts\EdoMessageDto;
use App\Services\Edo\Contracts\EdoProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DiadocProvider implements EdoProviderInterface
{
    public function name(): string
    {
        return 'diadoc';
    }

    public function sendUpd(string $xml): EdoMessageDto
    {
        throw new EdoException('Диадок ещё не подключён. Обратитесь к разработчику.');
    }

    public function getStatus(string $messageId): EdoMessageDto
    {
        throw new EdoException('Диадок ещё не подключён.');
    }

    public function getIncomingDocuments(Carbon $from, Carbon $to): Collection
    {
        return collect();
    }

    public function downloadIncoming(string $messageId): string
    {
        throw new EdoException('Диадок ещё не подключён.');
    }
}
