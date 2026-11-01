<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const string OLD_DEFAULT_TEXT = 'Ich bin einverstanden, dass mein Eintrag auf der Karte von {initiative} öffentlich angezeigt wird, mit folgenden Angaben: {felder} sowie dem Standort. Ich kann meine Einwilligung jederzeit über den Link in der Bestätigungsmail widerrufen.';

    private const string POINT_TEXT = 'Ich bin einverstanden, dass mein Eintrag auf der Karte von {initiative} mit seinem Standort öffentlich angezeigt wird. {widerruf}';

    private const string DETAILS_TEXT = 'Ich bin einverstanden, dass mein Eintrag auf der Karte von {initiative} öffentlich angezeigt wird, mit folgenden Angaben: {felder} sowie dem Standort. {widerruf}';

    public function up(): void
    {
        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            $table->text('consent_point_text')->nullable()->after('consent_text');
            $table->foreignId('location_field_id')->nullable()->constrained('form_fields')->nullOnDelete();
        });

        $this->updateTexts();
    }

    /**
     * Targets get the default text of the first level. Texts that are still the old default get the new one, own texts
     * are kept.
     */
    public function updateTexts(): void
    {
        DB::table('form_definition_to_map_points')->whereNull('consent_point_text')->update(['consent_point_text' => self::POINT_TEXT]);
        DB::table('form_definition_to_map_points')->where('consent_text', self::OLD_DEFAULT_TEXT)->update(['consent_text' => self::DETAILS_TEXT]);
    }

    public function down(): void
    {
        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('location_field_id');
            $table->dropColumn('consent_point_text');
        });
    }
};
