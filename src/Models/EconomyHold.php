<?php

namespace Modules\Glitter\Economy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Glitter\Economy\Enums\EconomyHoldStatus;

/**
 * 이코노미 지갑 잠금 모델.
 */
class EconomyHold extends Model
{
    protected $table = 'economy_holds';

    protected $fillable = [
        'uuid',
        'wallet_id',
        'hold_status',
        'amount',
        'reason',
        'source_module',
        'source_type',
        'source_id',
        'correlation_id',
        'metadata',
        'created_by',
        'released_by',
        'expires_at',
        'released_at',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'hold_status' => EconomyHoldStatus::class,
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
            'captured_at' => 'datetime',
        ];
    }

    /**
     * 대상 지갑 관계.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(EconomyWallet::class, 'wallet_id');
    }

    /**
     * 생성 사용자 관계.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 해제 사용자 관계.
     */
    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
