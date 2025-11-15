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
            $table->text('dashboard_info')->nullable()->after('description');
        });

        // Copy current advisorInfo setting to all groups
        $advisorInfo = Setting::get('advisorInfo');
        if ($advisorInfo !== null) {
            Group::query()->update(['dashboard_info' => $advisorInfo]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('dashboard_info');
        });
    }
};
