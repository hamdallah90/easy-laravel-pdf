<?php

namespace Jouda\EasyLaravelPdf\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jouda\EasyLaravelPdf\EasyLaravelPdf
 */
class EasyLaravelPdf extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Jouda\EasyLaravelPdf\EasyLaravelPdf::class;
    }
}
