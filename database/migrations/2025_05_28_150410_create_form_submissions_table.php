<?php

use App\Models\FormDefinition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Advice;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();

            $table->foreignIdFor(FormDefinition::class)->constrained();
            $table->foreignIdFor(Advice::class)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('form_name');
            $table->string('form_description')->nullable();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
