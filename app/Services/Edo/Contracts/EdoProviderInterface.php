<?php

namespace App\Services\Edo\Contracts;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface EdoProviderInterface
{
    public function name(): string;

    public function sendUpd(string $xml): EdoMessageDto;

    public function getStatus(string $messageId): EdoMessageDto;

    public function getIncomingDocuments(Carbon $from, Carbon $to): Collection;

    public function downloadIncoming(string $messageId): string;
}
