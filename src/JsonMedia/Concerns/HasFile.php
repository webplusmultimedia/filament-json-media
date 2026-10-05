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

use GalleryJsonMedia\Support\Disk;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

trait HasFile
{
    protected Filesystem $storage;

    protected function getFileName(): ?string
    {
        if ($fileName = $this->getContentKeyValue('file')) {
            // A remote disk is not queried on each rendering : the entry is trusted
            if (! Disk::isLocal($this->getDisk()) || $this->getDisk()->exists($fileName)) {
                return $fileName;
            }
        }

        return null;
    }

    /**
     * Entries saved before the visibility was recorded are public.
     */
    public function getVisibility(): string
    {
        return $this->getContentKeyValue('visibility') === 'private' ? 'private' : 'public';
    }

    protected function getFileUrl(string $fileName): string
    {
        return Disk::url($this->getDisk(), $fileName, $this->getVisibility());
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
