<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('customer_goal')->nullable()->after('description_kh');
            $table->json('features')->nullable()->after('customer_goal');
            $table->json('applications')->nullable()->after('features');
            $table->json('material_notes')->nullable()->after('applications');
            $table->string('lead_time', 120)->nullable()->after('material_notes');
            $table->string('delivery_note', 160)->nullable()->after('lead_time');
            $table->json('compatible_product_slugs')->nullable()->after('delivery_note');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'customer_goal',
                'features',
                'applications',
                'material_notes',
                'lead_time',
                'delivery_note',
                'compatible_product_slugs',
            ]);
        });
    }
};
