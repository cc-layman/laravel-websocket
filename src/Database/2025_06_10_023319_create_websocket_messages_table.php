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
            $table->id();
            $table->string('msg_id')->unique()->comment('消息id');
            $table->string('group_id')->nullable()->comment('群房间id');
            $table->string('from')->comment('发送者用户ID');
            $table->string('classify')->comment('业务分类');
            $table->text('content')->comment('消息内容');
            $table->text('extra')->nullable()->comment('扩展内容');
            $table->enum('type', ['PRIVATE', 'GROUP', 'NOTICE', 'BROADCAST', 'ONLINE'])->comment('消息类型{PRIVATE:私聊}{GROUP:群聊}{NOTICE：广播用户}{BROADCAST：广播群组}{ONLINE：广播在线用户}');
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
