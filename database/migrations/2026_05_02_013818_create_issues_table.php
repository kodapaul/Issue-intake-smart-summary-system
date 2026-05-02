<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('priority');
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('status')->default('open');
            $table->text('summary')->nullable();
            $table->text('suggested_action')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->string('issuer')->nullable();
            $table->string('issuer_email')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
