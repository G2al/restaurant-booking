<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public static function sendNotification($message)
    {
        try {
            $token = env('TELEGRAM_BOT_TOKEN');
            $chatId = env('TELEGRAM_CHAT_ID');
            
            if (!$token || !$chatId) {
                Log::error('Telegram: Token o Chat ID mancanti');
                return false;
            }
            
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
            
            if ($response->successful()) {
                Log::info('Telegram: Notifica inviata con successo');
                return true;
            } else {
                Log::error('Telegram: Errore invio', ['response' => $response->body()]);
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error('Telegram: Eccezione', ['error' => $e->getMessage()]);
            return false;
        }
    }
}