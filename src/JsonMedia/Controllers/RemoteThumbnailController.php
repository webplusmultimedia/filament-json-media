<?php

declare(strict_types=1);

namespace GalleryJsonMedia\JsonMedia\Controllers;

use GalleryJsonMedia\JsonMedia\ImageManipulation\Croppa;
use GalleryJsonMedia\JsonMedia\UrlParser;
use GalleryJsonMedia\Support\Disk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Serves the thumbnails the web server cannot generate on a 404 : remote disks and private files.
 * The route is signed, so the disk, the path and the visibility come from the package.
 */
class RemoteThumbnailController extends Controller
{
    public function __invoke(Request $request, string $disk, string $path): RedirectResponse
    {
        $thumbnail = UrlParser::make()->parseThumbnailPath($path);
        abort_if($thumbnail === false, 404);

        $visibility = $request->query('visibility') === 'private' ? 'private' : 'public';
        $storage = Storage::disk($disk);
        abort_unless($storage->exists($thumbnail['path']), 404);

        $croppa = new Croppa($storage, $thumbnail['path'], $thumbnail['width'], $thumbnail['height'], $disk, $visibility);

        if (! $storage->exists($croppa->getPathNameForThumbs())) {
            try {
                $croppa->render();
            } catch (Throwable $exception) {
                report($exception);

                // The original image is better than a broken one
                return redirect()->away(Disk::url($storage, $thumbnail['path'], $visibility));
            }
        }

        return redirect()->away(Disk::url($storage, $croppa->getPathNameForThumbs(), $visibility));
    }
}
