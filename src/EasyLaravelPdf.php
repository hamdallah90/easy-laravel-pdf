<?php

namespace Jouda\EasyLaravelPdf;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Gotenberg\Gotenberg;
use Gotenberg\Stream;

class EasyLaravelPdf
{
    /**
     * The default options
     * 
     * @var array
     * @access private
     */
    private array $defaultOptions = [
        'emulatedMediaType' => 'print',
        'margin' => [
            'top' => '0',
            'right' => '0',
            'bottom' => '0',
            'left' => '0',
        ],
    ];

    /**
     * The options to pass to the puppeteer
     * 
     * @var array
     * @access private
     */
    private array $options = [];
    
    /**
     * The html content to convert to pdf
     * 
     * @var string
     * @access private
     */
    private string $html = '';

    /**
     * The url to send the html to and get the pdf
     * 
     * @var string
     * @access private
     */
    private string $html_to_pdf_url = '';

    /**
     * The url to send the html to and get the pdf
     * 
     * @var string
     * @access private
     */
    private string $url = '';

    /**
     * constructor
     * 
     * @param array $options The options to pass to the puppeteer
     * @param array $puppeteerLunchArgs The puppeteer launch args
     * @param string $filename The filename of the pdf
     * @access public
     */
    public function __construct(
        array $options = [],
        protected array $puppeteerLunchArgs = [],
        protected string $filename = 'filename.pdf',
    ) {
        $this->html_to_pdf_url = config('easy-laravel-pdf.url');

        if (empty($this->html_to_pdf_url)) {
            throw new \Exception('Please set the html_to_pdf_url in the config file');
        }

        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * Set the options
     * 
     * @param array $options The options to pass to the puppeteer
     * @return self
     * @access public
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->defaultOptions, $options);
        return $this;
    }

    /**
     * Set the puppeteer launch args
     * 
     * @param array $puppeteerLunchArgs The puppeteer launch args
     * @return self
     * @access public
     */
    public function setPuppeteerLunchArgs(array $puppeteerLunchArgs)
    {
        $this->puppeteerLunchArgs = $puppeteerLunchArgs;
        return $this;
    }

    /**
     * Add an option
     * 
     * @param string $key The option key
     * @param mixed $value The option value
     * @return self
     * @access public
     */
    public function addOption(string $key, mixed $value)
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * Add a puppeteer launch arg
     * 
     * @param string $key The puppeteer launch arg key
     * @param mixed $value The puppeteer launch arg value
     * @return self
     * @access public
     */
    public function addPuppeteerLunchArg(string $key, mixed $value)
    {
        $this->puppeteerLunchArgs[$key] = $value;
        return $this;
    }

    /**
     * Load a view
     * 
     * @param string $view The view name
     * @param array $data The view data
     * @return self
     * @access public
     */
    public function loadView(string $view, array $data = [])
    {
        $this->html = view($view, $data)->render();
        return $this;
    }

    /**
     * Load html content
     * 
     * @param string $html The html content
     * @return self
     * @access public
     */
    public function loadHtml(string $html)
    {
        $this->html = $html;
        return $this;
    }

    /**
     * Set a url
     * 
     * @param string $url The url
     * @return self
     * @access public
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Save the pdf to a file
     * 
     * @param string $path The path to save the pdf to
     * @return self
     * @access public
     */
    public function save(string $path)
    {
        $pdf = $this->sendHtmlToServerAndGetPdf();
        file_put_contents($path, $pdf);
        return $this;
    }

    /**
     * Get the pdf as a file
     * 
     * @return UploadedFile
     * @access public
     */
    public function getFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($this->filename, $this->sendHtmlToServerAndGetPdf());
    }

    /**
     * Stream the pdf
     * 
     * @param string $filename The filename of the pdf
     * @return self
     * @access public
     */
    public function stream(string $filename = null)
    {
        $filename = $filename ?? $this->filename;
        $pdf = $this->sendHtmlToServerAndGetPdf();

        // check if the pdf is valid
        if (substr($pdf, 0, 4) !== '%PDF') {
            throw new \Exception($pdf);
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        echo $pdf;
        return $this;
    }

    /**
     * Send the html to the server and get the pdf
     * 
     * @return string
     * @access private
     */
    private function sendHtmlToServerAndGetPdf()
    {
        if (config('easy-laravel-pdf.provider') === 'gotenberg') {
            return $this->buildPdfUsingGotenberg();
        }

        $pdfResponse = Http::timeout(60*2)->asJson()->post($this->html_to_pdf_url, [
            'html' => $this->html,
            'url' => $this->url,
            'options' => $this->options,
            'launch_args' => $this->puppeteerLunchArgs
        ]);

        return $pdfResponse->body();
    }

    /**
     * Build the pdf using gotenberg
     * 
     * @return string
     * @access private
     */
    private function buildPdfUsingGotenberg()
    {
        // check if the gotenberg package is installed
        if (!class_exists(Gotenberg::class)) {
            throw new \Exception('Please install the gotenberg package - composer require gotenberg/gotenberg-php');
        }

        $gotenberg = Gotenberg::chromium($this->html_to_pdf_url)->pdf();

        if ($this->options['printBackground'] ?? false) {
            $gotenberg = $gotenberg->printBackground();
        }

        if ($this->options['networkIdleEvent'] ?? false) {
            $gotenberg = $gotenberg->skipNetworkIdleEvent(false);
        }

        if (($this->options['orientation'] ?? false) === 'landscape') {
            $gotenberg = $gotenberg->landscape();
        }

        if ($this->options['margin'] ?? false) {
            $gotenberg = $gotenberg->margins(
                $this->options['margin']['top'] ?? '0',
                $this->options['margin']['bottom'] ?? '0',
                $this->options['margin']['left'] ?? '0',
                $this->options['margin']['right'] ?? '0'
            );
        }

        if ($this->options['waitForExpression'] ?? false) {
            $gotenberg = $gotenberg->waitForExpression($this->options['waitForExpression']);
        }

        if ($this->options['emulatedMediaType'] ?? false) {
            switch ($this->options['emulatedMediaType']) {
                case 'screen':
                    $gotenberg = $gotenberg->emulateScreenMediaType();
                    break;
                case 'print':
                    $gotenberg = $gotenberg->emulatePrintMediaType();
                    break;
            }
        }

        if ($this->options['preferCssPageSize'] ?? false) {
            $gotenberg = $gotenberg->preferCssPageSize();
        }

        if ($this->options['waitDelay'] ?? false) {
            $gotenberg = $gotenberg->waitDelay($this->options['waitDelay']);
        }

        // format is A4
        if ($this->options['format'] ?? false) {
            $gotenberg = $gotenberg->paperSize(...$this->getGotenbergSizeByFormat($this->options['format']));
        }
        
        if (!empty($this->html)) {
            $gotenberg = $gotenberg->html(Stream::string('index.html', $this->html));
        } else {
            $gotenberg = $gotenberg->url($this->url);
        }

        return Gotenberg::send($gotenberg)?->getBody();
    }

    /**
     * Get the size of the pdf
     * 
     * @param string $format The format of the pdf
     * @return array
     * @access private
     */
    private function getGotenbergSizeByFormat($format = 'A4')
    {
        $formats = [
            'Letter' => [8.5, 11],
            'Legal' => [8.5, 14],
            'Tabloid' => [11, 17],
            'Ledger' => [17, 11],
            'A0' => [33.1, 46.8],
            'A1' => [23.4, 33.1],
            'A2' => [16.54, 23.4],
            'A3' => [11.7, 16.54],
            'A4' => [8.27, 11.7],
            'A5' => [5.83, 8.27],
            'A6' => [4.13, 5.83],
        ];

        return $formats[$format] ?? $formats['A4'];
    }
}
