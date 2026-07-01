<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('title_kh')->nullable();
            $table->string('slug')->unique();
            $table->text('overview')->nullable();
            $table->string('setting', 100)->nullable();
            $table->string('focus', 100)->nullable();
            $table->string('goal', 500)->nullable();
            $table->text('challenge')->nullable();
            $table->text('response')->nullable();
            $table->json('scope')->nullable();
            $table->json('outcomes')->nullable();
            $table->json('process')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('og_image', 500)->nullable();
            $table->json('structured_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
