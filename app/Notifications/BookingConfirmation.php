<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmation extends Notification
{
    use Queueable;

    public $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Formatta data e orario in modo pulito
        $dataFormattata = \Carbon\Carbon::parse($this->booking->date)->format('d/m/Y');
        $orarioFormattato = \Carbon\Carbon::parse($this->booking->timeSlot->time)->format('H:i');
        
        $message = (new MailMessage)
                    ->subject('🍕 Prenotazione Confermata - Paninoteca da Luigi')
                    ->greeting('Ciao ' . $this->booking->customer_name . '! 👋')
                    ->line('**La tua prenotazione è stata confermata!**')
                    ->line('')
                    ->line('## 📋 Dettagli Prenotazione')
                    ->line('📅 **Data:** ' . $dataFormattata)
                    ->line('🕒 **Orario:** ' . $orarioFormattato)
                    ->line('👥 **Persone:** ' . $this->booking->guests_count)
                    ->line('📱 **Telefono:** ' . $this->booking->customer_phone);
        
        // Aggiungi richieste speciali solo se presenti
        if (!empty($this->booking->special_requests)) {
            $message->line('')
                    ->line('✨ **Richieste Speciali:**')
                    ->line($this->booking->special_requests);
        }
        
        return $message->line('')
                    ->line('## 📍 Dove trovarci')
                    ->line('**Paninoteca da Luigi**')
                    ->line('📍 Via Roma 123, Napoli')
                    ->line('📞 081-123456')
                    ->line('')
                    ->line('---')
                    ->line('💡 **Ti consigliamo di arrivare puntuale per garantire la tua prenotazione!**')
                    ->line('')
                    ->line('Grazie per averci scelto! Non vediamo l\'ora di accoglierti! 🍴')
                    ->salutation('A presto!')
                    ->salutation('**Team Paninoteca da Luigi**');
    }

    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}