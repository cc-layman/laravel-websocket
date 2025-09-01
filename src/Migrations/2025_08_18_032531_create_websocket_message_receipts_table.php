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
            $table->uuid()->primary();
            $table->string('message_uuid')->index()->comment('消息uuid');
            $table->string('receiver')->index()->comment('接收者唯一标识');
            $table->string('sn')->comment('消息序列号');
            $table->integer('index')->default(1)->comment('消息分片索引');
            $table->integer('count')->default(0)->comment('消息分片总数(0代表直播无限片)');
            $table->tinyInteger('pushed')->default(1)->comment('是否推送{1:未推送}{2:已推送}');
            $table->timestamp('read')->nullable()->comment('已读时间');
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
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
