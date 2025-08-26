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
        Schema::create('websocket_messages', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->tinyInteger('type')->comment('消息类型:{1:文本}{2:图片}{3:视屏}{4:文件}{5:控制消息}{6:音频}{7:表情/动画}{8:消息确认}{9:其他}');
            $table->string('sn')->comment('消息序列号');
            $table->integer('index')->default(1)->comment('消息分片索引');
            $table->integer('count')->default(0)->comment('消息分片总数(0代表直播无限片)');
            $table->tinyInteger('notice_type')->default(0)->comment('通知类型:{1:私聊}{2:群聊}{3:系统}{4:广播}');
            $table->string('sender')->index()->comment('发送者唯一标识');
            $table->string('group_code')->nullable()->index()->comment('群聊唯一标识');
            $table->text('payload')->comment('消息内容');
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
            $table->unique(['sn', 'index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('websocket_messages');
    }
};
