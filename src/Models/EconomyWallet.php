<?php

namespace Modules\Glitter\Economy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 이코노미 지갑 잔액 캐시 모델.
 */
class EconomyWallet extends Model
{
    use SoftDeletes;

    protected $table = 'economy_wallets';

    protected $fillable = [
        'uuid',
        'user_id',
        'wallet_type',
        'currency_code',
        'balance',
        'locked_balance',
        'available_balance',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'locked_balance' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'metadata' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * 지갑 소유 사용자 관계.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 지갑 원장 거래 관계.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(EconomyTransaction::class, 'wallet_id');
    }

    /**
     * 지갑 잠금 관계.
     */
    public function holds(): HasMany
    {
        return $this->hasMany(EconomyHold::class, 'wallet_id');
    }
}
