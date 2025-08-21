<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.pages.dashboard';
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverviewWidget::class,
            \App\Livewire\BookingNotifications::class,
        ];
    }
    
    public function getTitle(): string
    {
        return 'Dashboard Prenotazioni';
    }
    
    public function getSubheading(): ?string
    {
        return 'Panoramica generale del sistema';
    }
}