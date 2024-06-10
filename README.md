
# Easy Laravel PDF

## Overview

Easy Laravel PDF is a Laravel package that facilitates PDF generation within Laravel applications. This package utilizes a Node.js service, `node-html-to-pdf`, which can be run locally or via Docker to convert HTML to PDF.

## Installation

### Prerequisites

- PHP 7.3 or higher
- Laravel 6.0 or higher
- Composer
- Node.js and npm (optional, if running the service locally without Docker)
- Docker (optional, for using Docker container)

### Installing the Package

1. **Add the Package via Composer:**
   ```bash
   composer require hamdallah90/easy-laravel-pdf
   ```

2. **Publish Configuration:**
   Laravel's package discovery will automatically register the service provider. To publish the package configuration, run:
   ```bash
   php artisan vendor:publish --tag=pdf-config
   ```

## Configuration

Edit the published configuration file in `config/easy-laravel-pdf.php` to adjust settings like default PDF options.

## Using the Package

`EasyLaravelPdf` offers a variety of methods to generate PDF files. Here's how to use them in your Laravel applications:

### Load and Render a View

```php
use Jouda\EasyLaravelPdf\Facades\EasyLaravelPdf;

$pdf = EasyLaravelPdf::loadView('view.name', ['dataKey' => 'dataValue']);
return $pdf->stream('example.pdf');
```

### Load HTML Content Directly
To load raw HTML content into the PDF generator:

```php
use Jouda\EasyLaravelPdf\Facades\EasyLaravelPdf;

EasyLaravelPdf::loadView('view.name', ['dataKey' => 'dataValue'])->save('/path/to/file.pdf');
```

### Get the PDF as an UploadedFile
To get the PDF as an UploadedFile, which can be useful for testing or further manipulation:

```php
use Jouda\EasyLaravelPdf\Facades\EasyLaravelPdf;

$file = EasyLaravelPdf::loadHtml('<h1>Test PDF</h1>')->getFile();
$file->store('save-to-s3-or-local-path')
```

### Stream the PDF Directly to the Browser
To stream the PDF directly to the browser, which is useful for inline viewing:

```php
use Jouda\EasyLaravelPdf\Facades\EasyLaravelPdf;

return EasyLaravelPdf::loadHtml('<h1>Test PDF</h1>')->stream('optional-filename.pdf');  // Streams the PDF; if a filename is provided, it will be used
```

## Configuration and Customization
You can configure puppeteer options and launch arguments for more control over the PDF generation:

```php
use Jouda\EasyLaravelPdf\Facades\EasyLaravelPdf;
$pdf = EasyLaravelPdf::setOptions(['format' => 'A4']);
$pdf->setPuppeteerLunchArgs(['--no-sandbox', '--disable-setuid-sandbox']);
$pdf->loadHtml('<h1>Customized PDF</h1>');
$pdf->save('path/to/your/customized.pdf');
```

## Node HTML to PDF Service

This service is essential for PDF generation and can be run locally or in a Docker container.

### Running Locally

1. Navigate to the `node-html-to-pdf` directory.
2. Install dependencies:
   ```bash
   npm install
   ```
3. Start the server:
   ```bash
   npm start
   ```

### Running with Docker

1. **Using the Dockerfile:**
   Build the Docker image using the provided Dockerfile:
   ```bash
   cd node-html-to-pdf
   docker build -t your-username/html-to-pdf .
   docker run -p 3000:3000 your-username/html-to-pdf
   ```

2. **Using the Pre-built Image:**
   Alternatively, you can use a pre-built Docker image available on Docker Hub:
   ```bash
   docker pull hamdallah/html-to-pdf
   docker run -p 3000:3000 hamdallah/html-to-pdf
   ```

## Support

For issues, feature requests, or contributions, please use the GitHub issues section for this repository.
