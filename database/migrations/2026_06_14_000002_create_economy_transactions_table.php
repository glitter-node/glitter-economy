<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('economy_transactions', function (Blueprint $table) {
            $table->id()->comment('거래 ID');
            $table->uuid('uuid')->comment('외부 노출용 거래 UUID');
            $table->unsignedBigInteger('wallet_id')->comment('대상 지갑 ID');
            $table->unsignedBigInteger('related_wallet_id')->nullable()->comment('상대 지갑 ID');
            $table->unsignedBigInteger('reversed_transaction_id')->nullable()->comment('취소 또는 역분개 대상 거래 ID');
            $table->string('transaction_type', 40)->comment('거래 유형');
            $table->decimal('amount', 18, 2)->comment('거래 금액');
            $table->decimal('balance_after', 18, 2)->comment('거래 후 총 잔액');
            $table->decimal('locked_balance_after', 18, 2)->default(0)->comment('거래 후 잠금 잔액');
            $table->decimal('available_balance_after', 18, 2)->comment('거래 후 사용 가능 잔액');
            $table->string('source_module', 100)->nullable()->comment('거래 발생 모듈');
            $table->string('source_type', 100)->nullable()->comment('거래 발생 도메인 유형');
            $table->string('source_id', 100)->nullable()->comment('거래 발생 도메인 식별자');
            $table->string('correlation_id', 100)->nullable()->comment('연관 작업 식별자');
            $table->string('idempotency_key', 191)->nullable()->comment('중복 처리 방지 키');
            $table->text('description')->nullable()->comment('거래 설명');
            $table->mediumText('metadata')->nullable()->comment('확장 메타데이터 JSON');
            $table->unsignedBigInteger('created_by')->nullable()->comment('생성 사용자 ID');
            $table->timestamp('occurred_at')->comment('거래 발생 일시');
            $table->timestamps();

            $table->unique('uuid', 'economy_transactions_uuid_unique');
            $table->index('wallet_id', 'economy_transactions_wallet_id_index');
            $table->index('related_wallet_id', 'economy_transactions_related_wallet_id_index');
            $table->index('transaction_type', 'economy_transactions_type_index');
            $table->index('occurred_at', 'economy_transactions_occurred_at_index');
            $table->index(['source_module', 'source_type', 'source_id'], 'economy_transactions_source_index');
            $table->index('correlation_id', 'economy_transactions_correlation_id_index');
            $table->unique('idempotency_key', 'economy_transactions_idempotency_key_unique');
            $table->foreign('wallet_id', 'economy_transactions_wallet_id_foreign')
                ->references('id')->on('economy_wallets')->restrictOnDelete();
            $table->foreign('related_wallet_id', 'economy_transactions_related_wallet_id_foreign')
                ->references('id')->on('economy_wallets')->restrictOnDelete();
            $table->foreign('reversed_transaction_id', 'economy_transactions_reversed_transaction_id_foreign')
                ->references('id')->on('economy_transactions')->restrictOnDelete();
            $table->foreign('created_by', 'economy_transactions_created_by_foreign')
                ->references('id')->on('users')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `".DB::getTablePrefix()."economy_transactions` COMMENT '이코노미 원장 거래'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            if (Schema::hasTable('economy_transactions')) {
                Schema::dropIfExists('economy_transactions');
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
