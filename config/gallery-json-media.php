<?php

// config for WebplusMultimedia\GalleryJsonMedia
return [
    'disk' => 'public',
    'root_directory' => 'web_attachments',
    'form' => [
        'default' => [
            'image_accepted_text' => '.jpg, .svg, .png, .webp, .avif',
            'image_accepted_file_type' => ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp', 'image/avif'],
            'document_accepted_text' => '.pdf, .doc(x), .xls(x)',
            'document_accepted_file_type' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/wps-office.xlsx', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/wps-office.docx', 'application/pdf'],
        ],
    ],
];
