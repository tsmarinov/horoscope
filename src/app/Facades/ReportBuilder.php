<?php

namespace App\Facades;

use App\Contracts\HoroscopeSubject;
use App\DataTransfer\NatalReport;
use App\Enums\ReportMode;
use Illuminate\Support\Facades\Facade;

/**
 * @method static NatalReport buildNatalReport(HoroscopeSubject $subject, ReportMode $mode = ReportMode::Organic, string $language = 'en')
 *
 * @see \App\Services\ReportBuilder
 */
class ReportBuilder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\ReportBuilder::class;
    }
}
