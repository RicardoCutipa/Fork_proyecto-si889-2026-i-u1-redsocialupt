<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'user_avatar')) {
                $table->string('user_avatar', 500)->nullable()->after('user_faculty');
            }
        });

        Schema::table('comments', function (Blueprint $table) {
            if (!Schema::hasColumn('comments', 'user_avatar')) {
                $table->string('user_avatar', 500)->nullable()->after('user_name');
            }
            if (!Schema::hasColumn('comments', 'user_faculty')) {
                $table->string('user_faculty', 100)->nullable()->after('user_avatar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'user_avatar')) {
                $table->dropColumn('user_avatar');
            }
        });

        Schema::table('comments', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('comments', 'user_avatar')) {
                $drop[] = 'user_avatar';
            }
            if (Schema::hasColumn('comments', 'user_faculty')) {
                $drop[] = 'user_faculty';
            }
            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }
};
