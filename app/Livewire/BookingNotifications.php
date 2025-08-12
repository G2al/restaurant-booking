<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Booking;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Widgets\Widget;

class BookingNotifications extends Widget
{
    protected static string $view = 'livewire.booking-notifications';
    protected static bool $isLazy = false;
    
    public $lastCount = 0;
    
    public function mount()
    {
        $this->lastCount = Booking::where('status', 'confirmed')->count();
    }
    
    public function checkNewBookings()
    {
        $currentCount = Booking::where('status', 'confirmed')->count();
        
        if ($currentCount > $this->lastCount) {
            $newBookings = $currentCount - $this->lastCount;
            
            Notification::make()
                ->title('Nuove prenotazioni!')
                ->body("{$newBookings} nuove prenotazioni ricevute")
                ->success()
                ->actions([
                    Action::make('view')
                        ->label('Visualizza')
                        ->url('/admin/bookings')
                        ->button(),
                ])
                ->persistent() // Rimane finché non cliccata
                ->send();
            
            $this->lastCount = $currentCount;
        }
    }
}