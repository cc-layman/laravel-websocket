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
        Schema::create('websocket_group_users', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('group_code')->comment('群编号');
            $table->string('userid')->comment('用户ID');
            $table->tinyInteger('role')->default(0)->comment('成员角色（0=普通，1=管理员，2=群主）');
            $table->timestamp('joined_at')->comment('加入时间');
            $table->tinyInteger('status')->default(1)->comment('状态（1=正常，0=已退出/踢出）');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('websocket_group_users');
    }
};
