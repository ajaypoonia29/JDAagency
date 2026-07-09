<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Sales\SalesHeroWidget;
use App\Filament\Widgets\Sales\SalesStatsWidget;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class SalesWorkspace extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationLabel = 'Sales Workspace';

    protected static ?string $title = 'Sales Workspace';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.sales-workspace';

    public function getHeaderWidgets(): array
{
    return [
        SalesHeroWidget::class,
        SalesStatsWidget::class,
    ];
}
}