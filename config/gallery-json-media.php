<?php

declare(strict_types=1);

// config for WebplusMultimedia\GalleryJsonMedia
use Spatie\Image\Enums\CropPosition;


return [
    'disk' => 'public',
    'root_directory' => 'web_attachments',
    'images' => [
        'path' => 'storage/(.*)$',
        'signing_key' => 'app.key',
        'driver' => 'imagick', // gd or imagick
        'quality' => 80,
        'thumbnails-crop-method' => null,//CropPosition::Center->value,
        'thumbnails-saved-format' => null, // Manipulations::FORMAT_PNG / following formats are supported: FORMAT_JPG, FORMAT_PJPG, FORMAT_PNG, FORMAT_GIF, FORMAT_WEBP and FORMAT_TIFF

    ],
    'form' => [
        'default' => [
            'image_accepted_text' => '.jpg, .svg, .png, .webp, .avif',
            'image_accepted_file_type' => ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp', 'image/avif'],
            'document_accepted_text' => '.pdf, .doc(x), .xls(x)',
            'document_accepted_file_type' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/wps-office.xlsx', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/wps-office.docx', 'application/pdf'],
        ],
    ],
];
