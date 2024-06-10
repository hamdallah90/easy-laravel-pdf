<?php

namespace Jouda\EasyLaravelPdf;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class EasyLaravelPdf
{
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
     * constructor
     * 
     * @param array $options The options to pass to the puppeteer
     * @param array $puppeteerLunchArgs The puppeteer launch args
     * @param string $filename The filename of the pdf
     * @access public
     */
    public function __construct(
        protected array $options = [],
        protected array $puppeteerLunchArgs = [],
        protected string $filename = 'filename.pdf',
    ) {
        $this->html_to_pdf_url = config('easy-laravel-pdf.url');

        if (empty($this->html_to_pdf_url)) {
            throw new \Exception('Please set the html_to_pdf_url in the config file');
        }
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
        $this->options = $options;
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
        $pdfResponse = Http::timeout(60*2)->asJson()->post($this->html_to_pdf_url, [
            'html' => $this->html,
            'options' => $this->options,
            'launch_args' => $this->puppeteerLunchArgs
        ]);

        return $pdfResponse->body();
    }
}
