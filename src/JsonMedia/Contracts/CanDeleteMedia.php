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
 * Date: 21/02/2024 12:37
 */

namespace GalleryJsonMedia\JsonMedia\Contracts;

interface CanDeleteMedia
{
    public function delete(): void;
}
