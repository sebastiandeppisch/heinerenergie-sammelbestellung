<?php

use App\Models\FormField;
use App\Models\FormSubmission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->foreignIdFor(FormSubmission::class)->constrained();
            $table->foreignIdFor(FormField::class)->constrained();
            $table->string('field_label');
            $table->string('field_type');
            $table->json('value');
            $table->integer('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_fields');
    }
};
