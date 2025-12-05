<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $selectedDate = Carbon::parse($date);
        
        $tables = Table::whereDate('booking_date', $selectedDate)
            ->orderBy('booking_time')
            ->get()
            ->groupBy('table_number');

        $tableNumbers = ['1', '2', '3', '4', 'Барная стойка', '6', '7'];
        
        return view('tables.index', compact('tables', 'tableNumbers', 'selectedDate'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $clients = Client::orderBy('name')->get();
        $date = $request->get('date', date('Y-m-d'));
        $tableNumber = $request->get('table_number');
        $time = $request->get('time');
        
        return view('tables.create', compact('clients', 'date', 'tableNumber', 'time'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required|string|max:255',
            'booking_date' => 'required|date',
            'booking_time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:30',
            'guests_count' => 'required|integer|min:1',
            'comment' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'guest_name' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'status' => 'nullable|string|max:255',
        ]);

        $bookingTime = Carbon::parse($validated['booking_date'] . ' ' . $validated['booking_time']);
        $endTime = $bookingTime->copy()->addMinutes($validated['duration']);

        $overlapping = Table::where('table_number', $validated['table_number'])
            ->whereDate('booking_date', $validated['booking_date'])
            ->get()
            ->filter(function($existing) use ($bookingTime, $endTime) {
                $bookingTimeStr = is_string($existing->booking_time) ? $existing->booking_time : (is_object($existing->booking_time) ? $existing->booking_time->format('H:i:s') : '00:00:00');
                $existingStart = Carbon::parse($existing->booking_date->format('Y-m-d') . ' ' . $bookingTimeStr);
                $existingEnd = $existingStart->copy()->addMinutes($existing->duration);
                
                return ($bookingTime->lt($existingEnd) && $endTime->gt($existingStart));
            })
            ->isNotEmpty();

        if ($overlapping) {
            return back()->withInput()
                ->with('error', 'Этот стол уже занят в указанное время!');
        }

        Table::create($validated);

        return redirect()->route('tables.index', ['date' => $validated['booking_date']])
            ->with('success', 'Стол успешно забронирован!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Table $table)
    {
        $clients = Client::orderBy('name')->get();
        return view('tables.edit', compact('table', 'clients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Table $table)
    {
        $validated = $request->validate([
            'table_number' => 'required|string|max:255',
            'booking_date' => 'required|date',
            'booking_time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:30',
            'guests_count' => 'required|integer|min:1',
            'comment' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'guest_name' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'status' => 'nullable|string|max:255',
        ]);

        $bookingTime = Carbon::parse($validated['booking_date'] . ' ' . $validated['booking_time']);
        $endTime = $bookingTime->copy()->addMinutes($validated['duration']);

        $overlapping = Table::where('table_number', $validated['table_number'])
            ->whereDate('booking_date', $validated['booking_date'])
            ->where('id', '!=', $table->id)
            ->get()
            ->filter(function($existing) use ($bookingTime, $endTime) {
                $bookingTimeStr = is_string($existing->booking_time) ? $existing->booking_time : (is_object($existing->booking_time) ? $existing->booking_time->format('H:i:s') : '00:00:00');
                $existingStart = Carbon::parse($existing->booking_date->format('Y-m-d') . ' ' . $bookingTimeStr);
                $existingEnd = $existingStart->copy()->addMinutes($existing->duration);
                
                return ($bookingTime->lt($existingEnd) && $endTime->gt($existingStart));
            })
            ->isNotEmpty();

        if ($overlapping) {
            return back()->withInput()
                ->with('error', 'Этот стол уже занят в указанное время!');
        }

        $table->update($validated);

        return redirect()->route('tables.index', ['date' => $validated['booking_date']])
            ->with('success', 'Бронирование стола успешно обновлено!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Table $table)
    {
        $date = $table->booking_date->format('Y-m-d');
        $table->delete();

        return redirect()->route('tables.index', ['date' => $date])
            ->with('success', 'Бронирование стола успешно удалено!');
    }
}
