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
    protected Filesystem $storage;

    protected function getFileName(): ?string
    {
        if ($fileName = $this->getContentKeyValue('file')) {
            if ($this->storage->exists($fileName)) {
                return $fileName;
            }
        }

        return null;
    }

    protected function getDisk(): Filesystem
    {
        if (! isset($this->storage)) {
            $this->storage = Storage::disk($this->getContentKeyValue('disk'));
        }

        return $this->storage;
    }

    protected function getContentKeyValue(string $key): mixed
    {
        return data_get($this->content, $key);
    }
}
