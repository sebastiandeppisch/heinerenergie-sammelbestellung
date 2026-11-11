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

    /**
     * Fields of the HexaNetz category the follow-up form „Erzähl mehr“ asks for, the join form does not.
     *
     * @var array<int, array{type: string, label: string, public: bool, help_text?: string, option?: string}>
     */
    public const array STORY_FIELDS = [
        ['type' => 'image', 'label' => 'Das ist mein Stadtnaturgarten', 'public' => true],
        ['type' => 'image', 'label' => 'Welches Tier hat mich schon besucht?', 'public' => true],
        ['type' => 'image', 'label' => 'Mein Lieblingsplatz', 'public' => true],
        ['type' => 'textarea', 'label' => 'Beschreibung Lieblingsplatz', 'public' => true],
        ['type' => 'checkbox', 'label' => 'Ich möchte ein Stadtnatur-Treffen in meinem Garten anbieten', 'public' => false, 'help_text' => 'Wir melden uns mit Terminvorschlägen', 'option' => 'Stadtnatur-Treffen anbieten'],
    ];

    public const int RADIUS_M = 150;

    public const int THRESHOLD_MEDIUM = 2;

    public const int THRESHOLD_DARK = 3;

    public const int JITTER_M = 40;

    /**
     * @var array<int, array{name: string, color: string, icon: string}>
     */
    public const array MEASURES = [
        ['name' => '5 m² Rasen ungemäht', 'color' => '#7cb342', 'icon' => 'wheat'],
        ['name' => 'Blühstreifen angelegt', 'color' => '#f4b400', 'icon' => 'flower'],
        ['name' => 'Totholzhaufen', 'color' => '#8d6e63', 'icon' => 'bug'],
        ['name' => 'Wildsträucher gepflanzt', 'color' => '#2e7d32', 'icon' => 'sprout'],
        ['name' => 'Baum gepflanzt', 'color' => '#33691e', 'icon' => 'tree-deciduous'],
        ['name' => 'Sandlinse für Wildbienen', 'color' => '#e0c068', 'icon' => 'hexagon'],
        ['name' => 'Regentonne & feuchte Ecke', 'color' => '#4fc3f7', 'icon' => 'droplets'],
        ['name' => 'Efeu an der Mauer stehen lassen', 'color' => '#558b2f', 'icon' => 'leaf'],
        ['name' => 'Steinhaufen als Unterschlupf', 'color' => '#9e9e9e', 'icon' => 'mountain'],
        ['name' => 'Laubhaufen überwintert', 'color' => '#e8720c', 'icon' => 'leafy-green'],
        ['name' => 'Staudenbeet statt Schotter', 'color' => '#ab47bc', 'icon' => 'flower-2'],
    ];

    /** Placeholder icons in public/hexnet/icons, admins pick one per measure. */
    public const array ICONS = [
        'wheat', 'flower', 'flower-2', 'bug', 'tree-deciduous', 'tree-pine', 'hexagon', 'droplets', 'leaf', 'leafy-green',
        'mountain', 'sprout', 'clover', 'bird', 'snail', 'worm', 'squirrel', 'feather', 'sun', 'shovel', 'fence',
    ];

    public static function iconUrl(string $icon): string
    {
        return asset('hexnet/icons/'.$icon.'.svg');
    }

    /** Colors of the levels open, light, medium and dark, changeable per HexaNetz. */
    public const string COLOR_OPEN = '#e4e9e2';

    public const string COLOR_LIGHT = '#b7e4a8';

    public const string COLOR_MEDIUM = '#52b45a';

    public const string COLOR_DARK = '#1f7a34';
}
