<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            $table->text('consent_text')->nullable();
        });

        $this->fillConsentTexts();
    }

    /**
     * Existing targets get the default text, so a consent text is always stored.
     */
    public function fillConsentTexts(): void
    {
        DB::table('form_definition_to_map_points')->whereNull('consent_text')->update(['consent_text' => 'Ich bin einverstanden, dass mein Eintrag auf der Karte von {initiative} öffentlich angezeigt wird, mit folgenden Angaben: {felder} sowie dem Standort. Ich kann meine Einwilligung jederzeit über den Link in der Bestätigungsmail widerrufen.']);
    }

    public function down(): void
    {
        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            $table->dropColumn('consent_text');
        });
    }
};
