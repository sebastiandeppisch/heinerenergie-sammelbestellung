<?php

use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\Group;
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
        Schema::create('form_definition_to_advice', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->timestamps();

            $table->foreignIdFor(FormDefinition::class)->constrained();

            $table->foreignIdFor(FormField::class, 'address_field_id')->constrained();

            $table->foreignIdFor(FormField::class, 'email_field_id')->constrained();

            $table->foreignIdFor(FormField::class, 'phone_field_id')->constrained();

            $table->foreignIdFor(FormField::class, 'first_name_field_id')->constrained();

            $table->foreignIdFor(FormField::class, 'last_name_field_id')->constrained();

            $table->foreignIdFor(FormField::class, 'advice_type_field_id')->constrained();

            $table->foreignIdFor(Group::class, 'default_group_id')
                ->constrained();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_definition_to_advice');
    }
};
