<?php

namespace App\Services\WhatsApp;

use RuntimeException;
use Throwable;

class MetaWhatsAppException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $errorCode = 0,
        public readonly ?int $errorSubcode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $errorCode, $previous);
    }

    /**
     * Permanent failures should not be retried (invalid template, blocked user, etc.).
     */
    public function isPermanent(): bool
    {
        return in_array($this->errorCode, [
            131026, // Message undeliverable
            131047, // Re-engagement message / outside window without template issues
            131051, // Unsupported message type
            132000, // Template param count mismatch
            132001, // Template does not exist
            132005, // Template paused
            132007, // Template disabled
            132012, // Template param format mismatch
            132015, // Template is paused due to low quality
            132016, // Template is paused
            130472, // User number is part of experiment
        ], true);
    }
}
