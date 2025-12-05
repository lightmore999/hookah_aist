@extends('layouts.app')

@section('title', 'Столы')

@section('content')
<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex gap-2">
            @php
                $yesterday = $selectedDate->copy()->subDay()->format('Y-m-d');
                $today = now()->format('Y-m-d');
                $tomorrow = $selectedDate->copy()->addDay()->format('Y-m-d');
                $currentDate = $selectedDate->format('Y-m-d');
            @endphp
            
            <a href="{{ route('tables.index', ['date' => $yesterday]) }}" 
               class="btn btn-outline-secondary">
                Вчера
            </a>
            <a href="{{ route('tables.index', ['date' => $today]) }}" 
               class="btn {{ $currentDate == $today ? 'btn-secondary' : 'btn-outline-secondary' }}">
                Сегодня
            </a>
            <a href="{{ route('tables.index', ['date' => $tomorrow]) }}" 
               class="btn btn-outline-secondary">
                Завтра
            </a>
        </div>
        
        <div class="flex-grow-1 text-center">
            <form method="GET" action="{{ route('tables.index') }}" class="d-inline-block">
                <input type="date" 
                       name="date" 
                       value="{{ $currentDate }}" 
                       class="form-control d-inline-block" 
                       style="width: auto;"
                       onchange="this.form.submit()">
            </form>
        </div>
        
        <div>
            <a href="{{ route('tables.create', ['date' => $currentDate]) }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Добавить стол
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 80vh; overflow-y: auto;">
                <table class="table table-bordered mb-0" style="table-layout: fixed;">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width: 120px;" class="text-center">Время</th>
                            @foreach($tableNumbers as $tableNum)
                                <th class="text-center">{{ $tableNum }}</th>
                            @endforeach
                            <th style="width: 120px;" class="text-center">Время</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $times = [];
                            $start = \Carbon\Carbon::createFromTime(14, 0);
                            $end = \Carbon\Carbon::createFromTime(3, 30);
                            
                            $current = $start->copy();
                            $day = 0;
                            while ($day < 2) {
                                $times[] = $current->copy();
                                $current->addMinutes(30);
                                
                                if ($current->format('H:i') == '00:00') {
                                    $day++;
                                }
                                if ($day == 1 && $current->format('H:i') == '03:30') {
                                    break;
                                }
                            }
                        @endphp
                        
                        @php
                            $renderedCells = [];
                        @endphp
                        
                        @foreach($times as $timeIndex => $time)
                            @php
                                $timeStr = $time->format('H:i');
                            @endphp
                            <tr>
                                <td class="text-center fw-bold bg-light" style="position: sticky; left: 0; z-index: 10;">
                                    {{ $timeStr }}
                                </td>
                                
                                @foreach($tableNumbers as $tableNum)
                                    @php
                                        $cellKey = $timeIndex . '_' . $tableNum;
                                        if (isset($renderedCells[$cellKey])) {
                                            continue;
                                        }
                                        
                                        $tableBookings = $tables[$tableNum] ?? [];
                                        $currentBooking = null;
                                        $isStart = false;
                                        $durationSlots = 0;
                                        
                                        foreach($tableBookings as $booking) {
                                            $bookingTimeStr = is_string($booking->booking_time) ? $booking->booking_time : (is_object($booking->booking_time) ? $booking->booking_time->format('H:i:s') : '00:00:00');
                                            $bookingTime = \Carbon\Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . substr($bookingTimeStr, 0, 8));
                                            $bookingEnd = $bookingTime->copy()->addMinutes($booking->duration);
                                            
                                            $timeHour = (int)substr($timeStr, 0, 2);
                                            $timeMin = (int)substr($timeStr, 3, 2);
                                            
                                            if ($timeHour < 4) {
                                                $timeCarbon = \Carbon\Carbon::parse($selectedDate->format('Y-m-d') . ' ' . $timeStr)->addDay();
                                            } else {
                                                $timeCarbon = \Carbon\Carbon::parse($selectedDate->format('Y-m-d') . ' ' . $timeStr);
                                            }
                                            
                                            if ($timeCarbon->gte($bookingTime) && $timeCarbon->lt($bookingEnd)) {
                                                if ($bookingTime->format('H:i') == $timeStr) {
                                                    $currentBooking = $booking;
                                                    $isStart = true;
                                                    $durationSlots = max(1, ceil($booking->duration / 30));
                                                    
                                                    for ($i = 0; $i < $durationSlots; $i++) {
                                                        $renderedCells[($timeIndex + $i) . '_' . $tableNum] = true;
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp
                                    
                                    @if($isStart && $currentBooking)
                                        <td rowspan="{{ $durationSlots }}" class="p-2 align-top" style="background-color: #e3f2fd; border: 2px solid #2196f3; vertical-align: top;">
                                            <div class="small">
                                                <strong>{{ $currentBooking->guest_name ?? 'Без имени' }}</strong><br>
                                                @if($currentBooking->phone)
                                                    <i class="bi bi-telephone"></i> {{ $currentBooking->phone }}<br>
                                                @endif
                                                @if($currentBooking->guests_count)
                                                    <i class="bi bi-people"></i> {{ $currentBooking->guests_count }} чел.<br>
                                                @endif
                                                @if($currentBooking->comment)
                                                    <small class="text-muted">{{ Str::limit($currentBooking->comment, 30) }}</small><br>
                                                @endif
                                                <a href="{{ route('tables.edit', $currentBooking) }}" 
                                                   class="btn btn-sm btn-outline-warning mt-1">
                                                    <i class="bi bi-pencil"></i> Редактировать
                                                </a>
                                            </div>
                                        </td>
                                    @else
                                        <td class="p-1" style="min-height: 50px;"></td>
                                    @endif
                                @endforeach
                                
                                <td class="text-center fw-bold bg-light" style="position: sticky; right: 0; z-index: 10;">
                                    {{ $timeStr }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .table td {
        min-height: 50px;
        height: 50px;
    }
</style>
@endsection

