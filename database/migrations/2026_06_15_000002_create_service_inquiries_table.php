<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_inquiries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->default('service')->index();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable()->index();
            $table->string('company')->nullable();
            $table->string('project_name')->nullable();
            $table->string('project_location', 500)->nullable();
            $table->string('project_size', 100)->nullable();
            $table->string('budget_range', 100)->nullable();
            $table->date('preferred_date')->nullable();
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('admin_notes')->nullable();
            $table->decimal('quoted_price', 12, 2)->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_inquiries');
    }
};
