<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Table;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Calcola le statistiche
        $todayBookings = Booking::whereDate('date', Carbon::today())->count();
        $monthBookings = Booking::whereMonth('date', Carbon::now()->month)->count();
        $activeTables = Table::where('is_active', true)->count();
        
        // Calcola tasso occupazione oggi (esempio)
        $occupancyRate = $activeTables > 0 ? round(($todayBookings / $activeTables) * 100) : 0;
        
        return [
            Stat::make('Prenotazioni Oggi', $todayBookings)
                ->description('Prenotazioni confermate')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
                
            Stat::make('Prenotazioni Mese', $monthBookings)
                ->description('Totale mensile')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
                
            Stat::make('Tavoli Attivi', $activeTables)
                ->description('Tavoli disponibili')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('warning'),
                
            Stat::make('Occupazione', $occupancyRate . '%')
                ->description('Tasso occupazione oggi')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($occupancyRate > 80 ? 'danger' : 'success'),
        ];
    }
}