<?php

use App\Models\ChecklistEntry;
use App\Models\ChecklistEntryField;
use App\Models\FormField;
use App\Models\FormFieldOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_definitions', function (Blueprint $table) {
            $table->unsignedTinyInteger('type')->default(0)->after('group_id');
        });

        Schema::create('checklist_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('form_definition_id')->constrained();
            $table->foreignId('advice_id')->constrained('advices');
            $table->timestamps();

            $table->unique(['form_definition_id', 'advice_id']);
        });

        Schema::create('checklist_entry_fields', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(ChecklistEntry::class)->constrained();
            $table->foreignIdFor(FormField::class)->nullable()->constrained();
            $table->json('value')->nullable();
            $table->string('type');
            $table->string('label');
            $table->text('help_text')->nullable();
            $table->boolean('required')->default(false);
            $table->integer('sort_order');
            $table->timestamps();
        });

        Schema::create('checklist_entry_field_options', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(ChecklistEntryField::class)->constrained();
            $table->foreignIdFor(FormFieldOption::class)->nullable()->constrained();
            $table->string('label');
            $table->string('value');
            $table->integer('sort_order');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_entry_field_options');
        Schema::dropIfExists('checklist_entry_fields');
        Schema::dropIfExists('checklist_entries');

        Schema::table('form_definitions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
