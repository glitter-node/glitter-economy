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
        Schema::create('economy_wallets', function (Blueprint $table) {
            $table->id()->comment('지갑 ID');
            $table->uuid('uuid')->comment('외부 노출용 지갑 UUID');
            $table->unsignedBigInteger('user_id')->nullable()->comment('소유 사용자 ID');
            $table->string('wallet_type', 50)->default('default')->comment('지갑 유형');
            $table->string('currency_code', 20)->default('POINT')->comment('가치 단위 코드');
            $table->decimal('balance', 18, 2)->default(0)->comment('총 잔액 캐시');
            $table->decimal('locked_balance', 18, 2)->default(0)->comment('잠금 잔액 캐시');
            $table->decimal('available_balance', 18, 2)->default(0)->comment('사용 가능 잔액 캐시');
            $table->string('status', 30)->default('active')->comment('지갑 상태');
            $table->mediumText('metadata')->nullable()->comment('확장 메타데이터 JSON');
            $table->timestamps();
            $table->softDeletes()->comment('삭제 일시');

            $table->unique('uuid', 'economy_wallets_uuid_unique');
            $table->unique(['user_id', 'wallet_type', 'currency_code'], 'economy_wallets_user_type_currency_unique');
            $table->index('user_id', 'economy_wallets_user_id_index');
            $table->index(['status', 'wallet_type'], 'economy_wallets_status_type_index');
            $table->foreign('user_id', 'economy_wallets_user_id_foreign')
                ->references('id')->on('users')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `".DB::getTablePrefix()."economy_wallets` COMMENT '이코노미 지갑 잔액 캐시'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            if (Schema::hasTable('economy_wallets')) {
                Schema::dropIfExists('economy_wallets');
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
