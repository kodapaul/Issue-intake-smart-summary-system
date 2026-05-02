<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('playbook_entries', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->json('triggers');
            $table->text('summary_template');
            $table->text('suggested_action');
            $table->json('troubleshooting_steps');
            $table->json('faqs');
            $table->string('category_hint')->nullable();
            $table->string('priority_hint')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playbook_entries');
    }
};
