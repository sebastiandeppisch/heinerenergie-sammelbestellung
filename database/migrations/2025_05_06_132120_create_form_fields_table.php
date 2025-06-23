
<?php

use App\Models\FormDefinition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
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
            $table->boolean('enable_processing')->default(false); // for geo_coordinate
            $table->boolean('enable_email_confirmation')->default(false); // for email fields
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
