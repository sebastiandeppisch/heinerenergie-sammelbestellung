<?php

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
        Schema::table('form_definitions', function (Blueprint $table) {
            $table->text('success_message')->nullable()->after('description');
            $table->boolean('show_next_form_button')->default(false)->after('success_message');
            $table->string('next_form_button_text')->nullable()->after('show_next_form_button');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_definitions', function (Blueprint $table) {
            $table->dropColumn(['success_message', 'show_next_form_button', 'next_form_button_text']);
        });
    }
};
