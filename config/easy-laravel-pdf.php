<?php

// config for Jouda/EasyLaravelPdf
return [
    "url" => env('EASY_LARAVEL_HTML_TO_PDF_URL', 'http://localhost:3000/html-to-pdf'),

    /**
     * The provider to use to convert the html to pdf
     * 
     * The available providers are:
     * - html-to-pdf
     * - gotenberg (you need to install the gotenberg package - composer require gotenberg/gotenberg-php)
     * 
     * @var string
     * @access public
     */
    'provider' => 'html-to-pdf',
];