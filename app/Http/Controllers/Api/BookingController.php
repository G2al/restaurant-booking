<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\TimeSlot;
use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Notifications\BookingConfirmation;
use Illuminate\Support\Facades\Notification;
use App\Services\TelegramService;

class BookingController extends Controller
{
    public function availableDates()
    {
        // Generiamo i prossimi 30 giorni
        $dates = collect();
        
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->addDays($i)->format('Y-m-d');
            
            // Controlliamo se c'è almeno un tavolo libero in questa data
            $hasAvailableTable = $this->hasAvailableTableForDate($date);
            
            if ($hasAvailableTable) {
                $dates->push([
                    'date' => $date,
                    'formatted' => Carbon::parse($date)->format('d/m/Y'),
                    'day_name' => Carbon::parse($date)->locale('it')->dayName
                ]);
            }
        }
        
        return response()->json($dates);
    }
    
    private function hasAvailableTableForDate($date)
    {
        $totalSlots = TimeSlot::where('is_active', true)->count();
        $activeTables = Table::where('is_active', true)->count();
        
        // Slot totali possibili per questa data
        $totalPossibleBookings = $totalSlots * $activeTables;
        
        // Prenotazioni già esistenti per questa data
        $existingBookings = Booking::where('date', $date)
            ->where('status', 'confirmed')
            ->count();
        
        // Se ci sono ancora slot liberi
        return $existingBookings < $totalPossibleBookings;
    }

    public function availableTimes(Request $request)
    {
        $date = $request->get('date');
        $guests = (int) $request->get('guests');
        
        if (!$date || !$guests) {
            return response()->json(['error' => 'Data e numero ospiti richiesti'], 400);
        }
        
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $selectedDate = Carbon::parse($date);
        $isToday = $selectedDate->isToday();

        // 🔹 Trova tavoli attivi e aperti quel giorno
        $candidateTables = Table::where('is_active', true)
            ->get()
            ->filter(function ($table) use ($dayOfWeek, $guests) {
                if (!$table->isOpenOnDay($dayOfWeek)) {
                    return false;
                }

                // Tavoli modulari (capacities)
                return !empty($table->capacities) && in_array($guests, $table->capacities);
            });

        if ($candidateTables->isEmpty()) {
            return response()->json([]);
        }

        // 🔹 Trova tutti gli slot attivi compatibili
        $allSlots = TimeSlot::where('is_active', true)
            ->whereHas('tables', function ($query) use ($candidateTables, $dayOfWeek) {
                $query->whereIn('table_id', $candidateTables->pluck('id'))
                    ->where('table_time_slots.is_disabled', false)
                    ->whereRaw('JSON_CONTAINS(opening_days, ?)', ['"'.$dayOfWeek.'"']);
            })
            ->orderBy('time')
            ->get();

        $availableSlots = collect();

        foreach ($allSlots as $slot) {
            // 🔹 Se è oggi, salta orari già passati
            if ($isToday) {
                $slotDateTime = Carbon::today()->setTimeFromTimeString($slot->time);
                if ($slotDateTime->isPast()) {
                    continue;
                }
            }

            // 🔹 Verifica che ci sia almeno un tavolo libero per questo slot
            $availableTable = $candidateTables->filter(function ($table) use ($date, $slot) {
                return !Booking::where('date', $date)
                    ->where('time_slot_id', $slot->id)
                    ->where('status', 'confirmed')
                    ->where('table_id', $table->id)
                    ->exists();
            })->isNotEmpty();

            if ($availableTable) {
                $availableSlots->push([
                    'id' => $slot->id,
                    'time' => $slot->time->format('H:i'),
                    'formatted' => $slot->time->format('H:i'),
                ]);
            }
        }

        return response()->json($availableSlots);
    }


    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'time_slot_id' => 'required|exists:time_slots,id',
            'guests' => 'required|integer|min:1',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string',
            'special_requests' => 'nullable|string|max:1000'
        ]);
        
        $date = $request->date;
        $timeSlotId = $request->time_slot_id;
        $guests = $request->guests;
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // 🔹 Trova tavoli candidati aperti e compatibili con numero ospiti
        $candidateTables = Table::where('is_active', true)
            ->get()
            ->filter(function ($table) use ($dayOfWeek, $guests) {
                if (!$table->isOpenOnDay($dayOfWeek)) {
                    return false;
                }

                return !empty($table->capacities) && in_array($guests, $table->capacities);
            });

        if ($candidateTables->isEmpty()) {
            return response()->json(['error' => 'Nessun tavolo disponibile per questo numero di ospiti.'], 400);
        }

        // 🔹 Cerca un tavolo libero per quello slot
        $availableTable = $candidateTables->filter(function ($table) use ($timeSlotId, $date) {
            $hasSlotEnabled = $table->timeSlots()
                ->where('time_slot_id', $timeSlotId)
                ->where('table_time_slots.is_disabled', false)
                ->exists();

            if (!$hasSlotEnabled) {
                return false;
            }

            $alreadyBooked = Booking::where('date', $date)
                ->where('time_slot_id', $timeSlotId)
                ->where('status', 'confirmed')
                ->where('table_id', $table->id)
                ->exists();

            return !$alreadyBooked;
        })->first();

        if (!$availableTable) {
            return response()->json(['error' => 'Spiacenti, questo orario è stato appena prenotato da qualcun altro.'], 400);
        }

        // 🔹 Crea la prenotazione
        $booking = Booking::create([
            'table_id' => $availableTable->id,
            'date' => $date,
            'time_slot_id' => $timeSlotId,
            'guests_count' => $guests,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'special_requests' => $request->special_requests,
            'status' => 'confirmed'
        ]);

        // 🔹 Invia notifica Telegram
        $dataFormatted = Carbon::parse($booking->date)->locale('it')->isoFormat('DD/MM - dddd');
        $oraFormatted = Carbon::parse($booking->timeSlot->time)->format('H:i');

        $message = "🍽️ <b>NUOVA PRENOTAZIONE!</b>\n\n" .
                "👤 <b>Cliente:</b> {$booking->customer_name}\n" .
                "📞 <b>Telefono:</b> {$booking->customer_phone}\n" .
                "🪑 <b>Tavolo:</b> {$availableTable->name}\n" .
                "📅 <b>Data:</b> {$dataFormatted}\n" .
                "⏰ <b>Ora:</b> {$oraFormatted}\n" .
                "👥 <b>Ospiti:</b> {$booking->guests_count}";

        TelegramService::sendNotification($message);

        // 🔹 Invia email di conferma
        Notification::route('mail', $request->customer_email)
            ->notify(new BookingConfirmation($booking));

        return response()->json([
            'success' => true,
            'booking_id' => $booking->id,
            'table' => $availableTable->name,
            'message' => 'Prenotazione confermata!'
        ]);
    }


    public function availableCapacities(Request $request)
    {
        $date = $request->get('date');
        
        if (!$date) {
            return response()->json(['error' => 'Data richiesta'], 400);
        }
        
        // Ottieni il giorno della settimana (0=domenica, 1=lunedì, etc.)
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        
        $availableCapacities = collect();
        
        // Ottieni solo i tavoli attivi e aperti in questo giorno
        $activeTables = Table::where('is_active', true)
            ->get()
            ->filter(function ($table) use ($dayOfWeek) {
                return $table->isOpenOnDay($dayOfWeek);
            });
        
        foreach ($activeTables as $table) {
            // Controlla se questo tavolo ha almeno uno slot libero NON disabilitato
            $hasAvailableSlot = TimeSlot::where('is_active', true)
                ->whereHas('tables', function ($query) use ($table) {
                    $query->where('table_id', $table->id)
                        ->where('table_time_slots.is_disabled', false);
                })
                ->whereNotExists(function ($query) use ($date, $table) {
                    $query->select(DB::raw(1))
                        ->from('bookings')
                        ->whereRaw('bookings.table_id = ?', [$table->id])
                        ->whereRaw('bookings.date = ?', [$date])
                        ->whereRaw('bookings.time_slot_id = time_slots.id')
                        ->where('bookings.status', 'confirmed');
                })
                ->exists();
                
            if ($hasAvailableSlot && !empty($table->capacities) && is_array($table->capacities)) {
                foreach ($table->capacities as $cap) {
                    $availableCapacities->push((int) $cap);
                }
            }
        }
        
        // Rimuovi duplicati e ordina
        $uniqueCapacities = $availableCapacities->unique()->sort()->values();
        
        return response()->json($uniqueCapacities);
    }
}
