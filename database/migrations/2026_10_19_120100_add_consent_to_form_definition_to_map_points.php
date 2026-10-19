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
            $table->foreignId('contact_email_field_id')->nullable()->constrained('form_fields', indexName: 'fdtmp_contact_email_field_id_foreign')->nullOnDelete();
        });

        DB::table('form_definition_to_map_points')->update(['consent_text' => 'Ich bin einverstanden, dass mein Eintrag auf der Karte von {initiative} öffentlich angezeigt wird, mit folgenden Angaben: {felder} sowie dem Standort. Ich kann meine Einwilligung jederzeit über den Link in der Bestätigungsmail widerrufen.']);
    }

    public function down(): void
    {
        Schema::table('form_definition_to_map_points', function (Blueprint $table): void {
            $table->dropForeign('fdtmp_contact_email_field_id_foreign');
            $table->dropColumn(['consent_text', 'contact_email_field_id']);
        });
    }
};
