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

class BookingController extends Controller
{
    public function availableDates()
    {
        // Generiamo le prossime 30 giorni
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
        
        // Ottieni il giorno della settimana
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        
        // Trova tavoli con capacità ESATTA E aperti in questo giorno
        $exactTables = Table::where('is_active', true)
            ->where('capacity', $guests)
            ->get()
            ->filter(function ($table) use ($dayOfWeek) {
                return $table->isOpenOnDay($dayOfWeek);
            })
            ->pluck('id');
            
        if ($exactTables->isEmpty()) {
            return response()->json([]);
        }
        
        // Trova tutti gli slot attivi
       // Trova gli slot attivi NON disabilitati per i tavoli con capacità esatta
        $allSlots = TimeSlot::where('is_active', true)
            ->whereHas('tables', function ($query) use ($guests, $dayOfWeek) {
                $query->where('is_active', true)
                    ->where('capacity', $guests)
                    ->whereRaw('JSON_CONTAINS(opening_days, ?)', ['"'.$dayOfWeek.'"'])
                    ->where('table_time_slots.is_disabled', false);
            })
            ->orderBy('time')
            ->get();

        // NUOVO: Controllo orari passati se è oggi
        $availableSlots = collect();
        $now = Carbon::now();
        $selectedDate = Carbon::parse($date);
        $isToday = $selectedDate->isToday();
        
        foreach ($allSlots as $slot) {
            // SE È OGGI, salta orari passati
            if ($isToday) {
                $slotDateTime = Carbon::today()->setTimeFromTimeString($slot->time);
                if ($slotDateTime->isPast()) {
                    continue; // Salta questo slot
                }
            }
            
            // Resto della logica rimane uguale
            $availableExactTable = Table::where('is_active', true)
                ->where('capacity', $guests)
                ->get()
                ->filter(function ($table) use ($dayOfWeek) {
                    return $table->isOpenOnDay($dayOfWeek);
                })
                ->whereNotIn('id', Booking::where('date', $date)
                    ->where('time_slot_id', $slot->id)
                    ->where('status', 'confirmed')
                    ->pluck('table_id'))
                ->isNotEmpty();
                
            if ($availableExactTable) {
                $availableSlots->push([
                    'id' => $slot->id,
                    'time' => $slot->time->format('H:i'),
                    'formatted' => $slot->time->format('H:i')
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
        
        // Trova il primo tavolo disponibile con capacità ESATTA e orario NON disabilitato
        $availableTable = Table::where('is_active', true)
            ->where('capacity', $guests)
            ->whereHas('timeSlots', function ($query) use ($timeSlotId) {
                $query->where('time_slot_id', $timeSlotId)
                    ->where('table_time_slots.is_disabled', false);
            })
            ->whereNotIn('id', Booking::where('date', $date)
                ->where('time_slot_id', $timeSlotId)
                ->where('status', 'confirmed')
                ->pluck('table_id'))
            ->orderBy('capacity')
            ->first();

        if (!$availableTable) {
            return response()->json(['error' => 'Spiacenti, questo orario è stato appena prenotato da qualcun altro. Scegli un altro orario.'], 400);
        }
        
        // Crea la prenotazione
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
        
        // Invia email di conferma
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
        
        // Trova le capacità esatte dei tavoli aperti in questo giorno che hanno almeno uno slot libero
        $availableCapacities = collect();
        
        // Ottieni solo i tavoli attivi E aperti in questo giorno
        $activeTables = Table::where('is_active', true)
            ->get()
            ->filter(function ($table) use ($dayOfWeek) {
                return $table->isOpenOnDay($dayOfWeek);
            });
        
        foreach ($activeTables as $table) {
            // Controlla se questo tavolo ha almeno uno slot libero E non disabilitato
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
                
            if ($hasAvailableSlot) {
                $availableCapacities->push($table->capacity);
            }
        }
        
        // Rimuovi duplicati e ordina
        $uniqueCapacities = $availableCapacities->unique()->sort()->values();
        
        return response()->json($uniqueCapacities);
    }
}