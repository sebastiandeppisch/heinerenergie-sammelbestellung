<?php

use App\Models\Advice;
use App\Models\FormDefinition;
use App\Models\Group;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();

            $table->foreignIdFor(FormDefinition::class)->nullable();
            $table->foreignIdFor(Advice::class)->nullable();
            $table->timestamp('submitted_at');
            $table->string('form_name');
            $table->string('form_description')->nullable();
            $table->boolean('seen')->default(false);

            $table->foreignIdFor(Group::class);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
