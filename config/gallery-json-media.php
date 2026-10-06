<?php

declare(strict_types=1);

// config for WebplusMultimedia\GalleryJsonMedia

use Spatie\Image\Enums\ImageDriver;

return [
    'disk' => 'public',
    // 'public' or 'private' : default visibility of the uploaded files, a field can change it with ->visibility()
    'visibility' => 'public',
    'root_directory' => 'web_attachments',
    'images' => [
        'path' => 'storage/(.*)$',
        'driver' => ImageDriver::Imagick, // gd or imagick
        'quality' => 70,
        'thumbnails-crop-method' => null,
        'thumbnails-saved-format' => [],
        // Lifetime in minutes of the links to private files (temporary urls)
        'temporary_url_ttl' => 5,
        // The responsive-image component offers the browser several sizes and formats of the thumbnails
        'responsive' => [
            // Widths of the srcset, up to twice the displayed width (high density screens)
            'widths' => [320, 640, 960, 1280, 1920],
            // Formats of the <picture> sources by order of preference, 'avif' needs a driver that supports it
            'formats' => ['webp'],
        ],

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
