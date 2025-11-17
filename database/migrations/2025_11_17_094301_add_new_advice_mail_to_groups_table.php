<?php

use App\Models\Group;
use App\Models\Setting;
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
        Schema::table('groups', function (Blueprint $table) {
            $table->text('new_advice_mail')->nullable()->after('dashboard_info');
        });

        // Copy current newAdviceMail setting to all groups
        $newAdviceMail = Setting::get('newAdviceMail');
        if ($newAdviceMail !== null) {
            Group::query()->update(['new_advice_mail' => $newAdviceMail]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('new_advice_mail');
        });
    }
};
