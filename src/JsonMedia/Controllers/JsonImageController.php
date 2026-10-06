<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 *
 * @category    Category
 *
 * @author      daniel
 *
 * @link        http://webplusm.net
 * Date: 16/04/2024 10:09
 */

namespace GalleryJsonMedia\JsonMedia\Controllers;

use GalleryJsonMedia\JsonMedia\ImageManipulation\Croppa;
use GalleryJsonMedia\JsonMedia\UrlParser;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JsonImageController extends Controller
{
    public function __construct(protected UrlParser $urlParser) {}

    /**
     * The signature is checked by the route middleware.
     */
    public function handle(string $requestPath): BinaryFileResponse | RedirectResponse | null
    {
        if (! $params = $this->urlParser->parse($requestPath)) {
            return null;
        }
        ['path' => $path, 'width' => $width, 'height' => $height, 'format' => $format] = $params;

        // The signed url gives the disk of the image, which may not be the default one (an older image on a local disk)
        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk(request()->query('disk', config('gallery-json-media.disk')));
        abort_unless($storage->exists($path), 404);
        /**@todo : for non local file Soon */
        // Create the image file
        $croppa = (new Croppa(storage: $storage, filePath: $path, width: $width, height: $height, format: $format));
        $croppa->render();

        if (! $storage->getAdapter() instanceof LocalFilesystemAdapter) {
            return redirect(url($requestPath));
        }

        return redirect(url($requestPath)); // response()->file($croppa->getFullPathForThumb());
    }
}
