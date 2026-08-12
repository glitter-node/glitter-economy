<?php

namespace Modules\Glitter\Economy\Enums;

/**
 * 이코노미 잠금 상태 Enum.
 */
enum EconomyHoldStatus: string
{
    case Active = 'active';
    case Released = 'released';
    case Captured = 'captured';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    /**
     * 모든 값을 문자열 배열로 반환합니다.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
