<?php

declare(strict_types=1);

namespace App\HexNet;

/**
 * Starting values of a new HexaNetz. Admins change them in the wizard.
 */
final class HexNetDefaults
{
    public const string TITLE = 'Darmstadt wird grün';

    public const string SUBTITLE = 'Live-Spielstand der Trittstein-Kampagne';

    public const string CTA_TEXT = 'Eine Grünfläche wird zum Trittstein, sobald sie die Mindestanforderungen erfüllt – etwa 5 m² Rasen ungemäht stehen lassen. Melde deinen Trittstein an und färbe deine Wabe grün.';

    public const string MAIN_CATEGORY = 'Trittsteine';

    public const int RADIUS_M = 150;

    public const int THRESHOLD_MEDIUM = 2;

    public const int THRESHOLD_DARK = 3;

    public const int JITTER_M = 40;

    /**
     * @var array<int, array{name: string, color: string}>
     */
    public const array MEASURES = [
        ['name' => '5 m² Rasen ungemäht', 'color' => '#7cb342'],
        ['name' => 'Blühstreifen angelegt', 'color' => '#f4b400'],
        ['name' => 'Totholzhaufen', 'color' => '#8d6e63'],
        ['name' => 'Wildsträucher gepflanzt', 'color' => '#2e7d32'],
        ['name' => 'Sandlinse für Wildbienen', 'color' => '#e0c068'],
        ['name' => 'Regentonne & feuchte Ecke', 'color' => '#4fc3f7'],
        ['name' => 'Efeu an der Mauer stehen lassen', 'color' => '#558b2f'],
        ['name' => 'Steinhaufen als Unterschlupf', 'color' => '#9e9e9e'],
        ['name' => 'Laubhaufen überwintert', 'color' => '#e8720c'],
        ['name' => 'Staudenbeet statt Schotter', 'color' => '#ab47bc'],
    ];

    public const string COLOR_OPEN = '#e4e9e2';

    public const string COLOR_LIGHT = '#b7e4a8';

    public const string COLOR_MEDIUM = '#52b45a';

    public const string COLOR_DARK = '#1f7a34';
}
