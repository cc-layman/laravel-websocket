<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('websocket_message_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('msg_id')->index()->comment('消息id');
            $table->string('to')->comment('接收者用户ID');
            $table->enum('pushed', ['PENDING', 'SUCCESS'])->default('PENDING')->comment('是否推送{PENDING:未推送}{SUCCESS:已推送}');
            $table->timestamp('read')->nullable()->comment('已读时间');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['msg_id', 'to'], 'msg_to_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('websocket_message_receipts');
    }
};
