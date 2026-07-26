<?php

namespace KiisKepri\Esign\V2;

use KiisKepri\Esign\BaseResponse;

class UserStatusResponse extends BaseResponse
{
    public const STATUS_ISSUE = 'ISSUE';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_RENEW = 'RENEW';
    public const STATUS_WAITING_FOR_VERIFICATION = 'WAITING_FOR_VERIFICATION';
    public const STATUS_NEW = 'NEW';
    public const STATUS_NO_CERTIFICATE = 'NO_CERTIFICATE';
    public const STATUS_NOT_REGISTERED = 'NOT_REGISTERED';
    public const STATUS_SUSPEND = 'SUSPEND';
    public const STATUS_REVOKE = 'REVOKE';

    public function getUserStatus(): ?string
    {
        if (!is_array($this->data)) {
            return null;
        }

        return $this->data['status']
            ?? $this->data['userStatus']
            ?? $this->data['data']['status']
            ?? null;
    }

    public function canSign(): bool
    {
        return strtoupper((string) $this->getUserStatus()) === self::STATUS_ISSUE;
    }
}
