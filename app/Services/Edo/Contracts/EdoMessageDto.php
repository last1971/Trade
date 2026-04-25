<?php

namespace App\Services\Edo\Contracts;

use Carbon\Carbon;

class EdoMessageDto
{
    public string $messageId;
    public string $status;
    public ?string $providerStatus;
    public array $errors;
    public ?Carbon $sentAt;

    public function __construct(
        string $messageId,
        string $status,
        ?string $providerStatus = null,
        array $errors = [],
        ?Carbon $sentAt = null
    ) {
        $this->messageId = $messageId;
        $this->status = $status;
        $this->providerStatus = $providerStatus;
        $this->errors = $errors;
        $this->sentAt = $sentAt;
    }
}
