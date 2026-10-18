<?php

namespace Modules\Glitter\Economy\Enums;

/**
 * 이코노미 거래 유형 Enum.
 */
enum EconomyTransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Hold = 'hold';
    case HoldRelease = 'hold_release';
    case HoldVoid = 'hold_void';
    case HoldCapture = 'hold_capture';
    case Adjustment = 'adjustment';
    case Expiration = 'expiration';
    case Reversal = 'reversal';

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
