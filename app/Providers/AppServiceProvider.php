<?php

declare(strict_types=1);

namespace App\Providers;

use App\Database\CsvUserImportationConverter;
use App\Database\UserImportationConverterInterface;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public array $bindings = [
        UserImportationConverterInterface::class => CsvUserImportationConverter::class,
    ];
}
