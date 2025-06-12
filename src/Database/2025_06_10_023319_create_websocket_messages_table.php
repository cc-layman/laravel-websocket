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
            $table->engine = 'MyISAM';
            $table->id();
            $table->string('msg_id')->unique()->comment('消息id');
            $table->string('from')->comment('发送者用户ID');
            $table->string('to')->comment('接收者用户ID');
            $table->text('content')->comment('消息内容');
            $table->text('extra')->nullable()->comment('扩展内容');
            $table->enum('type', ['PRIVATE', 'GROUP', 'SYSTEM'])->comment('消息类型{PRIVATE:私聊}{GROUP:群聊}{SYSTEM：系统}');
            $table->enum('status', ['READ', 'UNREAD'])->default('UNREAD')->comment('是否已读{READ:已读}{UNREAD:未读}');
            $table->timestamps();
            $table->softDeletes();
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
