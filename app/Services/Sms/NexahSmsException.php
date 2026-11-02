<?php

namespace App\Services\Sms;

use RuntimeException;
use Throwable;

class NexahSmsException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int|string|null $errorCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, is_int($errorCode) ? $errorCode : 0, $previous);
    }

    /**
     * Permanent failures should not be retried (bad number, inactive account, invalid sender).
     */
    public function isPermanent(): bool
    {
        $code = is_numeric($this->errorCode) ? (int) $this->errorCode : null;

        return in_array($code, [
            -10019, // Inactive User
            -10003, // Invalid Mobile Number
            -10026, // Client SMS ID Max Limit Exceed
        ], true)
            || str_contains(mb_strtolower($this->getMessage()), 'invalid sender')
            || str_contains(mb_strtolower($this->getMessage()), 'senderid require')
            || str_contains(mb_strtolower($this->getMessage()), 'mobile number require');
    }
}
