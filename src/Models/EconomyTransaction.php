<?php

namespace Modules\Glitter\Economy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Glitter\Economy\Enums\EconomyTransactionType;

/**
 * 이코노미 원장 거래 모델.
 */
class EconomyTransaction extends Model
{
    protected $table = 'economy_transactions';

    protected $fillable = [
        'uuid',
        'wallet_id',
        'related_wallet_id',
        'reversed_transaction_id',
        'transaction_type',
        'amount',
        'balance_after',
        'locked_balance_after',
        'available_balance_after',
        'source_module',
        'source_type',
        'source_id',
        'correlation_id',
        'idempotency_key',
        'description',
        'metadata',
        'created_by',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => EconomyTransactionType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'locked_balance_after' => 'decimal:2',
            'available_balance_after' => 'decimal:2',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
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
     * 상대 지갑 관계.
     */
    public function relatedWallet(): BelongsTo
    {
        return $this->belongsTo(EconomyWallet::class, 'related_wallet_id');
    }

    /**
     * 취소 또는 역분개 대상 거래 관계.
     */
    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_transaction_id');
    }

    /**
     * 생성 사용자 관계.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
