<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('comment_likes')) {
            return;
        }

        Schema::create('comment_likes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('comment_id');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'comment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_likes');
    }
};
