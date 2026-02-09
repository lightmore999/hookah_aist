<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Expenditure;  
use App\Models\Fine;  
use Illuminate\Support\Facades\Schema;  
use App\Models\Hookah;  
use App\Models\Product;
use App\Models\ProductCategory;

class StatisticsController extends Controller
{
    public function index()
    {
        return view('statistics.index');
    }

    public function accounting()
    {
        return view('statistics.accounting');
    }

    public function hookahPage()
    {
        return view('statistics.hookah');
    }

    public function productsPage()
    {
        return view('statistics.products');
    }
    
    public function expensesPage()
    {
        return view('statistics.expenses');
    }
    
    /**
     * 1. Динамика посещений - ВСЯ история с группировкой по периодам
     * Период: day (по дням), week (по неделям), month (по месяцам)
     * НЕ ограничиваем по времени - берем ВСЕ данные
     * ВАЖНО: показываем все дни, включая пустые!
     */
    public function visitDynamics(Request $request)
    {
        try {
            $period = $request->input('period', 'month');
            
            if ($period === 'day') {
                // ПО ДНЯМ: показываем все дни в диапазоне
                
                // Получаем первую и последнюю дату бронирования
                $dateRange = Table::selectRaw('MIN(booking_date) as min_date, MAX(booking_date) as max_date')->first();
                
                if (!$dateRange->min_date || !$dateRange->max_date) {
                    return response()->json([
                        'success' => true,
                        'labels' => [],
                        'tables_data' => [],
                        'guests_data' => [],
                        'total_tables' => 0,
                        'total_guests' => 0,
                        'avg_guests_per_table' => 0,
                        'date_range' => [
                            'min' => null,
                            'max' => null
                        ]
                    ]);
                }
                
                $start = Carbon::parse($dateRange->min_date)->startOfDay();
                $end = Carbon::parse($dateRange->max_date)->endOfDay();
                
                // Создаем массив всех дней в диапазоне
                $allDays = [];
                $currentDate = $start->copy();
                
                while ($currentDate <= $end) {
                    $allDays[$currentDate->format('Y-m-d')] = [
                        'date' => $currentDate->copy(),
                        'tables_count' => 0,
                        'guests_count' => 0
                    ];
                    $currentDate->addDay();
                }
                
                // Получаем данные по бронированиям
                $bookings = Table::selectRaw('booking_date, COUNT(*) as tables_count, SUM(guests_count) as guests_count')
                    ->whereBetween('booking_date', [$start, $end])
                    ->groupBy('booking_date')
                    ->get()
                    ->keyBy(function($item) {
                        return Carbon::parse($item->booking_date)->format('Y-m-d');
                    });
                
                // Заполняем данные
                foreach ($allDays as $dateStr => $dayData) {
                    if ($bookings->has($dateStr)) {
                        $booking = $bookings[$dateStr];
                        $allDays[$dateStr]['tables_count'] = (int)$booking->tables_count;
                        $allDays[$dateStr]['guests_count'] = (int)$booking->guests_count;
                    }
                }
                
                // Формируем ответ
                $labels = [];
                $tablesData = [];
                $guestsData = [];
                
                foreach ($allDays as $dateStr => $dayData) {
                    $labels[] = $dayData['date']->format('d.m.Y');
                    $tablesData[] = $dayData['tables_count'];
                    $guestsData[] = $dayData['guests_count'];
                }
                
                return response()->json([
                    'success' => true,
                    'labels' => $labels,
                    'tables_data' => $tablesData,
                    'guests_data' => $guestsData,
                    'total_tables' => array_sum($tablesData),
                    'total_guests' => array_sum($guestsData),
                    'avg_guests_per_table' => array_sum($tablesData) > 0 
                        ? round(array_sum($guestsData) / array_sum($tablesData), 1) 
                        : 0,
                    'date_range' => [
                        'min' => $start->format('Y-m-d'),
                        'max' => $end->format('Y-m-d')
                    ]
                ]);
                
            } 
            elseif ($period === 'week') {
                // ПО НЕДЕЛЯМ: показываем все недели в диапазоне
                
                // Получаем первую и последнюю дату бронирования
                $dateRange = Table::selectRaw('MIN(booking_date) as min_date, MAX(booking_date) as max_date')->first();
                
                if (!$dateRange->min_date || !$dateRange->max_date) {
                    return response()->json([
                        'success' => true,
                        'labels' => [],
                        'tables_data' => [],
                        'guests_data' => [],
                        'total_tables' => 0,
                        'total_guests' => 0,
                        'avg_guests_per_table' => 0,
                        'date_range' => [
                            'min' => null,
                            'max' => null
                        ]
                    ]);
                }
                
                $start = Carbon::parse($dateRange->min_date)->startOfWeek(); // Начало недели с бронированием
                $end = Carbon::parse($dateRange->max_date)->endOfWeek(); // Конец недели с бронированием
                
                // Создаем массив всех недель в диапазоне
                $allWeeks = [];
                $currentWeek = $start->copy();
                
                while ($currentWeek <= $end) {
                    $weekKey = $currentWeek->format('Y-W');
                    $allWeeks[$weekKey] = [
                        'start_date' => $currentWeek->copy(),
                        'end_date' => $currentWeek->copy()->endOfWeek(),
                        'tables_count' => 0,
                        'guests_count' => 0
                    ];
                    $currentWeek->addWeek();
                }
                
                // Получаем данные по бронированиям с группировкой по неделям
                $bookings = Table::selectRaw('EXTRACT(YEAR FROM booking_date) as year, EXTRACT(WEEK FROM booking_date) as week_number')
                    ->selectRaw('COUNT(*) as tables_count, SUM(guests_count) as guests_count')
                    ->whereBetween('booking_date', [$start, $end])
                    ->groupBy('year', 'week_number')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->week_number, 2, '0', STR_PAD_LEFT);
                    });
                
                // Заполняем данные
                foreach ($allWeeks as $weekKey => $weekData) {
                    if ($bookings->has($weekKey)) {
                        $booking = $bookings[$weekKey];
                        $allWeeks[$weekKey]['tables_count'] = (int)$booking->tables_count;
                        $allWeeks[$weekKey]['guests_count'] = (int)$booking->guests_count;
                    }
                }
                
                // Формируем ответ
                $labels = [];
                $tablesData = [];
                $guestsData = [];
                
                foreach ($allWeeks as $weekKey => $weekData) {
                    $startDate = $weekData['start_date'];
                    $endDate = $weekData['end_date'];
                    $labels[] = $startDate->format('d.m') . '-' . $endDate->format('d.m');
                    $tablesData[] = $weekData['tables_count'];
                    $guestsData[] = $weekData['guests_count'];
                }
                
                return response()->json([
                    'success' => true,
                    'labels' => $labels,
                    'tables_data' => $tablesData,
                    'guests_data' => $guestsData,
                    'total_tables' => array_sum($tablesData),
                    'total_guests' => array_sum($guestsData),
                    'avg_guests_per_table' => array_sum($tablesData) > 0 
                        ? round(array_sum($guestsData) / array_sum($tablesData), 1) 
                        : 0,
                    'date_range' => [
                        'min' => $start->format('Y-m-d'),
                        'max' => $end->format('Y-m-d')
                    ]
                ]);
                
            }
            else { // month
                // ПО МЕСЯЦАМ: показываем все месяцы в диапазоне
                
                // Получаем первую и последнюю дату бронирования
                $dateRange = Table::selectRaw('MIN(booking_date) as min_date, MAX(booking_date) as max_date')->first();
                
                if (!$dateRange->min_date || !$dateRange->max_date) {
                    return response()->json([
                        'success' => true,
                        'labels' => [],
                        'tables_data' => [],
                        'guests_data' => [],
                        'total_tables' => 0,
                        'total_guests' => 0,
                        'avg_guests_per_table' => 0,
                        'date_range' => [
                            'min' => null,
                            'max' => null
                        ]
                    ]);
                }
                
                $start = Carbon::parse($dateRange->min_date)->startOfMonth();
                $end = Carbon::parse($dateRange->max_date)->endOfMonth();
                
                // Создаем массив всех месяцев в диапазоне
                $allMonths = [];
                $currentMonth = $start->copy();
                
                while ($currentMonth <= $end) {
                    $monthKey = $currentMonth->format('Y-m');
                    $allMonths[$monthKey] = [
                        'month' => $currentMonth->copy(),
                        'tables_count' => 0,
                        'guests_count' => 0
                    ];
                    $currentMonth->addMonth();
                }
                
                // Получаем данные по бронированиям с группировкой по месяцам
                $bookings = Table::selectRaw('EXTRACT(YEAR FROM booking_date) as year, EXTRACT(MONTH FROM booking_date) as month')
                    ->selectRaw('COUNT(*) as tables_count, SUM(guests_count) as guests_count')
                    ->whereBetween('booking_date', [$start, $end])
                    ->groupBy('year', 'month')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                    });
                
                // Заполняем данные
                foreach ($allMonths as $monthKey => $monthData) {
                    if ($bookings->has($monthKey)) {
                        $booking = $bookings[$monthKey];
                        $allMonths[$monthKey]['tables_count'] = (int)$booking->tables_count;
                        $allMonths[$monthKey]['guests_count'] = (int)$booking->guests_count;
                    }
                }
                
                // Формируем ответ
                $labels = [];
                $tablesData = [];
                $guestsData = [];
                
                $monthNames = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 
                            'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
                
                foreach ($allMonths as $monthKey => $monthData) {
                    $monthDate = $monthData['month'];
                    $labels[] = $monthNames[(int)$monthDate->format('n') - 1] . ' ' . $monthDate->format('Y');
                    $tablesData[] = $monthData['tables_count'];
                    $guestsData[] = $monthData['guests_count'];
                }
                
                return response()->json([
                    'success' => true,
                    'labels' => $labels,
                    'tables_data' => $tablesData,
                    'guests_data' => $guestsData,
                    'total_tables' => array_sum($tablesData),
                    'total_guests' => array_sum($guestsData),
                    'avg_guests_per_table' => array_sum($tablesData) > 0 
                        ? round(array_sum($guestsData) / array_sum($tablesData), 1) 
                        : 0,
                    'date_range' => [
                        'min' => $start->format('Y-m-d'),
                        'max' => $end->format('Y-m-d')
                    ]
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 2. Популярные столы - за период (неделя/месяц/год) с переключением
     */
    public function popularTables(Request $request)
    {
        try {
            $period = $request->input('period', 'month');
            $offset = (int)$request->input('offset', 0);
            
            // Получаем диапазон дат на основе периода и смещения
            $dateRange = $this->getPeriodRange($period, $offset);
            
            // Используем join с table_names чтобы получить названия столов
            $data = Table::join('table_names', 'table_bookings.table_name_id', '=', 'table_names.id')
                ->whereBetween('table_bookings.booking_date', [$dateRange['start'], $dateRange['end']])
                ->select(
                    'table_names.name as table_name',
                    'table_names.id as table_id',
                    DB::raw('COUNT(table_bookings.id) as visits_count')
                )
                ->groupBy('table_names.id', 'table_names.name')
                ->orderByDesc('visits_count')
                ->limit(10)
                ->get();
            
            // Формируем метки для графика
            $labels = $data->pluck('table_name');
            
            return response()->json([
                'success' => true,
                'labels' => $labels,
                'visits_data' => $data->pluck('visits_count')->map(fn($v) => (int)$v),
                'total' => (int)$data->sum('visits_count'),
                'period' => $period,
                'current_range' => [
                    'start' => $dateRange['start']->format('Y-m-d'),
                    'end' => $dateRange['end']->format('Y-m-d'),
                    'label' => $dateRange['label']
                ],
                'offset' => $offset,
                'debug' => config('app.debug') ? [
                    'data_count' => $data->count(),
                    'first_item' => $data->first(),
                    'date_range' => [
                        'start' => $dateRange['start']->format('Y-m-d H:i:s'),
                        'end' => $dateRange['end']->format('Y-m-d H:i:s')
                    ]
                ] : null
            ]);
            
        } catch (\Exception $e) {
            // Добавляем логирование для отладки
            \Log::error('Error in popularTables method: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при получении статистики по столам: ' . $e->getMessage(),
                'debug' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ] : null
            ], 500);
        }
    }
    
    /**
     * 3. Популярное время - за период (неделя/месяц/год) с переключением
     */
    public function popularHours(Request $request)
    {
        try {
            $period = $request->input('period', 'month');
            $offset = (int)$request->input('offset', 0);
            
            // Получаем диапазон дат на основе периода и смещения
            $dateRange = $this->getPeriodRange($period, $offset);
            
            $query = Table::whereBetween('booking_date', [$dateRange['start'], $dateRange['end']]);
            
            $data = $query->select(DB::raw('EXTRACT(HOUR FROM booking_time) as hour'), DB::raw('COUNT(*) as count'))
                ->groupBy(DB::raw('EXTRACT(HOUR FROM booking_time)'))
                ->orderBy('hour')
                ->get();
            
            // Заполняем все 24 часа
            $allHours = [];
            for ($i = 0; $i < 24; $i++) {
                $found = $data->firstWhere('hour', $i);
                $allHours[$i] = $found ? (int)$found->count : 0;
            }
            
            // Находим пиковый час
            $peakHour = array_search(max($allHours), $allHours);
            
            return response()->json([
                'success' => true,
                'labels' => array_map(fn($h) => sprintf('%02d:00', $h), array_keys($allHours)),
                'tables_data' => array_values($allHours),
                'peak_hour' => sprintf('%02d:00', $peakHour),
                'period' => $period,
                'current_range' => [
                    'start' => $dateRange['start']->format('Y-m-d'),
                    'end' => $dateRange['end']->format('Y-m-d'),
                    'label' => $dateRange['label']
                ],
                'offset' => $offset
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 4. Дни недели - за период (неделя/месяц/год) с переключением
     */
    public function popularWeekdays(Request $request)
    {
        try {
            $period = $request->input('period', 'month');
            $offset = (int)$request->input('offset', 0);
            
            // Получаем диапазон дат на основе периода и смещения
            $dateRange = $this->getPeriodRange($period, $offset);
            
            $query = Table::whereBetween('booking_date', [$dateRange['start'], $dateRange['end']]);
            
            $data = $query->select(
                    DB::raw('EXTRACT(DOW FROM booking_date) + 1 as weekday'), 
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy(DB::raw('EXTRACT(DOW FROM booking_date)'))
                ->orderBy('weekday')
                ->get();
            
            $weekdayNames = [
                1 => 'Вс', 2 => 'Пн', 3 => 'Вт', 4 => 'Ср',
                5 => 'Чт', 6 => 'Пт', 7 => 'Сб'
            ];
            
            // Заполняем все дни
            $allDays = [];
            for ($i = 1; $i <= 7; $i++) {
                $found = $data->firstWhere('weekday', $i);
                $allDays[$weekdayNames[$i]] = $found ? (int)$found->count : 0;
            }
            
            // Находим самый популярный день
            arsort($allDays);
            $mostPopularDay = key($allDays);
            
            return response()->json([
                'success' => true,
                'labels' => array_keys($allDays),
                'tables_data' => array_values($allDays),
                'most_popular_day' => $mostPopularDay,
                'period' => $period,
                'current_range' => [
                    'start' => $dateRange['start']->format('Y-m-d'),
                    'end' => $dateRange['end']->format('Y-m-d'),
                    'label' => $dateRange['label']
                ],
                'offset' => $offset
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 5. Статусы столов - за период (неделя/месяц/год) с переключением
     */
    public function tableStatuses(Request $request)
    {
        try {
            $period = $request->input('period', 'month');
            $offset = (int)$request->input('offset', 0);
            
            // Получаем диапазон дат на основе периода и смещения
            $dateRange = $this->getPeriodRange($period, $offset);
            
            $query = Table::whereBetween('booking_date', [$dateRange['start'], $dateRange['end']]);
            
            $data = $query->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get();
            
            $statusLabels = [
                'new' => 'Новые',
                'opened_without_hookah' => 'Открытые (без кальяна)',
                'opened_with_hookah' => 'Открытые (с кальяном)',
                'closed' => 'Закрытые'
            ];
            
            return response()->json([
                'success' => true,
                'labels' => $data->map(fn($item) => $statusLabels[$item->status] ?? $item->status),
                'data' => $data->pluck('count')->map(fn($v) => (int)$v),
                'total' => (int)$data->sum('count'),
                'period' => $period,
                'current_range' => [
                    'start' => $dateRange['start']->format('Y-m-d'),
                    'end' => $dateRange['end']->format('Y-m-d'),
                    'label' => $dateRange['label']
                ],
                'offset' => $offset
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 6. Средняя продолжительность - за период (неделя/месяц/год) с переключением
     */
    public function averageDuration(Request $request)
    {
        try {
            $period = $request->input('period', 'month');
            $offset = (int)$request->input('offset', 0);
            
            // Получаем диапазон дат на основе периода и смещения
            $dateRange = $this->getPeriodRange($period, $offset);
            
            $query = Table::whereNotNull('duration')
                ->whereBetween('booking_date', [$dateRange['start'], $dateRange['end']]);
            
            $stats = $query->select(
                    DB::raw('AVG(duration) as avg'),
                    DB::raw('MIN(duration) as min'),
                    DB::raw('MAX(duration) as max')
                )
                ->first();
            
            $format = function($minutes) {
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                if ($hours > 0) {
                    return "{$hours} ч " . ($mins > 0 ? "{$mins} мин" : "");
                }
                return "{$mins} мин";
            };
            
            return response()->json([
                'success' => true,
                'avg_duration_formatted' => $stats && $stats->avg ? $format($stats->avg) : '0 мин',
                'min_duration' => $stats && $stats->min ? (int)$stats->min : 0,
                'max_duration' => $stats && $stats->max ? (int)$stats->max : 0,
                'avg_duration_minutes' => $stats && $stats->avg ? round($stats->avg, 1) : 0,
                'period' => $period,
                'current_range' => [
                    'start' => $dateRange['start']->format('Y-m-d'),
                    'end' => $dateRange['end']->format('Y-m-d'),
                    'label' => $dateRange['label']
                ],
                'offset' => $offset
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 7. Методы оплаты - за период (неделя/месяц/год) с переключением
     * Используем таблицу sales
     */
    public function paymentMethods(Request $request)
    {
        try {
            $period = $request->input('period', 'month');
            $offset = (int)$request->input('offset', 0);
            
            // Получаем диапазон дат на основе периода и смещения
            $dateRange = $this->getPeriodRange($period, $offset);
            
            $query = Sale::query()
                ->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']]);
            
            // Группируем по способам оплаты
            $data = $query->select('payment_method_id', DB::raw('COUNT(*) as count'))
                ->whereNotNull('payment_method_id')
                ->groupBy('payment_method_id')
                ->with('paymentMethod')
                ->get();
            
            // Форматируем данные
            $labels = [];
            $counts = [];
            $amounts = [];
            
            foreach ($data as $item) {
                if ($item->paymentMethod) {
                    $labels[] = $item->paymentMethod->Name;
                    $counts[] = (int)$item->count;
                    
                    // Сумма продаж по этому способу оплаты
                    $amount = $query->clone()
                        ->where('payment_method_id', $item->payment_method_id)
                        ->sum('total');
                    $amounts[] = (float)$amount;
                }
            }
            
            // Ищем самый популярный способ оплаты
            if (!empty($counts)) {
                $maxIndex = array_search(max($counts), $counts);
                $mostPopular = $labels[$maxIndex] ?? null;
            } else {
                $mostPopular = null;
            }
            
            return response()->json([
                'success' => true,
                'labels' => $labels,
                'data' => $counts,
                'amounts' => $amounts,
                'most_popular' => $mostPopular,
                'period' => $period,
                'current_range' => [
                    'start' => $dateRange['start']->format('Y-m-d'),
                    'end' => $dateRange['end']->format('Y-m-d'),
                    'label' => $dateRange['label']
                ],
                'offset' => $offset
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 8. Сводка - за период (неделя/месяц/год) с переключением
     */
    public function summary(Request $request)
    {
        try {
            $period = $request->input('period', 'month');
            $offset = (int)$request->input('offset', 0);
            
            // Получаем диапазон дат на основе периода и смещения
            $dateRange = $this->getPeriodRange($period, $offset);
            
            // Для таблиц
            $tableQuery = Table::query()
                ->whereBetween('booking_date', [$dateRange['start'], $dateRange['end']]);
            
            // Для продаж
            $saleQuery = Sale::query()
                ->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']]);
            
            // Статистика по столам
            $tableStats = $tableQuery->select(
                DB::raw('COUNT(*) as total_bookings'),
                DB::raw('SUM(guests_count) as total_guests'),
                DB::raw('AVG(guests_count) as avg_guests')
            )->first();
            
            // Статистика по продажам
            $saleStats = $saleQuery->select(
                DB::raw('COUNT(*) as total_sales'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('AVG(total) as avg_sale')
            )->first();
            
            // Количество дней с бронированиями
            $activeDays = $tableQuery->clone()
                ->select(DB::raw('COUNT(DISTINCT booking_date) as days'))
                ->first();
            
            return response()->json([
                'success' => true,
                'total_bookings' => (int)($tableStats->total_bookings ?? 0),
                'total_guests' => (int)($tableStats->total_guests ?? 0),
                'avg_guests_per_booking' => round($tableStats->avg_guests ?? 0, 1),
                'total_sales' => (int)($saleStats->total_sales ?? 0),
                'total_revenue' => round($saleStats->total_revenue ?? 0, 2),
                'avg_sale' => round($saleStats->avg_sale ?? 0, 2),
                'active_days' => (int)($activeDays->days ?? 0),
                'avg_bookings_per_day' => ($activeDays->days ?? 0) > 0 
                    ? round(($tableStats->total_bookings ?? 0) / ($activeDays->days ?? 1), 1)
                    : 0,
                'period' => $period,
                'current_range' => [
                    'start' => $dateRange['start']->format('Y-m-d'),
                    'end' => $dateRange['end']->format('Y-m-d'),
                    'label' => $dateRange['label']
                ],
                'offset' => $offset
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Вспомогательный метод для получения даты начала по умолчанию
     */
    private function getDefaultStartDate($period, $units = 12)
    {
        $now = Carbon::now();
        
        return match ($period) {
            'day' => $now->copy()->subDays($units),
            'week' => $now->copy()->subWeeks($units),
            'year' => $now->copy()->subYears($units),
            default => $now->copy()->subMonths($units), // month
        };
    }
    
    /**
     * Вспомогательный метод для получения диапазона дат на основе периода и смещения
     * offset = 0 - текущий период
     * offset = -1 - предыдущий период
     * offset = 1 - следующий период
     */
    private function getPeriodRange($period, $offset = 0, $year = null)
    {
        $now = $year ? Carbon::create($year, 1, 1) : Carbon::now();
        
        switch ($period) {
            case 'week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                
                if ($offset !== 0) {
                    $start->addWeeks($offset);
                    $end->addWeeks($offset);
                }
                
                $label = $start->format('d.m.Y') . ' - ' . $end->format('d.m.Y');
                break;
                
            case 'year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                
                if ($offset !== 0) {
                    $start->addYears($offset);
                    $end->addYears($offset);
                }
                
                $label = $start->format('Y');
                break;
                
            case 'day':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                
                if ($offset !== 0) {
                    $start->addDays($offset);
                    $end->addDays($offset);
                }
                
                $label = $start->format('d.m.Y');
                break;
                
            case 'month':
            default:
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                
                if ($offset !== 0) {
                    $start->addMonths($offset);
                    $end->addMonths($offset);
                }
                
                $monthNames = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 
                            'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
                $label = $monthNames[(int)$start->format('n') - 1] . ' ' . $start->format('Y');
                break;
        }
        
        return [
            'start' => $start,
            'end' => $end,
            'label' => $label
        ];
    }

   /**
     * 9. Финансовая статистика - выручка и прибыль (реальная реализация)
     * Используем данные из таблиц: sales, expenditures, fines
     */
    public function revenueProfitStats(Request $request)
    {
        try {
            \Log::info('Revenue profit stats called', $request->all());
            
            $period = $request->input('period', 'month');
            
            $hasSales = \Schema::hasTable('sales');
            $hasExpenditures = \Schema::hasTable('expenditures');
            $hasFines = \Schema::hasTable('fines');
            
            if (!$hasSales) {
                return response()->json([
                    'success' => false,
                    'error' => 'Таблица sales не существует',
                    'debug_info' => 'Проверьте миграции и базу данных'
                ], 500);
            }
            
            // Получаем МИНИМАЛЬНУЮ дату с начала истории
            $firstSaleDate = Sale::where('status', 'completed')
                ->orderBy('sale_date')
                ->value('sale_date');
                
            if (!$firstSaleDate) {
                // Если нет продаж, возвращаем пустые данные
                return response()->json([
                    'success' => true,
                    'labels' => [],
                    'revenue_data' => [],
                    'profit_data' => [],
                    'expenses_data' => [],
                    'total_revenue' => 0,
                    'total_profit' => 0,
                    'total_expenses' => 0,
                    'total_expenditures' => 0,
                    'total_fines' => 0,
                    'period' => $period,
                    'date_range' => [
                        'start' => null,
                        'end' => null
                    ]
                ]);
            }
            
            $start = Carbon::parse($firstSaleDate)->startOfDay();
            $end = Carbon::now()->endOfDay();
            
            \Log::info('Date range for revenue profit (ALL TIME)', [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'period' => $period
            ]);
            
            if ($period === 'day') {
                // ПО ДНЯМ: ВСЯ история по дням
                
                // Создаем массив всех дней в диапазоне
                $allDays = [];
                $currentDate = $start->copy();
                
                while ($currentDate <= $end) {
                    $allDays[$currentDate->format('Y-m-d')] = [
                        'date' => $currentDate->copy(),
                        'revenue' => 0,
                        'expenditures' => 0,
                        'fines' => 0
                    ];
                    $currentDate->addDay();
                }
                
                // Получаем выручку по дням
                $salesByDay = Sale::select(
                        DB::raw('DATE(sale_date) as date'),
                        DB::raw('SUM(total) as revenue')
                    )
                    ->where('status', 'completed')
                    ->whereBetween('sale_date', [$start, $end])
                    ->groupBy(DB::raw('DATE(sale_date)'))
                    ->get()
                    ->keyBy('date');
                
                // Получаем расходы по дням
                $expendituresByDay = Expenditure::select(
                        DB::raw('DATE(expenditure_date) as date'),
                        DB::raw('SUM(cost) as expenditures')
                    )
                    ->whereBetween('expenditure_date', [$start, $end])
                    ->groupBy(DB::raw('DATE(expenditure_date)'))
                    ->get()
                    ->keyBy('date');
                
                // Получаем штрафы по дням
                $finesByDay = Fine::select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('SUM(amount) as fines')
                    )
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy(DB::raw('DATE(created_at)'))
                    ->get()
                    ->keyBy('date');
                
                // Заполняем данные
                foreach ($allDays as $dateStr => $dayData) {
                    if ($salesByDay->has($dateStr)) {
                        $allDays[$dateStr]['revenue'] = (float)$salesByDay[$dateStr]->revenue;
                    }
                    if ($expendituresByDay->has($dateStr)) {
                        $allDays[$dateStr]['expenditures'] = (float)$expendituresByDay[$dateStr]->expenditures;
                    }
                    if ($finesByDay->has($dateStr)) {
                        $allDays[$dateStr]['fines'] = (float)$finesByDay[$dateStr]->fines;
                    }
                }
                
                // Формируем ответ
                $labels = [];
                $revenueData = [];
                $expensesData = [];
                $profitData = [];
                
                foreach ($allDays as $dateStr => $dayData) {
                    $labels[] = $dayData['date']->format('d.m.Y');
                    $revenueData[] = $dayData['revenue'];
                    $totalExpenses = $dayData['expenditures'] + $dayData['fines'];
                    $expensesData[] = $totalExpenses;
                    $profitData[] = $dayData['revenue'] - $totalExpenses;
                }
                
            } elseif ($period === 'week') {
                // ПО НЕДЕЛЯМ: ВСЯ история по неделям
                
                // Начинаем с начала недели первой продажи
                $startWeek = $start->copy()->startOfWeek();
                $endWeek = $end->copy()->endOfWeek();
                
                // Создаем массив всех недель в диапазоне
                $allWeeks = [];
                $currentWeek = $startWeek->copy();
                
                while ($currentWeek <= $endWeek) {
                    $weekKey = $currentWeek->format('Y-W');
                    $allWeeks[$weekKey] = [
                        'start_date' => $currentWeek->copy(),
                        'end_date' => $currentWeek->copy()->endOfWeek(),
                        'revenue' => 0,
                        'expenditures' => 0,
                        'fines' => 0
                    ];
                    $currentWeek->addWeek();
                }
                
                // Получаем выручку по неделям
                $salesByWeek = Sale::select(
                        DB::raw('EXTRACT(YEAR FROM sale_date) as year'),
                        DB::raw('EXTRACT(WEEK FROM sale_date) as week_number'),
                        DB::raw('SUM(total) as revenue')
                    )
                    ->where('status', 'completed')
                    ->whereBetween('sale_date', [$start, $end])
                    ->groupBy('year', 'week_number')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->week_number, 2, '0', STR_PAD_LEFT);
                    });
                
                // Получаем расходы по неделям
                $expendituresByWeek = Expenditure::select(
                        DB::raw('EXTRACT(YEAR FROM expenditure_date) as year'),
                        DB::raw('EXTRACT(WEEK FROM expenditure_date) as week_number'),
                        DB::raw('SUM(cost) as expenditures')
                    )
                    ->whereBetween('expenditure_date', [$start, $end])
                    ->groupBy('year', 'week_number')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->week_number, 2, '0', STR_PAD_LEFT);
                    });
                
                // Получаем штрафы по неделям
                $finesByWeek = Fine::select(
                        DB::raw('EXTRACT(YEAR FROM created_at) as year'),
                        DB::raw('EXTRACT(WEEK FROM created_at) as week_number'),
                        DB::raw('SUM(amount) as fines')
                    )
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy('year', 'week_number')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->week_number, 2, '0', STR_PAD_LEFT);
                    });
                
                // Заполняем данные
                foreach ($allWeeks as $weekKey => $weekData) {
                    if ($salesByWeek->has($weekKey)) {
                        $allWeeks[$weekKey]['revenue'] = (float)$salesByWeek[$weekKey]->revenue;
                    }
                    if ($expendituresByWeek->has($weekKey)) {
                        $allWeeks[$weekKey]['expenditures'] = (float)$expendituresByWeek[$weekKey]->expenditures;
                    }
                    if ($finesByWeek->has($weekKey)) {
                        $allWeeks[$weekKey]['fines'] = (float)$finesByWeek[$weekKey]->fines;
                    }
                }
                
                // Формируем ответ
                $labels = [];
                $revenueData = [];
                $expensesData = [];
                $profitData = [];
                
                foreach ($allWeeks as $weekKey => $weekData) {
                    $startDate = $weekData['start_date'];
                    $endDate = $weekData['end_date'];
                    
                    // Показываем только недели, которые пересекаются с нашим диапазоном
                    if ($endDate >= $start && $startDate <= $end) {
                        $labels[] = $startDate->format('d.m') . '-' . $endDate->format('d.m');
                        $revenueData[] = $weekData['revenue'];
                        $totalExpenses = $weekData['expenditures'] + $weekData['fines'];
                        $expensesData[] = $totalExpenses;
                        $profitData[] = $weekData['revenue'] - $totalExpenses;
                    }
                }
                
            } else { // month
                // ПО МЕСЯЦАМ: ВСЯ история по месяцам
                
                $startMonth = $start->copy()->startOfMonth();
                $endMonth = $end->copy()->endOfMonth();
                
                // Создаем массив всех месяцев в диапазоне
                $allMonths = [];
                $currentMonth = $startMonth->copy();
                
                while ($currentMonth <= $endMonth) {
                    $monthKey = $currentMonth->format('Y-m');
                    $allMonths[$monthKey] = [
                        'month' => $currentMonth->copy(),
                        'revenue' => 0,
                        'expenditures' => 0,
                        'fines' => 0
                    ];
                    $currentMonth->addMonth();
                }
                
                // Получаем выручку по месяцам
                $salesByMonth = Sale::select(
                        DB::raw('EXTRACT(YEAR FROM sale_date) as year'),
                        DB::raw('EXTRACT(MONTH FROM sale_date) as month'),
                        DB::raw('SUM(total) as revenue')
                    )
                    ->where('status', 'completed')
                    ->whereBetween('sale_date', [$start, $end])
                    ->groupBy('year', 'month')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                    });
                
                // Получаем расходы по месяцам
                $expendituresByMonth = Expenditure::select(
                        DB::raw('EXTRACT(YEAR FROM expenditure_date) as year'),
                        DB::raw('EXTRACT(MONTH FROM expenditure_date) as month'),
                        DB::raw('SUM(cost) as expenditures')
                    )
                    ->whereBetween('expenditure_date', [$start, $end])
                    ->groupBy('year', 'month')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                    });
                
                // Получаем штрафы по месяцам
                $finesByMonth = Fine::select(
                        DB::raw('EXTRACT(YEAR FROM created_at) as year'),
                        DB::raw('EXTRACT(MONTH FROM created_at) as month'),
                        DB::raw('SUM(amount) as fines')
                    )
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy('year', 'month')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                    });
                
                // Заполняем данные
                foreach ($allMonths as $monthKey => $monthData) {
                    if ($salesByMonth->has($monthKey)) {
                        $allMonths[$monthKey]['revenue'] = (float)$salesByMonth[$monthKey]->revenue;
                    }
                    if ($expendituresByMonth->has($monthKey)) {
                        $allMonths[$monthKey]['expenditures'] = (float)$expendituresByMonth[$monthKey]->expenditures;
                    }
                    if ($finesByMonth->has($monthKey)) {
                        $allMonths[$monthKey]['fines'] = (float)$finesByMonth[$monthKey]->fines;
                    }
                }
                
                // Формируем ответ
                $labels = [];
                $revenueData = [];
                $expensesData = [];
                $profitData = [];
                
                $monthNames = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 
                            'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
                
                foreach ($allMonths as $monthKey => $monthData) {
                    $monthDate = $monthData['month'];
                    
                    // Показываем только месяцы, которые пересекаются с нашим диапазоном
                    if ($monthDate->endOfMonth() >= $start && $monthDate->startOfMonth() <= $end) {
                        $labels[] = $monthNames[(int)$monthDate->format('n') - 1] . ' ' . $monthDate->format('Y');
                        $revenueData[] = $monthData['revenue'];
                        $totalExpenses = $monthData['expenditures'] + $monthData['fines'];
                        $expensesData[] = $totalExpenses;
                        $profitData[] = $monthData['revenue'] - $totalExpenses;
                    }
                }
            }
            
            // Общие суммы за весь период
            $totalRevenue = Sale::where('status', 'completed')
                ->whereBetween('sale_date', [$start, $end])
                ->sum('total') ?? 0;
            
            $totalExpenditures = Expenditure::whereBetween('expenditure_date', [$start, $end])
                ->sum('cost') ?? 0;
            
            $totalFines = Fine::whereBetween('created_at', [$start, $end])
                ->sum('amount') ?? 0;
            
            $totalExpenses = $totalExpenditures + $totalFines;
            $totalProfit = $totalRevenue - $totalExpenses;
            
            $response = [
                'success' => true,
                'labels' => $labels,
                'revenue_data' => $revenueData,
                'profit_data' => $profitData,
                'expenses_data' => $expensesData,
                'total_revenue' => round($totalRevenue, 2),
                'total_profit' => round($totalProfit, 2),
                'total_expenses' => round($totalExpenses, 2),
                'total_expenditures' => round($totalExpenditures, 2),
                'total_fines' => round($totalFines, 2),
                'period' => $period,
                'date_range' => [
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->format('Y-m-d')
                ]
            ];
            
            \Log::info('Revenue profit stats response prepared', [
                'labels_count' => count($labels),
                'period' => $period,
                'total_revenue' => $totalRevenue
            ]);
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            \Log::error('Error in revenueProfitStats: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при получении финансовой статистики',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 10. Статистика среднего чека (реальная реализация)
     */
    public function averageCheckStats(Request $request)
    {
        try {
            \Log::info('averageCheckStats called', $request->all());
            
            $period = $request->input('period', 'month');
            
            // Получаем МИНИМАЛЬНУЮ дату с начала истории
            $firstSaleDate = Sale::where('status', 'completed')
                ->orderBy('sale_date')
                ->value('sale_date');
                
            if (!$firstSaleDate) {
                // Если нет продаж, возвращаем пустые данные
                return response()->json([
                    'success' => true,
                    'labels' => [],
                    'average_check_data' => [],
                    'sales_count_data' => [],
                    'total_sales' => 0,
                    'total_average_check' => 0,
                    'period' => $period,
                    'date_range' => [
                        'start' => null,
                        'end' => null
                    ]
                ]);
            }
            
            $start = Carbon::parse($firstSaleDate)->startOfDay();
            $end = Carbon::now()->endOfDay();
            
            \Log::info('Date range for average check (ALL TIME)', [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'period' => $period
            ]);
            
            if ($period === 'day') {
                // ПО ДНЯМ: ВСЯ история по дням
                
                // Получаем статистику по дням одним запросом
                $dailyStats = Sale::select(
                        DB::raw('DATE(sale_date) as date'),
                        DB::raw('COUNT(*) as sales_count'),
                        DB::raw('AVG(total) as avg_check')
                    )
                    ->where('status', 'completed')
                    ->whereBetween('sale_date', [$start, $end])
                    ->groupBy(DB::raw('DATE(sale_date)'))
                    ->orderBy('date')
                    ->get()
                    ->keyBy('date');
                
                // Создаем массив всех дней в диапазоне
                $allDays = [];
                $current = $start->copy();
                
                while ($current <= $end) {
                    $dateStr = $current->format('Y-m-d');
                    $allDays[$dateStr] = [
                        'date' => $current->copy(),
                        'sales_count' => 0,
                        'avg_check' => 0
                    ];
                    $current->addDay();
                }
                
                // Заполняем данные
                foreach ($dailyStats as $dateStr => $stat) {
                    if (isset($allDays[$dateStr])) {
                        $allDays[$dateStr]['sales_count'] = (int)$stat->sales_count;
                        $allDays[$dateStr]['avg_check'] = round($stat->avg_check, 2);
                    }
                }
                
                // Формируем ответ
                $labels = [];
                $averageCheckData = [];
                $salesCountData = [];
                
                foreach ($allDays as $dateStr => $dayData) {
                    $labels[] = $dayData['date']->format('d.m.Y');
                    $averageCheckData[] = $dayData['avg_check'];
                    $salesCountData[] = $dayData['sales_count'];
                }
                
            } elseif ($period === 'week') {
                // ПО НЕДЕЛЯМ: ВСЯ история по неделям
                
                $startWeek = $start->copy()->startOfWeek();
                $endWeek = $end->copy()->endOfWeek();
                
                // Получаем статистику по неделям
                $weeklyStats = Sale::select(
                        DB::raw('EXTRACT(YEAR FROM sale_date) as year'),
                        DB::raw('EXTRACT(WEEK FROM sale_date) as week'),
                        DB::raw('COUNT(*) as sales_count'),
                        DB::raw('AVG(total) as avg_check')
                    )
                    ->where('status', 'completed')
                    ->whereBetween('sale_date', [$start, $end])
                    ->groupBy('year', 'week')
                    ->orderBy('year')
                    ->orderBy('week')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->week, 2, '0', STR_PAD_LEFT);
                    });
                
                // Создаем массив всех недель в диапазоне
                $allWeeks = [];
                $current = $startWeek->copy();
                
                while ($current <= $endWeek) {
                    $weekKey = $current->format('Y-W');
                    $allWeeks[$weekKey] = [
                        'start_date' => $current->copy(),
                        'end_date' => $current->copy()->endOfWeek(),
                        'sales_count' => 0,
                        'avg_check' => 0
                    ];
                    $current->addWeek();
                }
                
                // Заполняем данные
                foreach ($weeklyStats as $weekKey => $stat) {
                    if (isset($allWeeks[$weekKey])) {
                        $allWeeks[$weekKey]['sales_count'] = (int)$stat->sales_count;
                        $allWeeks[$weekKey]['avg_check'] = round($stat->avg_check, 2);
                    }
                }
                
                // Формируем ответ
                $labels = [];
                $averageCheckData = [];
                $salesCountData = [];
                
                foreach ($allWeeks as $weekKey => $weekData) {
                    // Показываем только недели, которые пересекаются с нашим диапазоном
                    if ($weekData['end_date'] >= $start && $weekData['start_date'] <= $end) {
                        $labels[] = $weekData['start_date']->format('d.m') . '-' . $weekData['end_date']->format('d.m');
                        $averageCheckData[] = $weekData['avg_check'];
                        $salesCountData[] = $weekData['sales_count'];
                    }
                }
                
            } else { // month
                // ПО МЕСЯЦАМ: ВСЯ история по месяцам
                
                $startMonth = $start->copy()->startOfMonth();
                $endMonth = $end->copy()->endOfMonth();
                
                // Получаем статистику по месяцам
                $monthlyStats = Sale::select(
                        DB::raw('EXTRACT(YEAR FROM sale_date) as year'),
                        DB::raw('EXTRACT(MONTH FROM sale_date) as month'),
                        DB::raw('COUNT(*) as sales_count'),
                        DB::raw('AVG(total) as avg_check')
                    )
                    ->where('status', 'completed')
                    ->whereBetween('sale_date', [$start, $end])
                    ->groupBy('year', 'month')
                    ->orderBy('year')
                    ->orderBy('month')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                    });
                
                // Создаем массив всех месяцев в диапазоне
                $allMonths = [];
                $current = $startMonth->copy();
                $monthNames = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 
                            'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
                
                while ($current <= $endMonth) {
                    $monthKey = $current->format('Y-m');
                    $allMonths[$monthKey] = [
                        'month' => $current->copy(),
                        'sales_count' => 0,
                        'avg_check' => 0,
                        'label' => $monthNames[(int)$current->format('n') - 1] . ' ' . $current->format('Y')
                    ];
                    $current->addMonth();
                }
                
                // Заполняем данные
                foreach ($monthlyStats as $monthKey => $stat) {
                    if (isset($allMonths[$monthKey])) {
                        $allMonths[$monthKey]['sales_count'] = (int)$stat->sales_count;
                        $allMonths[$monthKey]['avg_check'] = round($stat->avg_check, 2);
                    }
                }
                
                // Формируем ответ
                $labels = [];
                $averageCheckData = [];
                $salesCountData = [];
                
                foreach ($allMonths as $monthKey => $monthData) {
                    // Показываем только месяцы, которые пересекаются с нашим диапазоном
                    if ($monthData['month']->endOfMonth() >= $start && $monthData['month']->startOfMonth() <= $end) {
                        $labels[] = $monthData['label'];
                        $averageCheckData[] = $monthData['avg_check'];
                        $salesCountData[] = $monthData['sales_count'];
                    }
                }
            }
            
            // Общая статистика за весь период
            $overallStats = Sale::select(
                    DB::raw('COUNT(*) as total_sales'),
                    DB::raw('AVG(total) as overall_avg_check')
                )
                ->where('status', 'completed')
                ->whereBetween('sale_date', [$start, $end])
                ->first();
            
            return response()->json([
                'success' => true,
                'labels' => $labels,
                'average_check_data' => $averageCheckData,
                'sales_count_data' => $salesCountData,
                'total_sales' => $overallStats ? (int)$overallStats->total_sales : 0,
                'total_average_check' => $overallStats ? round($overallStats->overall_avg_check, 2) : 0,
                'period' => $period,
                'date_range' => [
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->format('Y-m-d')
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in averageCheckStats: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при получении статистики среднего чека: ' . $e->getMessage()
            ], 500);
        }
    }

   /**
     * 11. Статистика расходов (упрощенная версия)
     */
    public function expensesStats(Request $request)
    {
        try {
            \Log::info('expensesStats called', $request->all());
            
            $period = $request->input('period', 'month');
            
            // Получаем МИНИМАЛЬНУЮ дату расходов с начала истории
            $firstExpenditureDate = Expenditure::orderBy('expenditure_date')
                ->value('expenditure_date');
                
            $firstFineDate = Fine::orderBy('created_at')
                ->value('created_at');
            
            // Определяем самую раннюю дату
            $dates = array_filter([$firstExpenditureDate, $firstFineDate]);
            $firstDate = $dates ? min($dates) : null;
            
            if (!$firstDate) {
                // Если нет расходов, возвращаем пустые данные
                return response()->json([
                    'success' => true,
                    'labels' => [],
                    'expenses_data' => [],
                    'total_expenses' => 0,
                    'total_expenditures' => 0,
                    'total_fines' => 0,
                    'expenditure_categories' => [],
                    'period' => $period,
                    'date_range' => [
                        'start' => null,
                        'end' => null
                    ]
                ]);
            }
            
            $start = Carbon::parse($firstDate)->startOfDay();
            $end = Carbon::now()->endOfDay();
            
            \Log::info('Date range for expenses (ALL TIME)', [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'period' => $period
            ]);
            
            if ($period === 'day') {
                // ПО ДНЯМ: ВСЯ история по дням
                
                // Получаем расходы по дням
                $expendituresByDay = Expenditure::select(
                        DB::raw('DATE(expenditure_date) as date'),
                        DB::raw('SUM(cost) as expenditures')
                    )
                    ->whereBetween('expenditure_date', [$start, $end])
                    ->groupBy(DB::raw('DATE(expenditure_date)'))
                    ->get()
                    ->keyBy('date');
                
                // Получаем штрафы по дням
                $finesByDay = Fine::select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('SUM(amount) as fines')
                    )
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy(DB::raw('DATE(created_at)'))
                    ->get()
                    ->keyBy('date');
                
                // Создаем массив всех дней в диапазоне
                $allDays = [];
                $current = $start->copy();
                
                while ($current <= $end) {
                    $dateStr = $current->format('Y-m-d');
                    $allDays[$dateStr] = [
                        'date' => $current->copy(),
                        'expenditures' => 0,
                        'fines' => 0,
                        'total' => 0
                    ];
                    $current->addDay();
                }
                
                // Заполняем данные
                foreach ($expendituresByDay as $dateStr => $stat) {
                    if (isset($allDays[$dateStr])) {
                        $allDays[$dateStr]['expenditures'] = (float)$stat->expenditures;
                        $allDays[$dateStr]['total'] += (float)$stat->expenditures;
                    }
                }
                
                foreach ($finesByDay as $dateStr => $stat) {
                    if (isset($allDays[$dateStr])) {
                        $allDays[$dateStr]['fines'] = (float)$stat->fines;
                        $allDays[$dateStr]['total'] += (float)$stat->fines;
                    }
                }
                
                // Формируем ответ
                $labels = [];
                $expensesData = [];
                
                foreach ($allDays as $dateStr => $dayData) {
                    $labels[] = $dayData['date']->format('d.m.Y');
                    $expensesData[] = $dayData['total'];
                }
                
            } elseif ($period === 'week') {
                // ПО НЕДЕЛЯМ: ВСЯ история по неделям
                
                $startWeek = $start->copy()->startOfWeek();
                $endWeek = $end->copy()->endOfWeek();
                
                // Получаем расходы по неделям
                $expendituresByWeek = Expenditure::select(
                        DB::raw('EXTRACT(YEAR FROM expenditure_date) as year'),
                        DB::raw('EXTRACT(WEEK FROM expenditure_date) as week_number'),
                        DB::raw('SUM(cost) as expenditures')
                    )
                    ->whereBetween('expenditure_date', [$start, $end])
                    ->groupBy('year', 'week_number')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->week_number, 2, '0', STR_PAD_LEFT);
                    });
                
                // Получаем штрафы по неделям
                $finesByWeek = Fine::select(
                        DB::raw('EXTRACT(YEAR FROM created_at) as year'),
                        DB::raw('EXTRACT(WEEK FROM created_at) as week_number'),
                        DB::raw('SUM(amount) as fines')
                    )
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy('year', 'week_number')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->week_number, 2, '0', STR_PAD_LEFT);
                    });
                
                // Создаем массив всех недель в диапазоне
                $allWeeks = [];
                $current = $startWeek->copy();
                
                while ($current <= $endWeek) {
                    $weekKey = $current->format('Y-W');
                    $allWeeks[$weekKey] = [
                        'start_date' => $current->copy(),
                        'end_date' => $current->copy()->endOfWeek(),
                        'expenditures' => 0,
                        'fines' => 0,
                        'total' => 0
                    ];
                    $current->addWeek();
                }
                
                // Заполняем данные
                foreach ($expendituresByWeek as $weekKey => $stat) {
                    if (isset($allWeeks[$weekKey])) {
                        $allWeeks[$weekKey]['expenditures'] = (float)$stat->expenditures;
                        $allWeeks[$weekKey]['total'] += (float)$stat->expenditures;
                    }
                }
                
                foreach ($finesByWeek as $weekKey => $stat) {
                    if (isset($allWeeks[$weekKey])) {
                        $allWeeks[$weekKey]['fines'] = (float)$stat->fines;
                        $allWeeks[$weekKey]['total'] += (float)$stat->fines;
                    }
                }
                
                // Формируем ответ
                $labels = [];
                $expensesData = [];
                
                foreach ($allWeeks as $weekKey => $weekData) {
                    // Показываем только недели, которые пересекаются с нашим диапазоном
                    if ($weekData['end_date'] >= $start && $weekData['start_date'] <= $end) {
                        $labels[] = $weekData['start_date']->format('d.m') . '-' . $weekData['end_date']->format('d.m');
                        $expensesData[] = $weekData['total'];
                    }
                }
                
            } else { // month
                // ПО МЕСЯЦАМ: ВСЯ история по месяцам
                
                $startMonth = $start->copy()->startOfMonth();
                $endMonth = $end->copy()->endOfMonth();
                
                // Получаем расходы по месяцам
                $expendituresByMonth = Expenditure::select(
                        DB::raw('EXTRACT(YEAR FROM expenditure_date) as year'),
                        DB::raw('EXTRACT(MONTH FROM expenditure_date) as month'),
                        DB::raw('SUM(cost) as expenditures')
                    )
                    ->whereBetween('expenditure_date', [$start, $end])
                    ->groupBy('year', 'month')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                    });
                
                // Получаем штрафы по месяцам
                $finesByMonth = Fine::select(
                        DB::raw('EXTRACT(YEAR FROM created_at) as year'),
                        DB::raw('EXTRACT(MONTH FROM created_at) as month'),
                        DB::raw('SUM(amount) as fines')
                    )
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy('year', 'month')
                    ->get()
                    ->keyBy(function($item) {
                        return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                    });
                
                // Создаем массив всех месяцев в диапазоне
                $allMonths = [];
                $current = $startMonth->copy();
                $monthNames = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 
                            'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
                
                while ($current <= $endMonth) {
                    $monthKey = $current->format('Y-m');
                    $allMonths[$monthKey] = [
                        'month' => $current->copy(),
                        'expenditures' => 0,
                        'fines' => 0,
                        'total' => 0,
                        'label' => $monthNames[(int)$current->format('n') - 1] . ' ' . $current->format('Y')
                    ];
                    $current->addMonth();
                }
                
                // Заполняем данные
                foreach ($expendituresByMonth as $monthKey => $stat) {
                    if (isset($allMonths[$monthKey])) {
                        $allMonths[$monthKey]['expenditures'] = (float)$stat->expenditures;
                        $allMonths[$monthKey]['total'] += (float)$stat->expenditures;
                    }
                }
                
                foreach ($finesByMonth as $monthKey => $stat) {
                    if (isset($allMonths[$monthKey])) {
                        $allMonths[$monthKey]['fines'] = (float)$stat->fines;
                        $allMonths[$monthKey]['total'] += (float)$stat->fines;
                    }
                }
                
                // Формируем ответ
                $labels = [];
                $expensesData = [];
                
                foreach ($allMonths as $monthKey => $monthData) {
                    // Показываем только месяцы, которые пересекаются с нашим диапазоном
                    if ($monthData['month']->endOfMonth() >= $start && $monthData['month']->startOfMonth() <= $end) {
                        $labels[] = $monthData['label'];
                        $expensesData[] = $monthData['total'];
                    }
                }
            }
            
            // Категории расходов
            $categoryStats = Expenditure::select('category', DB::raw('SUM(cost) as total'))
                ->whereBetween('expenditure_date', [$start, $end])
                ->groupBy('category')
                ->get();
            
            $totalExpenditures = Expenditure::whereBetween('expenditure_date', [$start, $end])
                ->sum('cost') ?? 0;
            
            $totalFines = Fine::whereBetween('created_at', [$start, $end])
                ->sum('amount') ?? 0;
            
            $totalExpenses = $totalExpenditures + $totalFines;
            
            $expenditureCategories = [];
            foreach ($categoryStats as $stat) {
                $percentage = $totalExpenditures > 0 ? round(($stat->total / $totalExpenditures) * 100, 1) : 0;
                $expenditureCategories[] = [
                    'name' => $stat->category ?: 'Без категории',
                    'value' => (float)$stat->total,
                    'percentage' => $percentage
                ];
            }
            
            $response = [
                'success' => true,
                'labels' => $labels,
                'expenses_data' => $expensesData,
                'total_expenses' => round($totalExpenses, 2),
                'total_expenditures' => round($totalExpenditures, 2),
                'total_fines' => round($totalFines, 2),
                'expenditure_categories' => $expenditureCategories,
                'period' => $period,
                'date_range' => [
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->format('Y-m-d')
                ]
            ];
            
            \Log::info('expensesStats response prepared', [
                'labels_count' => count($labels),
                'total_expenses' => $totalExpenses
            ]);
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            \Log::error('Error in expensesStats: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Ошибка в expensesStats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 14. Статистика по кальянам
     */
    public function hookahStatistics(Request $request)
    {
        $startDate = $request->input('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $period = $request->input('period', 'month'); // month, week, custom
        
        // Если выбран период, а не даты
        if ($period === 'month' && !$request->has('start_date')) {
            $startDate = now()->startOfMonth()->format('Y-m-d');
            $endDate = now()->endOfMonth()->format('Y-m-d');
        } elseif ($period === 'week' && !$request->has('start_date')) {
            $startDate = now()->startOfWeek()->format('Y-m-d');
            $endDate = now()->endOfWeek()->format('Y-m-d');
        }
        
        // ПРАВИЛЬНО получаем статистику по кальянам за период
        $hookahStats = DB::table('sales')
            ->join('sale_hookahs', 'sales.id', '=', 'sale_hookahs.sale_id')
            ->join('hookahs', 'sale_hookahs.hookah_id', '=', 'hookahs.id')
            ->select(
                'hookahs.id',
                'hookahs.name',
                'hookahs.price',
                'hookahs.cost',
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('SUM(hookahs.price) as total_revenue'),
                DB::raw('SUM(hookahs.cost) as total_cost')
            )
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('hookahs.id', 'hookahs.name', 'hookahs.price', 'hookahs.cost')
            ->orderBy('hookahs.name')
            ->get()
            ->map(function($item) {
                $profit = $item->total_revenue - $item->total_cost;
                $profitMargin = $item->total_revenue > 0 ? ($profit / $item->total_revenue) * 100 : 0;
                
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => (float)$item->price,
                    'cost' => (float)$item->cost,
                    'sales_count' => (int)$item->sales_count,
                    'total_revenue' => (float)$item->total_revenue,
                    'total_cost' => (float)$item->total_cost,
                    'total_profit' => (float)$profit,
                    'profit_margin' => round($profitMargin, 1),
                ];
            });
        
        // Добавляем кальяны, которые не продавались
        $allHookahs = Hookah::all();
        $hookahStats = $allHookahs->map(function($hookah) use ($hookahStats) {
            $stat = $hookahStats->firstWhere('id', $hookah->id);
            
            if ($stat) {
                return $stat;
            }
            
            return [
                'id' => $hookah->id,
                'name' => $hookah->name,
                'price' => (float)$hookah->price,
                'cost' => (float)$hookah->cost,
                'sales_count' => 0,
                'total_revenue' => 0,
                'total_cost' => 0,
                'total_profit' => 0,
                'profit_margin' => 0,
            ];
        })->sortBy('name')->values();
        
        // Данные для круговой диаграммы
        $totalSales = $hookahStats->sum('sales_count');
        $pieChartData = $hookahStats->filter(function($item) {
            return $item['sales_count'] > 0;
        })->map(function($item) use ($totalSales) {
            return [
                'name' => $item['name'],
                'percentage' => $totalSales > 0 ? round(($item['sales_count'] / $totalSales) * 100, 1) : 0,
                'sales_count' => $item['sales_count'],
                'color' => $this->generateColor($item['name'])
            ];
        })->sortByDesc('sales_count')->values();
        
        // Данные для линейного графика - ДИНАМИКА по КАЖДОМУ КАЛЬЯНУ
        // Получаем все дни в диапазоне
        $allDays = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        while ($current <= $end) {
            $allDays[$current->format('Y-m-d')] = [
                'date' => $current->copy(),
                'labels' => $current->format('d.m.Y')
            ];
            $current->addDay();
        }
        
        $dynamicsLabels = array_column($allDays, 'labels');
        
        // Получаем данные по каждому кальяну за каждый день
        $dailyHookahStats = DB::table('sales')
            ->join('sale_hookahs', 'sales.id', '=', 'sale_hookahs.sale_id')
            ->join('hookahs', 'sale_hookahs.hookah_id', '=', 'hookahs.id')
            ->select(
                'hookahs.id',
                'hookahs.name',
                DB::raw('DATE(sales.sale_date) as date'),
                DB::raw('COUNT(*) as daily_count')
            )
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('hookahs.id', 'hookahs.name', DB::raw('DATE(sales.sale_date)'))
            ->orderBy('date')
            ->orderBy('hookahs.name')
            ->get();
        
        // Подготавливаем данные для каждого кальяна
        $dynamicsDatasets = [];
        $totalDailyData = array_fill(0, count($dynamicsLabels), 0); // Для линии "Всего"
        
        foreach ($allHookahs as $hookah) {
            $hookahData = array_fill(0, count($dynamicsLabels), 0);
            $hookahDailyStats = $dailyHookahStats->where('id', $hookah->id);
            
            foreach ($hookahDailyStats as $dailyStat) {
                $dateStr = Carbon::parse($dailyStat->date)->format('Y-m-d');
                $dayIndex = array_search($dateStr, array_keys($allDays));
                
                if ($dayIndex !== false) {
                    $hookahData[$dayIndex] = (int)$dailyStat->daily_count;
                    $totalDailyData[$dayIndex] += (int)$dailyStat->daily_count;
                }
            }
            
            // Добавляем только если были продажи
            if ($hookahDailyStats->count() > 0) {
                $color = $this->generateColor($hookah->name);
                $dynamicsDatasets[] = [
                    'label' => $hookah->name,
                    'data' => $hookahData,
                    'borderColor' => sprintf('rgba(%d, %d, %d, 1)', $color[0], $color[1], $color[2]),
                    'backgroundColor' => sprintf('rgba(%d, %d, %d, 0.1)', $color[0], $color[1], $color[2]),
                    'borderWidth' => 1,
                    'tension' => 0.3,
                    'fill' => false
                ];
            }
        }
        
        // Добавляем линию "Всего кальянов"
        $dynamicsDatasets[] = [
            'label' => 'Всего кальянов',
            'data' => $totalDailyData,
            'borderColor' => '#0d6efd',
            'backgroundColor' => 'rgba(13, 110, 253, 0.1)',
            'borderWidth' => 3,
            'tension' => 0.3,
            'fill' => false
        ];
        
        return response()->json([
            'success' => true,
            'table_data' => $hookahStats->toArray(),
            'pie_chart_data' => $pieChartData,
            'dynamics_data' => [
                'labels' => $dynamicsLabels,
                'datasets' => $dynamicsDatasets,
            ],
            'summary' => [
                'total_sales' => $totalSales,
                'total_revenue' => $hookahStats->sum('total_revenue'),
                'total_cost' => $hookahStats->sum('total_cost'),
                'total_profit' => $hookahStats->sum('total_profit'),
            ],
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
                'period' => $period
            ]
        ]);
    }

    /**
     * Генерация цвета по строке
     */
    private function generateColor($string)
    {
        $hash = md5($string);
        return [
            hexdec(substr($hash, 0, 2)),
            hexdec(substr($hash, 2, 2)),
            hexdec(substr($hash, 4, 2))
        ];
    }
   /**
     * Статистика по категориям товаров
     */
    private function getCategoryStatistics($startDate, $endDate)
    {
        // Проверяем, есть ли таблица product_categories
        $hasCategoriesTable = Schema::hasTable('product_categories');
        
        if ($hasCategoriesTable) {
            $stats = DB::table('sales')
                ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
                ->select(
                    'product_categories.name as category_name',
                    'product_categories.id as category_id',
                    DB::raw('SUM(sale_items.quantity) as total_quantity'),
                    DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as total_revenue'),
                    DB::raw('SUM(sale_items.quantity * products.cost) as total_cost'),
                    DB::raw('COUNT(DISTINCT products.id) as unique_products')
                )
                ->where('sales.status', 'completed')
                ->whereBetween('sales.sale_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->groupBy('product_categories.id', 'product_categories.name')
                ->orderByDesc('total_revenue')
                ->get()
                ->map(function($item) {
                    $profit = $item->total_revenue - $item->total_cost;
                    $profitMargin = $item->total_revenue > 0 ? ($profit / $item->total_revenue) * 100 : 0;
                    
                    return [
                        'category_id' => $item->category_id,
                        'category' => $item->category_name ?: 'Без категории',
                        'total_quantity' => (int)$item->total_quantity,
                        'total_revenue' => (float)$item->total_revenue,
                        'total_cost' => (float)$item->total_cost,
                        'total_profit' => (float)$profit,
                        'profit_margin' => round($profitMargin, 1),
                        'unique_products' => (int)$item->unique_products,
                        'avg_revenue_per_product' => $item->unique_products > 0 ? 
                            round($item->total_revenue / $item->unique_products, 2) : 0,
                    ];
                });
            
            // Добавляем категории, даже если нет продаж
            $allCategories = DB::table('product_categories')
                ->select('id as category_id', 'name as category_name')
                ->get();
            
            foreach ($allCategories as $category) {
                if (!$stats->contains('category_id', $category->category_id)) {
                    $stats->push([
                        'category_id' => $category->category_id,
                        'category' => $category->category_name,
                        'total_quantity' => 0,
                        'total_revenue' => 0,
                        'total_cost' => 0,
                        'total_profit' => 0,
                        'profit_margin' => 0,
                        'unique_products' => Product::where('product_category_id', $category->category_id)->count(),
                        'avg_revenue_per_product' => 0,
                    ]);
                }
            }
            
            // Добавляем товары без категории
            $noCategoryCount = Product::whereNull('product_category_id')->count();
            if ($noCategoryCount > 0) {
                $stats->push([
                    'category_id' => null,
                    'category' => 'Без категории',
                    'total_quantity' => 0,
                    'total_revenue' => 0,
                    'total_cost' => 0,
                    'total_profit' => 0,
                    'profit_margin' => 0,
                    'unique_products' => $noCategoryCount,
                    'avg_revenue_per_product' => 0,
                ]);
            }
            
            return $stats->sortByDesc('total_revenue')->values();
        } else {
            // Если нет таблицы категорий, возвращаем пустой массив
            return collect();
        }
    }

    /**
     * Распределение выручки: столы vs не столы
     */
    private function getRevenueDistribution($startDate, $endDate)
    {
        // Общая выручка
        $totalRevenue = Sale::where('status', 'completed')
            ->whereBetween('sale_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('total') ?? 0;
        
        // Выручка от продаж с привязанными столами
        $tableRevenue = Sale::where('status', 'completed')
            ->whereNotNull('table_id')
            ->whereBetween('sale_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('total') ?? 0;
        
        // Выручка от продаж без столов
        $nonTableRevenue = Sale::where('status', 'completed')
            ->whereNull('table_id')
            ->whereBetween('sale_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('total') ?? 0;
        
        return [
            'total_revenue' => (float)$totalRevenue,
            'table_revenue' => (float)$tableRevenue,
            'non_table_revenue' => (float)$nonTableRevenue,
            'table_percentage' => $totalRevenue > 0 ? round(($tableRevenue / $totalRevenue) * 100, 1) : 0,
            'non_table_percentage' => $totalRevenue > 0 ? round(($nonTableRevenue / $totalRevenue) * 100, 1) : 0,
        ];
    }

    /**
     * Детальная статистика по каждому товару
     */
    private function getProductsStatistics($startDate, $endDate, $categoryId = null)
{
    $query = DB::table('sales')
        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
        ->join('products', 'sale_items.product_id', '=', 'products.id')
        ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
        ->select(
            'products.id',
            'products.name',
            'product_categories.name as category_name',
            'product_categories.id as category_id',
            'products.price',
            'products.cost',
            DB::raw('SUM(sale_items.quantity) as total_quantity'),
            DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as total_revenue'),
            DB::raw('SUM(sale_items.quantity * products.cost) as total_cost'),
            DB::raw('COUNT(DISTINCT sales.id) as sales_count')
        )
        ->where('sales.status', 'completed')
        ->whereBetween('sales.sale_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
        ->groupBy('products.id', 'products.name', 'product_categories.name', 'product_categories.id', 'products.price', 'products.cost');
    
    // Применяем фильтр по категории если указан
    if ($categoryId) {
        if ($categoryId === 'no_category') {
            $query->whereNull('products.product_category_id');
        } else {
            $query->where('products.product_category_id', $categoryId);
        }
    }
    
    $stats = $query->get()
        ->map(function($item) {
            $profit = $item->total_revenue - $item->total_cost;
            $profitMargin = $item->total_revenue > 0 ? ($profit / $item->total_revenue) * 100 : 0;
            $avgQuantityPerSale = $item->sales_count > 0 ? $item->total_quantity / $item->sales_count : 0;
            
            return [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category_name ?: 'Без категории',
                'category_id' => $item->category_id,
                'price' => (float)$item->price,
                'cost' => (float)$item->cost,
                'total_quantity' => (int)$item->total_quantity,
                'total_revenue' => (float)$item->total_revenue,
                'total_cost' => (float)$item->total_cost,
                'total_profit' => (float)$profit,
                'profit_margin' => round($profitMargin, 1),
                'sales_count' => (int)$item->sales_count,
                'avg_quantity_per_sale' => round($avgQuantityPerSale, 2),
                'avg_revenue_per_sale' => $item->sales_count > 0 ? 
                    round($item->total_revenue / $item->sales_count, 2) : 0,
            ];
        });
    
    // Добавляем товары, которые не продавались (только если не фильтруем по категории)
    if (!$categoryId) {
        $allProductsQuery = Product::with('category');
        
        $allProducts = $allProductsQuery->get();
        $productsStats = $allProducts->map(function($product) use ($stats) {
            $stat = $stats->firstWhere('id', $product->id);
            
            if ($stat) {
                return $stat;
            }
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category ? $product->category->name : 'Без категории',
                'category_id' => $product->product_category_id,
                'price' => (float)$product->price,
                'cost' => (float)$product->cost,
                'total_quantity' => 0,
                'total_revenue' => 0,
                'total_cost' => 0,
                'total_profit' => 0,
                'profit_margin' => 0,
                'sales_count' => 0,
                'avg_quantity_per_sale' => 0,
                'avg_revenue_per_sale' => 0,
            ];
        });
    } else {
        $productsStats = $stats;
    }
    
    // Сортируем по прибыли (по убыванию)
    return $productsStats->sortByDesc('total_profit')->values();
}

    /**
     * Топ товаров по выручке
     */
    private function getTopProducts($startDate, $endDate, $limit = 10)
    {
        return DB::table('sales')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->select(
                'products.id',
                'products.name',
                'product_categories.name as category_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as total_revenue')
            )
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('products.id', 'products.name', 'product_categories.name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(function($item, $index) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category_name ?: 'Без категории',
                    'total_quantity' => (int)$item->total_quantity,
                    'total_revenue' => (float)$item->total_revenue,
                    'rank' => $index + 1,
                ];
            });
    }

    private function getTopProductsByProfit($startDate, $endDate, $limit = 10)
    {
        return DB::table('sales')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->select(
                'products.id',
                'products.name',
                'product_categories.name as category_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as total_revenue'),
                DB::raw('SUM(sale_items.quantity * products.cost) as total_cost'),
                DB::raw('SUM((sale_items.quantity * sale_items.unit_price) - (sale_items.quantity * products.cost)) as total_profit')
            )
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('products.id', 'products.name', 'product_categories.name')
            ->orderByDesc('total_profit')
            ->limit($limit)
            ->get()
            ->map(function($item, $index) {
                $profitMargin = $item->total_revenue > 0 ? ($item->total_profit / $item->total_revenue) * 100 : 0;
                
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category_name ?: 'Без категории',
                    'total_quantity' => (int)$item->total_quantity,
                    'total_revenue' => (float)$item->total_revenue,
                    'total_cost' => (float)$item->total_cost,
                    'total_profit' => (float)$item->total_profit,
                    'profit_margin' => round($profitMargin, 1),
                    'rank' => $index + 1,
                ];
            });
    }

    private function getAllCategories()
    {
        $categories = DB::table('product_categories')
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name
                ];
            });
        
        // Добавляем вариант "Без категории"
        $categories->push([
            'id' => 'no_category',
            'name' => 'Без категории'
        ]);
        
        return $categories;
    }
    
    public function productsStatistics(Request $request)
    {
        $startDate = $request->input('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $period = $request->input('period', 'month');
        $categoryId = $request->input('category_id');
        
        if ($period === 'month' && !$request->has('start_date')) {
            $startDate = now()->startOfMonth()->format('Y-m-d');
            $endDate = now()->endOfMonth()->format('Y-m-d');
        } elseif ($period === 'week' && !$request->has('start_date')) {
            $startDate = now()->startOfWeek()->format('Y-m-d');
            $endDate = now()->endOfWeek()->format('Y-m-d');
        }
        
        // 1. Общая статистика по продажам: столы vs не столы
        $revenueStats = $this->getRevenueDistribution($startDate, $endDate);
        
        // 2. Статистика по категориям товаров
        $categoryStats = $this->getCategoryStatistics($startDate, $endDate);
        
        // 3. Детальная статистика по каждому товару с возможностью фильтрации по категории
        $productsStats = $this->getProductsStatistics($startDate, $endDate, $categoryId);
        
        // 4. Топ товаров по прибыли (вместо выручки)
        $topProducts = $this->getTopProductsByProfit($startDate, $endDate, 10);
        
        return response()->json([
            'success' => true,
            'revenue_distribution' => $revenueStats,
            'category_stats' => $categoryStats,
            'products_stats' => $productsStats,
            'top_products' => $topProducts,
            'summary' => [
                'total_revenue' => $revenueStats['total_revenue'],
                'table_revenue' => $revenueStats['table_revenue'],
                'non_table_revenue' => $revenueStats['non_table_revenue'],
                'total_products_sold' => $productsStats->sum('total_quantity'),
                'unique_products' => $productsStats->count(),
                'total_profit' => $productsStats->sum('total_profit'),
                'avg_profit_margin' => $productsStats->where('total_revenue', '>', 0)->avg('profit_margin') ?: 0,
            ],
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
                'period' => $period
            ],
            'categories' => $this->getAllCategories() // Для фильтра по категориям
        ]);
    }

    /**
     * Статистика расходов по категориям
     */
    public function expensesStatistics(Request $request)
{
    try {
        \Log::info('=== START expensesStatistics ===');
        
        $startDate = $request->input('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $period = $request->input('period', 'month');
        
        \Log::info('Parameters:', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'period' => $period,
            'all_params' => $request->all()
        ]);
        
        // ВРЕМЕННО: возвращаем простые тестовые данные
        return $this->getTestExpensesData($request);
        
    } catch (\Exception $e) {
        \Log::error('ERROR in expensesStatistics: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'request' => $request->all()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => 'Ошибка в expensesStatistics: ' . $e->getMessage(),
            'debug' => config('app.debug') ? [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ] : null
        ], 500);
    }
}

private function getTestExpensesData($request)
{
    $period = $request->input('period', 'day');
    $offset = (int)$request->input('offset', 0);
    
    // Тестовые данные статистики по расходам
    $expensesStats = [
        [
            'category_name' => 'Зарплата',
            'count' => 4,
            'total_cost' => 50000,
            'percentage' => 40
        ],
        [
            'category_name' => 'Аренда',
            'count' => 1,
            'total_cost' => 30000,
            'percentage' => 24
        ],
        [
            'category_name' => 'Продукты',
            'count' => 8,
            'total_cost' => 25000,
            'percentage' => 20
        ],
        [
            'category_name' => 'Коммунальные',
            'count' => 2,
            'total_cost' => 20000,
            'percentage' => 16
        ],
        [
            'category_name' => 'Реклама',
            'count' => 0,
            'total_cost' => 0,
            'percentage' => 0
        ],
        [
            'category_name' => 'Ремонт',
            'count' => 0,
            'total_cost' => 0,
            'percentage' => 0
        ]
    ];
    
    // Данные для диаграммы
    $pieChartData = [
        [
            'name' => 'Зарплата',
            'value' => 50000,
            'percentage' => 40,
            'count' => 4,
            'color' => ['hex' => '#ff6384', 'rgb' => [255, 99, 132]]
        ],
        [
            'name' => 'Аренда',
            'value' => 30000,
            'percentage' => 24,
            'count' => 1,
            'color' => ['hex' => '#36a2eb', 'rgb' => [54, 162, 235]]
        ],
        [
            'name' => 'Продукты',
            'value' => 25000,
            'percentage' => 20,
            'count' => 8,
            'color' => ['hex' => '#ffce56', 'rgb' => [255, 206, 86]]
        ],
        [
            'name' => 'Коммунальные',
            'value' => 20000,
            'percentage' => 16,
            'count' => 2,
            'color' => ['hex' => '#4bc0c0', 'rgb' => [75, 192, 192]]
        ]
    ];
    
    $labels = ['2024-11-01', '2024-11-02', '2024-11-03', '2024-11-04', '2024-11-05', '2024-11-06', '2024-11-07'];
    $expensesData = [8500, 12000, 7500, 15000, 9000, 11000, 8000];
    
    return response()->json([
        'success' => true,
        'labels' => $labels,
        'expenses_data' => $expensesData,
        
        // ВАЖНО: Добавляем поле expenses_stats
        'expenses_stats' => $expensesStats,
        
        // ВАЖНО: Добавляем поле pie_chart_data
        'pie_chart_data' => $pieChartData,
        
        // Сводка
        'summary' => [
            'total_expenses' => array_sum($expensesData),
            'total_expenditures' => array_sum($expensesData) * 0.8,
            'total_fines' => array_sum($expensesData) * 0.2,
            'categories_count' => count($expensesStats),
            'categories_with_expenses' => count(array_filter($expensesStats, fn($item) => $item['total_cost'] > 0))
        ],
        
        'total_expenses' => array_sum($expensesData),
        'total_expenditures' => array_sum($expensesData) * 0.8,
        'total_fines' => array_sum($expensesData) * 0.2,
        'expenditure_categories' => $pieChartData,
        'period' => $period,
        'current_range' => [
            'start' => '2024-11-01',
            'end' => '2024-11-07',
            'label' => '01.11.2024 - 07.11.2024'
        ],
        'offset' => $offset,
        'debug' => [
            'note' => 'Тестовые данные (метод getTestExpensesData)',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]
    ]);
}


    /**
     * Генерация цвета для категории расходов
     */
    private function generateExpenseColor($categoryName)
    {
        $hash = md5($categoryName);
        return [
            'hex' => '#' . substr($hash, 0, 6),
            'rgb' => [
                hexdec(substr($hash, 0, 2)),
                hexdec(substr($hash, 2, 2)),
                hexdec(substr($hash, 4, 2))
            ]
        ];
    }

    private function getMonthLabel($month, $year)
    {
        $monthNames = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 
                    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
        
        return $monthNames[(int)$month - 1] . ' ' . $year;
    }

}