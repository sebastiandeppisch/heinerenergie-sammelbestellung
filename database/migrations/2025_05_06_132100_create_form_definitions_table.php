<?php

use App\Models\Advice;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\SubmissionField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('form_definitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreignIdFor(Group::class)->constrained();
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignIdFor(FormDefinition::class)->constrained();
            $table->string('type'); // Enum/FieldType
            $table->string('label');
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->boolean('required')->default(false);
            $table->string('default_value')->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('min_length')->nullable();
            $table->integer('max_length')->nullable();
            $table->float('min_value')->nullable();
            $table->float('max_value')->nullable();
            $table->json('accepted_file_types')->nullable();
            $table->timestamps();
        });

        Schema::create('form_field_options', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignIdFor(FormField::class)->constrained();
            $table->string('label');
            $table->string('value');
            $table->integer('sort_order');
            $table->boolean('is_default');
            $table->timestamps();
            $table->boolean('is_required')->default(false);

        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->timestamps();

            $table->foreignIdFor(FormDefinition::class)->nullable();
            $table->foreignIdFor(Advice::class)->nullable();
            $table->timestamp('submitted_at');
            $table->string('form_name');
            $table->string('form_description')->nullable();
            $table->boolean('seen')->default(false);

            $table->foreignIdFor(Group::class);
        });

        Schema::create('submission_fields', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->timestamps();
            $table->foreignIdFor(FormSubmission::class)->constrained();
            $table->foreignIdFor(FormField::class)->nullable()->constrained();
            $table->json('value')->nullable();
            $table->string('type');
            $table->string('label');
            $table->text('help_text')->nullable();
            $table->boolean('required');
            $table->integer('sort_order');
        });

        Schema::create('submission_field_options', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignIdFor(SubmissionField::class)->constrained();
            $table->foreignIdFor(FormFieldOption::class)->nullable()->constrained();
            $table->string('label');
            $table->string('value');
            $table->integer('sort_order');
            $table->boolean('is_default');
            $table->timestamps();
            $table->boolean('is_required')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_field_options');
        Schema::dropIfExists('submission_fields');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_field_options');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('form_definitions');
    }
};
