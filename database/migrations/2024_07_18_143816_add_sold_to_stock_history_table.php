<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_history', function (Blueprint $table) {
            $table->integer('running_sold')->default(0);
            $table->uuid('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock_history', function (Blueprint $table) {
            $table->dropColumn('running_sold');
            $table->uuid('user_id')->nullable(false)->change();
        });
    }
};
