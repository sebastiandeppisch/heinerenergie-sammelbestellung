<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->after('id');
        });

        Schema::table('advices', function (Blueprint $table) {
            $table->uuid('uuid')->after('id');
        });

        Schema::table('advice_status', function (Blueprint $table) {
            $table->uuid('uuid')->after('id');
        });

        $this->generateUuidsForExistingRecords();

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->change();
        });

        Schema::table('advices', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->change();
        });

        Schema::table('advice_status', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->change();
        });
    }

    private function generateUuidsForExistingRecords(): void
    {
        $tables = ['users', 'advices', 'advice_status'];

        foreach ($tables as $table) {
            $records = DB::table($table)->get();

            foreach ($records as $record) {
                DB::table($table)
                    ->where('id', $record->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('advices', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('advice_status', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
