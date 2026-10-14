<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('tld', 20);
            $table->unsignedSmallInteger('registered_year');
            $table->unsignedInteger('backlinks')->default(0);
            $table->unsignedInteger('referring_domains')->default(0);
            $table->unsignedTinyInteger('authority_score')->default(0);
            $table->string('niche', 60);
            $table->unsignedInteger('price_cents');
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            // Composite indexes lead with status because every public query filters on it.
            $table->index(['status', 'tld']);
            $table->index(['status', 'niche']);
            $table->index(['status', 'price_cents']);
            $table->index(['status', 'authority_score']);
            $table->index(['status', 'registered_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
