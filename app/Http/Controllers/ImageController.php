<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FormSubmission;
use App\Models\MapPoint;
use App\Models\SubmissionField;
use App\Services\ImageStorage;
use App\Services\MapPointImageAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the images of form submissions and map points. Images someone may not see are reported as missing,
 * so their existence is not revealed.
 */
class ImageController extends Controller
{
    public function __construct(private readonly ImageStorage $imageStorage) {}

    public function formSubmission(Request $request, FormSubmission $formSubmission, string $image): Response
    {
        $path = 'form-images/'.$formSubmission->uuid.'/'.$image;
        $isStored = $formSubmission->submissionFields()->get()->contains(fn (SubmissionField $field): bool => in_array($path, (array) $field->value, true));

        abort_unless($isStored && Gate::allows('view', $formSubmission), 404);

        return $this->imageStorage->response($request, $path, $this->width($request));
    }

    public function mapPoint(Request $request, MapPoint $mapPoint, string $image, MapPointImageAccess $access): Response
    {
        $path = $mapPoint->imageDirectory().'/'.$image;

        abort_unless($access->allows($request->user(), $mapPoint, $path), 404);

        return $this->imageStorage->response($request, $path, $this->width($request));
    }

    private function width(Request $request): ?int
    {
        return $request->has('w') ? $request->integer('w') : null;
    }
}
