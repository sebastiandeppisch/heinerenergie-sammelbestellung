<?php

namespace App\Http\Controllers\Api;

use App\Contracts\NextcloudFileClientContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateNextcloudFolderRequest;
use App\Http\Requests\LinkNextcloudFolderRequest;
use App\Http\Requests\UploadNextcloudFileRequest;
use App\Models\Advice;
use App\Nextcloud\NextcloudFolderResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NextcloudAdviceController extends Controller
{
    public function __construct(
        private readonly NextcloudFileClientContract $nextcloud,
        private readonly NextcloudFolderResolver $resolver,
    ) {}

    public function search(Advice $advice, Request $request): JsonResponse
    {
        $this->authorize('update', $advice);

        $searchPath = $advice->group->nextcloud_search_path ?? '/';
        $slug = $request->string('q')->toString();

        $dirs = $this->nextcloud->searchDirs($searchPath, $slug);

        return response()->json($dirs);
    }

    public function browse(Advice $advice, Request $request): JsonResponse
    {
        $this->authorize('update', $advice);

        $path = $request->string('path', '/')->toString() ?: '/';
        $items = $this->nextcloud->dirListing($path);

        return response()->json($items);
    }

    public function createFolder(CreateNextcloudFolderRequest $request, Advice $advice): JsonResponse
    {
        $dir = $this->nextcloud->createDir($request->validated('parent_path'), $request->validated('name'));

        $advice->update([
            'nextcloud_folder_id' => $dir->fileId,
            'nextcloud_folder_path' => $dir->path,
        ]);

        return response()->json($dir);
    }

    public function link(LinkNextcloudFolderRequest $request, Advice $advice): JsonResponse
    {
        $advice->update([
            'nextcloud_folder_id' => $request->validated('fileId'),
            'nextcloud_folder_path' => $request->validated('path'),
        ]);

        return response()->json(['fileId' => $advice->nextcloud_folder_id, 'path' => $advice->nextcloud_folder_path]);
    }

    public function unlink(Advice $advice): JsonResponse
    {
        $this->authorize('update', $advice);

        $advice->update([
            'nextcloud_folder_id' => null,
            'nextcloud_folder_path' => null,
        ]);

        return response()->json(null, 204);
    }

    public function upload(UploadNextcloudFileRequest $request, Advice $advice): JsonResponse
    {
        $path = $this->resolver->resolve($advice);
        $uploadedFile = $request->file('file');

        $stream = fopen($uploadedFile->getRealPath(), 'rb');
        $file = $this->nextcloud->uploadFile($path, $uploadedFile->getClientOriginalName(), $stream);
        fclose($stream);

        return response()->json($file);
    }

    public function download(Advice $advice, string $fileId): StreamedResponse
    {
        $this->authorize('view', $advice);

        $stream = $this->nextcloud->downloadFile($fileId);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        });
    }

    public function files(Advice $advice): JsonResponse
    {
        $this->authorize('view', $advice);

        if (! $advice->nextcloud_folder_id) {
            return response()->json([]);
        }

        try {
            $path = $this->resolver->resolve($advice);

            return response()->json($this->nextcloud->dirListing($path));
        } catch (RuntimeException) {
            return response()->json(null, 422);
        }
    }
}
