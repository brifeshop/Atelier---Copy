<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();            // 1-1000, 1-1100, dst
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->foreignId('parent_id')->nullable()
                  ->constrained('chart_of_accounts')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('is_header')->default(false);    // akun header: tidak bisa diposting
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};