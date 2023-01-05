<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            [
                'key' => 'orderFormPassword',
                'value' => '',
            ],
            [
                'key' => 'orderFormText',
                'value' => 'Bestellungformular Text',
            ],
            [
                'key' => 'impress',
                'value' => 'Impressum',
            ],
            [
                'key' => 'datapolicy',
                'value' => 'Datenschutzerklärung',
            ]
        ];
        
        foreach ($settings as $setting) {
            if(!Setting::where('key', $setting['key'])->exists()) {
                Setting::create($setting);
            }
        }
    }
}
