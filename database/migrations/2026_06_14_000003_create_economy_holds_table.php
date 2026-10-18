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
        Schema::create('economy_holds', function (Blueprint $table) {
            $table->id()->comment('잠금 ID');
            $table->uuid('uuid')->comment('외부 노출용 잠금 UUID');
            $table->unsignedBigInteger('wallet_id')->comment('대상 지갑 ID');
            $table->string('hold_status', 30)->default('active')->comment('잠금 상태');
            $table->decimal('amount', 18, 2)->comment('잠금 금액');
            $table->string('reason', 100)->nullable()->comment('잠금 사유');
            $table->string('source_module', 100)->nullable()->comment('잠금 발생 모듈');
            $table->string('source_type', 100)->nullable()->comment('잠금 발생 도메인 유형');
            $table->string('source_id', 100)->nullable()->comment('잠금 발생 도메인 식별자');
            $table->string('correlation_id', 100)->nullable()->comment('연관 작업 식별자');
            $table->mediumText('metadata')->nullable()->comment('확장 메타데이터 JSON');
            $table->unsignedBigInteger('created_by')->nullable()->comment('생성 사용자 ID');
            $table->unsignedBigInteger('released_by')->nullable()->comment('해제 사용자 ID');
            $table->timestamp('expires_at')->nullable()->comment('잠금 만료 예정 일시');
            $table->timestamp('released_at')->nullable()->comment('잠금 해제 일시');
            $table->timestamp('captured_at')->nullable()->comment('잠금 확정 차감 일시');
            $table->timestamps();

            $table->unique('uuid', 'economy_holds_uuid_unique');
            $table->index('wallet_id', 'economy_holds_wallet_id_index');
            $table->index('hold_status', 'economy_holds_status_index');
            $table->index('expires_at', 'economy_holds_expires_at_index');
            $table->index(['source_module', 'source_type', 'source_id'], 'economy_holds_source_index');
            $table->index('correlation_id', 'economy_holds_correlation_id_index');
            $table->foreign('wallet_id', 'economy_holds_wallet_id_foreign')
                ->references('id')->on('economy_wallets')->restrictOnDelete();
            $table->foreign('created_by', 'economy_holds_created_by_foreign')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('released_by', 'economy_holds_released_by_foreign')
                ->references('id')->on('users')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `".DB::getTablePrefix()."economy_holds` COMMENT '이코노미 지갑 잠금'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            if (Schema::hasTable('economy_holds')) {
                Schema::dropIfExists('economy_holds');
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
