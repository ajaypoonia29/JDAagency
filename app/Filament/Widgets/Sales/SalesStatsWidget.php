<?php

namespace App\Filament\Widgets\Sales;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Quotation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
{
    return [

        Stat::make('Leads', Lead::count())
            ->description('Total Leads')
            ->descriptionIcon('heroicon-m-user-plus')
            ->color('primary'),

        Stat::make('Customers', Customer::count())
            ->description('Active Customers')
            ->descriptionIcon('heroicon-m-building-office')
            ->color('success'),

        Stat::make('Quotations', Quotation::count())
            ->description('Total Quotations')
            ->descriptionIcon('heroicon-m-document-text')
            ->color('warning'),

        Stat::make('Meetings', Meeting::count())
            ->description('Scheduled Meetings')
            ->descriptionIcon('heroicon-m-calendar-days')
            ->color('info'),

    ];
}
}
