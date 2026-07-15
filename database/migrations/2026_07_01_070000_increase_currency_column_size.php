<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['orders', 'products', 'payments'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->string('currency', 50)->default('USD')->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['orders', 'products', 'payments'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->string('currency', 10)->default('USD')->change();
            });
        }
    }
};
