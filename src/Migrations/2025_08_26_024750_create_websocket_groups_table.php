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
        Schema::create('websocket_groups', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('name')->comment('群名称');
            $table->string('userid')->comment('群主ID');
            $table->string('description')->nullable()->comment('群简介/公告');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('websocket_groups');
    }
};
