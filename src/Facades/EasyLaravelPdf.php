<?php

namespace Jouda\EasyLaravelPdf\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jouda\EasyLaravelPdf\EasyLaravelPdf
 * 
 * @method static \Jouda\EasyLaravelPdf\EasyLaravelPdf setOptions(array $options)
 * @method static \Jouda\EasyLaravelPdf\EasyLaravelPdf addOption(string $key, mixed $value)
 * @method static \Jouda\EasyLaravelPdf\EasyLaravelPdf setPuppeteerLunchArgs(array $puppeteerLunchArgs)
 * @method static \Jouda\EasyLaravelPdf\EasyLaravelPdf loadView(string $view, array $data = [])
 * @method static \Jouda\EasyLaravelPdf\EasyLaravelPdf loadHtml(string $html)
 * @method static \Illuminate\Http\UploadedFile getFile()
 * @method static \Jouda\EasyLaravelPdf\EasyLaravelPdf save(string $path)
 * @method static \Jouda\EasyLaravelPdf\EasyLaravelPdf stream(string $filename)
 */
class EasyLaravelPdf extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Jouda\EasyLaravelPdf\EasyLaravelPdf::class;
    }
}
