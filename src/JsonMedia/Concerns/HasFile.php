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
 * Date: 05/03/2024 08:51
 */

namespace GalleryJsonMedia\JsonMedia\Concerns;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

trait HasFile
{
    protected function getFileName(): ?string
    {
        if ($fileName = $this->getContentKeyValue('file')) {
            $disk = $this->getDisk();
            if ($disk->exists($fileName)) {
                return $fileName;
            }

        }

        return null;
    }

    protected function getDisk(): Filesystem
    {
        return Storage::disk($this->getContentKeyValue('disk'));
    }

    protected function getContentKeyValue(string $key): mixed
    {
        return data_get($this->content, $key);
    }
}
