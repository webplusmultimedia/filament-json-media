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
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class JsonImageController extends Controller
{
    public function __construct(protected UrlParser $urlParser) {}

    public function handle(string $requestPath): BinaryFileResponse | \Illuminate\Http\RedirectResponse | null
    {
        // Validate the signing token
        $token = $this->urlParser->signingToken($requestPath);

        if ($token !== request('_token')) {
            throw new NotFoundHttpException('Token mismatch');
        }

        if (! $params = $this->urlParser->parse($requestPath)) {
            return null;
        }
        ['path' => $path, 'width' => $width, 'height' => $height, 'options' => $options] = $params;

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk(config('gallery-json-media.disk'));
        /**@todo : for non local file Soon */
        // Create the image file
        $croppa = (new Croppa(filesystem: $storage, filePath: $path, width: $width, height: $height));
        $croppa->render();
        if ($storage->getAdapter() instanceof LocalFilesystemAdapter) {
            return redirect(url($requestPath));
        }

        $absolutePath = $storage->path($requestPath);

        return new BinaryFileResponse($absolutePath, 200, [
            'Content-Type' => $storage->mimeType($requestPath),
        ]);

    }
}
