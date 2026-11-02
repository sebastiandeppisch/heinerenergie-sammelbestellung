<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PublicConsent;
use App\HexNet\Enums\HexNetStatus;
use App\HexNet\HexNetDefaults;
use App\HexNet\Models\HexNet;
use App\HexNet\Services\HexGrid;
use App\HexNet\Services\HexNetSetupService;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\Models\User;
use App\Services\ImageStorage;
use App\Services\MapPointConsentService;
use App\Services\MapPointFieldService;
use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;
use App\ValueObjects\StoredImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Demo HexaNetz "Darmstadt wird grün" with about 130 stepping stones. For development and demos only.
 */
class HexNetDemoSeeder extends Seeder
{
    public const string SLUG = 'darmstadt-wird-gruen';

    private const array CITY = [
        [49.8952, 8.6292], [49.8978, 8.6598], [49.8872, 8.6842],
        [49.8672, 8.6902], [49.8532, 8.6702], [49.8548, 8.6352], [49.8712, 8.6222],
    ];

    /** name, lat, lng, spread in meters */
    private const array QUARTERS = [
        ['Martinsviertel', 49.8802, 8.6452, 340], ['Johannesviertel', 49.8846, 8.6412, 300],
        ['Woogsviertel', 49.8756, 8.6612, 260], ['Bessungen', 49.8592, 8.6512, 360],
        ['Kapellplatzviertel', 49.8722, 8.6572, 240], ['Paulusviertel', 49.8672, 8.6552, 300],
        ['Mathildenhöhe', 49.8752, 8.6672, 240], ['Verlegerviertel', 49.8862, 8.6552, 280],
        ['Am Bürgerpark', 49.8912, 8.6392, 300], ['Riedeselstraße', 49.8672, 8.6672, 240],
        ['Heimstättensiedlung', 49.8592, 8.6642, 280], ['Am Woog', 49.8742, 8.6702, 220],
        ['Westviertel', 49.8752, 8.6332, 280], ['Prinz-Christians-Weg', 49.8792, 8.6712, 220],
    ];

    private const array TITLES = [
        'Wilde Ecke', 'Bienenparadies', 'Naturgarten', 'Blühender Hinterhof', 'Kleine Wildnis', 'Vorgarten voller Leben',
        'Insektenoase', 'Garten am Hang', 'Hofgarten', 'Summende Wiese', 'Schmetterlingsbeet', 'Gemeinschaftsgarten',
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('HexNetDemoSeeder läuft nicht in Produktion.');

            return;
        }

        if (HexNet::where('slug', self::SLUG)->exists()) {
            $this->command?->info('Das Demo-HexaNetz existiert bereits.');

            return;
        }

        DB::transaction(fn () => $this->seed());

        $this->command?->info('Demo-HexaNetz angelegt: '.route('hexnet.public', self::SLUG));
    }

    private function seed(): void
    {
        mt_srand(4711);

        $group = Group::firstOrCreate(['name' => 'BUND Darmstadt'], ['description' => 'Demo-Initiative für HexaNetz', 'accepts_transfers' => false]);
        $this->attachAdmin($group);

        $polygon = new Polygon(array_map(fn (array $c): array => ['lat' => $c[0], 'lng' => $c[1]], self::CITY));
        $center = $polygon->getCenter();

        $hexNet = HexNet::create([
            'group_id' => $group->id,
            'name' => 'Darmstadt wird grün 2027',
            'slug' => self::SLUG,
            'status' => HexNetStatus::ACTIVE,
            'title' => HexNetDefaults::TITLE,
            'subtitle' => HexNetDefaults::SUBTITLE,
            'cta_text' => HexNetDefaults::CTA_TEXT,
            'play_area' => $polygon,
            'grid_origin_lat' => $center->lat,
            'grid_origin_lng' => $center->lng,
            'hex_radius_m' => HexNetDefaults::RADIUS_M,
            'grid_locked' => true,
        ]);

        $setup = app(HexNetSetupService::class);
        $category = $setup->createCategories($hexNet, HexNetDefaults::MAIN_CATEGORY, HexNetDefaults::MAIN_CATEGORY.' 2027', HexNetDefaults::MEASURES);
        $setup->createForm($hexNet->refresh());

        HexNet::firstOrCreate(['slug' => 'darmstadt-wird-gruen-2028'], [
            'group_id' => $group->id,
            'name' => 'Darmstadt wird grün 2028',
            'status' => HexNetStatus::DRAFT,
            'title' => HexNetDefaults::TITLE,
            'subtitle' => 'Entwurf für das nächste Jahr',
            'cta_text' => HexNetDefaults::CTA_TEXT,
        ]);

        $this->createPoints($hexNet, $group, $category, $polygon, 130);
    }

    /**
     * The first system admin, e.g. from AdminSeeder, administers the demo group.
     */
    private function attachAdmin(Group $group): void
    {
        $admin = User::where('is_admin', true)->orderBy('id')->first();

        if ($admin === null) {
            $this->command?->warn('Kein Systemadmin vorhanden, siehe SEED_ADMIN_* in der .env.');

            return;
        }

        $group->users()->syncWithoutDetaching([$admin->id => ['is_admin' => true]]);
    }

    private function createPoints(HexNet $hexNet, Group $group, MapPointCategory $category, Polygon $polygon, int $count): void
    {
        $grid = new HexGrid($polygon->getCenter(), 1);
        $characteristics = $category->parent?->characteristics()->with('formDefinition.fields')->get()->keyBy('id') ?? collect();
        $characteristicIds = $characteristics->keys()->all();
        $photoFieldKeys = $characteristics->map(fn (MapPointCharacteristic $c): ?string => $c->formDefinition?->fields->first()?->uuid)->filter()
            ->map(fn (string $uuid): string => MapPointConsentService::fieldKey($uuid))->values()->all();
        $photos = $this->photos();
        $contactEmail = User::where('is_admin', true)->orderBy('id')->value('email');
        $fieldService = app(MapPointFieldService::class);

        for ($i = 0; $i < $count; $i++) {
            $coordinate = $this->randomCoordinate($grid, $polygon);

            if ($coordinate === null) {
                continue;
            }

            $consent = $this->pick([[PublicConsent::NOT_REQUIRED, 45], [PublicConsent::GIVEN, 35], [PublicConsent::REFUSED, 20]]);
            $createdAt = Carbon::create(2025, 3, 1)->addDays(mt_rand(0, 600));

            $point = new MapPoint([
                'group_id' => $group->id,
                'category_id' => $category->id,
                'title' => self::TITLES[mt_rand(0, count(self::TITLES) - 1)],
                'description' => 'Hier summt und brummt es: ein Stück Stadtnatur für Insekten und Vögel.',
                'coordinate' => $coordinate,
                'published' => mt_rand(1, 100) <= 70,
                'public_consent' => $consent,
                'public_consent_at' => $consent === PublicConsent::NOT_REQUIRED ? null : $createdAt,
                'public_consent_fields' => $consent === PublicConsent::GIVEN
                    ? [MapPointConsentService::COORDINATE, MapPointConsentService::TITLE, MapPointConsentService::DESCRIPTION, ...$photoFieldKeys]
                    : null,
            ]);

            if ($contactEmail !== null && mt_rand(1, 100) <= 50) {
                $point->contact_email = $contactEmail;
                $point->contact_email_verified_at = $createdAt;
                $point->contact_enabled = true;
            }

            $point->created_at = $createdAt;
            $point->updated_at = $createdAt;
            $point->save();

            shuffle($characteristicIds);
            $measureIds = array_slice($characteristicIds, 0, mt_rand(1, 3));
            $point->characteristics()->attach($measureIds);

            $photoField = $characteristics->get($measureIds[0])?->formDefinition?->fields->first();

            if ($photoField !== null && $photos !== [] && mt_rand(1, 100) <= 55) {
                $fieldService->syncValues($point, [$photoField->uuid => [new StoredImage($photos[mt_rand(0, count($photos) - 1)])]]);
            }
        }

        $hexNet->touch();
    }

    /**
     * Five photos, downloaded once from picsum.photos and kept on the image disk. Without network, plain green
     * placeholders stand in.
     *
     * @return array<int, string>
     */
    private function photos(): array
    {
        $disk = Storage::disk(ImageStorage::DISK);
        $paths = [];

        foreach (range(1, 5) as $i) {
            $path = 'hexnet-demo/photo-'.$i.'.jpg';

            if (! $disk->exists($path)) {
                $disk->put($path, $this->downloadPhoto($i) ?? $this->placeholderPhoto($i));
            }

            $paths[] = $path;
        }

        return $paths;
    }

    private function downloadPhoto(int $i): ?string
    {
        try {
            $response = Http::timeout(10)->get('https://picsum.photos/seed/hexanetz-'.$i.'/1200/800.jpg');

            return $response->successful() && str_starts_with((string) $response->header('Content-Type'), 'image/') ? $response->body() : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function placeholderPhoto(int $i): string
    {
        $image = imagecreatetruecolor(1200, 800);

        for ($y = 0; $y < 800; $y++) {
            $shade = (int) (60 + 120 * $y / 800);
            imagefilledrectangle($image, 0, $y, 1199, $y, (int) imagecolorallocate($image, 40 + 10 * $i, $shade, 50));
        }

        ob_start();
        imagejpeg($image, null, 85);

        return (string) ob_get_clean();
    }

    private function randomCoordinate(HexGrid $grid, Polygon $polygon): ?Coordinate
    {
        for ($try = 0; $try < 40; $try++) {
            [, $lat, $lng, $spread] = self::QUARTERS[mt_rand(0, count(self::QUARTERS) - 1)];
            $local = new HexGrid(new Coordinate($lat, $lng), 1);
            $coordinate = $local->unproject($this->gauss() * $spread, $this->gauss() * $spread);

            if ($grid->contains($polygon, $coordinate)) {
                return $coordinate;
            }
        }

        return null;
    }

    private function gauss(): float
    {
        $u = max(mt_rand() / mt_getrandmax(), 1e-9);

        return sqrt(-2 * log($u)) * cos(2 * M_PI * mt_rand() / mt_getrandmax());
    }

    /**
     * @template T
     *
     * @param  array<int, array{0: T, 1: int}>  $weighted
     * @return T
     */
    private function pick(array $weighted): mixed
    {
        $roll = mt_rand(1, array_sum(array_column($weighted, 1)));

        foreach ($weighted as [$value, $weight]) {
            if (($roll -= $weight) <= 0) {
                return $value;
            }
        }

        return $weighted[0][0];
    }
}
