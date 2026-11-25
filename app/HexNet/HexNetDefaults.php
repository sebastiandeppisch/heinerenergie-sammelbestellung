<?php

declare(strict_types=1);

namespace App\HexNet;

use App\HexNet\Enums\HexNetScoringMode;

/**
 * Starting values of a new HexaNetz. Admins change them in the wizard.
 */
final class HexNetDefaults
{
    public const string TITLE = 'Highway der Tiere';

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

    public const int THRESHOLD_MEDIUM = 6;

    public const int THRESHOLD_DARK = 12;

    public const HexNetScoringMode SCORING_MODE = HexNetScoringMode::DISTINCT_MEASURES;

    public const int JITTER_M = 40;

    /**
     * @var array<int, array{name: string, color: string, icon: string}>
     */
    public const array MEASURES = [
        ['name' => 'Vogel-Spa', 'color' => '#29b6f6', 'icon' => 'vogel'],
        ['name' => 'Schmetterlingswiese', 'color' => '#ab47bc', 'icon' => 'schmetterling'],
        ['name' => 'Igeltore', 'color' => '#8d6e63', 'icon' => 'fence'],
        ['name' => 'Käfer-Keller', 'color' => '#5d4037', 'icon' => 'bug'],
        ['name' => 'Haselmaus-Hecke', 'color' => '#2e7d32', 'icon' => 'rat'],
        ['name' => 'Sandkasten für Wildbienen', 'color' => '#e0c068', 'icon' => 'hexagon'],
        ['name' => 'Eidechsenburg', 'color' => '#9e9e9e', 'icon' => 'castle'],
        ['name' => 'Fledermaus-Fassaden', 'color' => '#5c6bc0', 'icon' => 'moon'],
        ['name' => 'Humusfabrik der Regenwürmer', 'color' => '#c2185b', 'icon' => 'worm'],
        ['name' => 'Badeparadies für Frösche', 'color' => '#26a69a', 'icon' => 'waves'],
        ['name' => 'Hotel „Zum Baum“', 'color' => '#33691e', 'icon' => 'tree-deciduous'],
        ['name' => 'Neupflanzgebiet: Abpflastern', 'color' => '#7cb342', 'icon' => 'sprout'],
    ];

    /** Placeholder icons in public/hexnet/icons, admins pick one per measure. */
    public const array ICONS = [
        'wheat', 'flower', 'flower-2', 'bug', 'tree-deciduous', 'tree-pine', 'hexagon', 'droplets', 'leaf', 'leafy-green',
        'mountain', 'sprout', 'clover', 'bird', 'snail', 'worm', 'squirrel', 'feather', 'sun', 'shovel', 'fence',
        'vogel', 'schmetterling', 'rat', 'castle', 'moon', 'waves',
    ];

    /** Icons are SVG, except for a few PNG drafts until their SVG arrives. */
    public static function iconFile(string $icon): string
    {
        return file_exists(public_path('hexnet/icons/'.$icon.'.svg')) ? $icon.'.svg' : $icon.'.png';
    }

    public static function iconUrl(string $icon): string
    {
        return asset('hexnet/icons/'.self::iconFile($icon));
    }

    /** Colors of the levels open, light, medium and dark, changeable per HexaNetz. */
    public const string COLOR_OPEN = '#e4e9e2';

    public const string COLOR_LIGHT = '#b7e4a8';

    public const string COLOR_MEDIUM = '#52b45a';

    public const string COLOR_DARK = '#1f7a34';
}
