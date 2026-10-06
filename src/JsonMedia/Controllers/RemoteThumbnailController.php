<?php

declare(strict_types=1);

namespace GalleryJsonMedia\JsonMedia\Controllers;

use GalleryJsonMedia\JsonMedia\ImageManipulation\Croppa;
use GalleryJsonMedia\JsonMedia\UrlParser;
use GalleryJsonMedia\Support\Disk;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Serves the thumbnails the web server cannot generate on a 404 : remote disks and private files.
 * The route is signed, so the disk, the path and the visibility come from the package.
 */
class RemoteThumbnailController extends Controller
{
    public function __invoke(Request $request, string $disk, string $path): Response
    {
        $thumbnail = UrlParser::make()->parseThumbnailPath($path);
        abort_if($thumbnail === false, 404);

        $visibility = $request->query('visibility') === 'private' ? 'private' : 'public';
        $storage = Storage::disk($disk);
        abort_unless($storage->exists($thumbnail['path']), 404);

        $croppa = new Croppa($storage, $thumbnail['path'], $thumbnail['width'], $thumbnail['height'], $disk, $visibility, $thumbnail['format']);

        if (! $storage->exists($croppa->getPathNameForThumbs())) {
            try {
                $croppa->render();
            } catch (Throwable $exception) {
                report($exception);

                // The original image is better than a broken one
                return $this->respondWith($storage, $thumbnail['path'], $visibility);
            }
        }

        return $this->respondWith($storage, $croppa->getPathNameForThumbs(), $visibility);
    }

    private function respondWith(Filesystem $storage, string $path, string $visibility): Response
    {
        // Laravel serves a local disk on /storage, where the token route of the public thumbnails would catch the redirection
        if (Disk::isLocal($storage)) {
            /** @var FilesystemAdapter $storage */
            return $storage->response($path);
        }

        return redirect()->away(Disk::url($storage, $path, $visibility));
    }
}
