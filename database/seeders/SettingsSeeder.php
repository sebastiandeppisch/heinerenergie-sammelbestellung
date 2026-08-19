<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'impress',
                'value' => 'Impressum',
            ],
            [
                'key' => 'datapolicy',
                'value' => 'Datenschutzerklärung',
            ],
            [
                'key' => 'advisorInfo',
                'value' => 'Berater*innen Info',
            ],
            [
                'key' => 'newAdviceMail',
                'value' => 'Neue Beratung E-Mail',
            ],
            [
                'key' => 'defaultLogo',
                'value' => 'img/logo_without_background.png',
            ],
            [
                'key' => 'defaultFavicon',
                'value' => 'favicon.ico',
            ],
            [
                'key' => 'defaultName',
                'value' => 'CRM-System',
            ],
        ];

        foreach ($settings as $setting) {
            if (! Setting::where('key', $setting['key'])->exists()) {
                Setting::create($setting);
            }
        }
    }
}
