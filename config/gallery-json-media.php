<?php

// config for WebplusMultimedia\GalleryJsonMedia
return [
    'disk'           => "public",
    'root-directory' => 'web-attachements',
    'form'           => [
        'default' => [
            'image-accepted-text' => '.jpg, .svg, .png, .webp, .avif',
            'image-accepted-file-type' => ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp', 'image/avif'],
            'document-accepted-text' =>'.pdf, .doc(x), .xls(x)',
            'document-accepted-file-type' =>['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/wps-office.xlsx', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/wps-office.docx', 'application/pdf'],
        ],
    ]
];
