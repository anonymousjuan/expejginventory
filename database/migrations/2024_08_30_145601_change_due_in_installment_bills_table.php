<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('installment_bills', function (Blueprint $table) {
            $table->date('due')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('installment_bills', function (Blueprint $table) {
            $table->date('due')->nullable(false)->change();
        });
    }
};
