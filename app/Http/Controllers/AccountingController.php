<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Expenditure;
use App\Models\Hookah;
use App\Models\Fine;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    /**
     * Главная страница бухгалтерии
     */
    public function index(Request $request)
    {
        // Получаем тип отчета: день, неделя, месяц
        $type = $request->get('type', 'day');
        $date = $request->get('date', now()->format('Y-m-d'));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $month = $request->get('month', now()->format('Y-m'));
        
        // Количество периодов для отображения
        $daysCount = $request->get('days_count', 30);
        $weeksCount = $request->get('weeks_count', 8);
        $monthsCount = $request->get('months_count', 6);
        
        // Если фильтр по датам не выбран, показываем от сегодня вниз
        if ($type === 'day' && empty($startDate) && empty($endDate)) {
            $startDate = now()->subDays($daysCount - 1)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        } elseif ($type === 'week' && empty($startDate) && empty($endDate)) {
            $startDate = now()->subWeeks($weeksCount)->startOfWeek()->format('Y-m-d');
            $endDate = now()->endOfWeek()->format('Y-m-d');
        } elseif ($type === 'month' && empty($month)) {
            $month = now()->format('Y-m');
        }
        
        // Получаем данные для таблицы
        $tableData = $this->getTableData($type, $startDate, $endDate, $month, $daysCount, $weeksCount, $monthsCount);
        
        // Общая статистика за все время
        $totalStats = $this->getTotalStats();
        
        return view('accounting.index', [
            'tableData' => $tableData,
            'type' => $type,
            'date' => $date,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'month' => $month,
            'daysCount' => $daysCount,
            'weeksCount' => $weeksCount,
            'monthsCount' => $monthsCount,
            'totalStats' => $totalStats,
        ]);
    }
    
    /**
     * Получить данные для таблицы
     */
    private function getTableData($type, $startDate, $endDate, $month, $daysCount, $weeksCount, $monthsCount)
    {
        $data = [];
        
        if ($type === 'day') {
            // По дням - от сегодня вниз
            $end = Carbon::parse($endDate ?: now()->format('Y-m-d'));
            $start = Carbon::parse($startDate ?: now()->subDays($daysCount - 1)->format('Y-m-d'));
            
            $current = $end->copy();
            
            while ($current >= $start) {
                $dateStr = $current->format('Y-m-d');
                $data[] = [
                    'period' => $current->format('d.m.Y'),
                    'date' => $dateStr,
                    'is_today' => $dateStr === now()->format('Y-m-d'),
                    ...$this->getDailyStats($dateStr)
                ];
                $current->subDay();
            }
            
        } elseif ($type === 'week') {
            // По неделям - от текущей недели вниз
            $end = Carbon::parse($endDate ?: now()->endOfWeek()->format('Y-m-d'));
            $start = Carbon::parse($startDate ?: now()->subWeeks($weeksCount - 1)->startOfWeek()->format('Y-m-d'));
            
            $current = $end->copy()->startOfWeek();
            
            while ($current >= $start) {
                $weekStart = $current->copy();
                $weekEnd = $current->copy()->endOfWeek();
                
                if ($weekStart < $start) {
                    $weekStart = $start->copy();
                }
                
                // Проверяем, текущая ли это неделя
                $now = Carbon::now();
                $isCurrentWeek = $now->between($weekStart, $weekEnd);
                
                $data[] = [
                    'period' => $weekStart->format('d.m') . ' - ' . $weekEnd->format('d.m.Y'),
                    'start_date' => $weekStart->format('Y-m-d'),
                    'end_date' => $weekEnd->format('Y-m-d'),
                    'is_current' => $isCurrentWeek,
                    ...$this->getPeriodStats($weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'))
                ];
                
                $current->subWeek()->startOfWeek();
            }
            
        } elseif ($type === 'month') {
            // По месяцам - от текущего месяца вниз
            $startMonth = Carbon::parse($month ?: now()->format('Y-m') . '-01');
            
            for ($i = 0; $i < $monthsCount; $i++) {
                $monthStart = $startMonth->copy()->subMonths($i)->startOfMonth();
                $monthEnd = $startMonth->copy()->subMonths($i)->endOfMonth();
                
                // Проверяем, текущий ли это месяц
                $now = Carbon::now();
                $isCurrentMonth = $now->between($monthStart, $monthEnd);
                
                $data[] = [
                    'period' => $monthStart->translatedFormat('F Y'),
                    'start_date' => $monthStart->format('Y-m-d'),
                    'end_date' => $monthEnd->format('Y-m-d'),
                    'is_current' => $isCurrentMonth,
                    ...$this->getPeriodStats($monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d'))
                ];
            }
        }
        
        return $data;
    }
    
    /**
     * Статистика за день
     */
    private function getDailyStats($date)
    {
        // Продажи за день (только завершенные)
        $sales = Sale::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->with(['items', 'hookahs'])
            ->get();
        
        // Разделяем продажи: столы и обычные
        $tableSales = $sales->filter(function($sale) {
            return !is_null($sale->table_id);
        });
        
        $nonTableSales = $sales->filter(function($sale) {
            return is_null($sale->table_id);
        });
        
        // === КАССА ===
        // Наличные
        $cashIncome = $sales->where('payment_method', 'cash')->sum('total');
        
        // Карта (включая онлайн и терминал)
        $cardIncome = $sales->whereIn('payment_method', ['card', 'online', 'terminal'])->sum('total');
        
        // === ВЫРУЧКА ===
        $totalRevenue = $sales->sum('total');
        
        // === СТОЛЫ ===
        // Гости
        $guestsCount = 0;
        foreach ($tableSales as $sale) {
            $guestsCount += $sale->guests_count ?: 1;
        }
        
        // Кальяны
        $hookahCount = $tableSales->sum(function($sale) {
            return $sale->hookahs->count();
        });
        
        // Выручка с кальянов
        $hookahIncome = $tableSales->sum(function($sale) {
            return $sale->hookahs->sum('price');
        });
        
        // Себестоимость кальянов
        $hookahCost = $tableSales->sum(function($sale) {
            return $sale->hookahs->sum('cost');
        });
        
        // Выручка с продаж привязанных к столу (БЕЗ кальянов)
        // Это ключевое изменение: total минус стоимость кальянов
        $tableProductIncome = $tableSales->sum(function($sale) {
            $hookahSum = $sale->hookahs->sum('price');
            return $sale->total - $hookahSum;
        });
        
        // Себестоимость продуктов для столов (без кальянов)
        $tableProductCost = $tableSales->sum(function($sale) {
            return $sale->items->sum('cost');
        });
        
        // Средний чек со столов
        $tableSalesCount = $tableSales->count();
        $totalTableIncome = $tableProductIncome + $hookahIncome; // Уже включает оба компонента
        $averageCheck = $tableSalesCount > 0 ? $totalTableIncome / $tableSalesCount : 0;
        
        // === ОСТАЛЬНЫЕ ПРОДАЖИ ===
        $nonTableIncome = $nonTableSales->sum('total');
        
        // Себестоимость остальных продаж
        $nonTableCost = $nonTableSales->sum(function($sale) {
            return $sale->items->sum('cost');
        });
        
        // === РАСХОДЫ ===
        // Все расходы
        $expenditures = Expenditure::whereDate('expenditure_date', $date)->sum('cost');
        
        // Штрафы
        $fines = Fine::whereDate('created_at', $date)->sum('amount');
        
        // === ПРИБЫЛЬ ===
        // Общая себестоимость
        $totalCost = $hookahCost + $tableProductCost + $nonTableCost;
        
        // Чистая прибыль (без зарплаты)
        $profit = $totalRevenue - $totalCost - $expenditures - $fines;
        
        // Проверка: общая сумма должна совпадать
        $calculatedTotal = $tableProductIncome + $hookahIncome + $nonTableIncome;
        $difference = $totalRevenue - $calculatedTotal;
        
        // Если есть разница, это могут быть скидки или другие корректировки
        // Можно добавить отладку
        
        return [
            // Касса
            'cash_total' => $cashIncome,
            'card_total' => $cardIncome,
            
            // Выручка
            'revenue' => $totalRevenue,
            
            // Столы
            'guests_count' => $guestsCount,
            'hookah_count' => $hookahCount,
            'hookah_income' => $hookahIncome,
            'table_product_income' => $tableProductIncome,
            'average_check' => round($averageCheck, 2),
            
            // Остальные продажи
            'non_table_income' => $nonTableIncome,
            
            // Расходы и прибыль
            'expenses' => $expenditures,
            'salary' => 0, // Пока пусто
            'fines' => $fines,
            'profit' => $profit,
            
            // Дополнительные данные
            'sales_count' => $sales->count(),
            'table_sales' => $tableSalesCount,
            'non_table_sales' => $nonTableSales->count(),
            'total_cost' => $totalCost,
            'hookah_cost' => $hookahCost,
            'revenue_check' => $calculatedTotal, // Для отладки
            'revenue_difference' => $difference, // Для отладки
        ];
    }
    
    /**
     * Статистика за период
     */
   private function getPeriodStats($startDate, $endDate)
    {
        // Продажи за период (только завершенные)
        $sales = Sale::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed')
            ->with(['items', 'hookahs'])
            ->get();
        
        // Разделяем продажи: столы и обычные
        $tableSales = $sales->filter(function($sale) {
            return !is_null($sale->table_id);
        });
        
        $nonTableSales = $sales->filter(function($sale) {
            return is_null($sale->table_id);
        });
        
        // === КАССА ===
        // Наличные
        $cashIncome = $sales->where('payment_method', 'cash')->sum('total');
        
        // Карта (включая онлайн и терминал)
        $cardIncome = $sales->whereIn('payment_method', ['card', 'online', 'terminal'])->sum('total');
        
        // === ВЫРУЧКА ===
        $totalRevenue = $sales->sum('total');
        
        // === СТОЛЫ ===
        // Гости
        $guestsCount = 0;
        foreach ($tableSales as $sale) {
            $guestsCount += $sale->guests_count ?: 1;
        }
        
        // Кальяны
        $hookahCount = $tableSales->sum(function($sale) {
            return $sale->hookahs->count();
        });
        
        // Выручка с кальянов
        $hookahIncome = $tableSales->sum(function($sale) {
            return $sale->hookahs->sum('price');
        });
        
        // Себестоимость кальянов
        $hookahCost = $tableSales->sum(function($sale) {
            return $sale->hookahs->sum('cost');
        });
        
        // Выручка с продаж привязанных к столу (БЕЗ кальянов)
        $tableProductIncome = $tableSales->sum(function($sale) {
            $hookahSum = $sale->hookahs->sum('price');
            return $sale->total - $hookahSum;
        });
        
        // Себестоимость продуктов для столов (без кальянов)
        $tableProductCost = $tableSales->sum(function($sale) {
            return $sale->items->sum('cost');
        });
        
        // Средний чек со столов
        $tableSalesCount = $tableSales->count();
        $totalTableIncome = $tableProductIncome + $hookahIncome;
        $averageCheck = $tableSalesCount > 0 ? $totalTableIncome / $tableSalesCount : 0;
        
        // === ОСТАЛЬНЫЕ ПРОДАЖИ ===
        $nonTableIncome = $nonTableSales->sum('total');
        
        // Себестоимость остальных продаж
        $nonTableCost = $nonTableSales->sum(function($sale) {
            return $sale->items->sum('cost');
        });
        
        // === РАСХОДЫ ===
        // Все расходы
        $expenditures = Expenditure::whereBetween('expenditure_date', [$startDate, $endDate])->sum('cost');
        
        // Штрафы
        $fines = Fine::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->sum('amount');
        
        // === ПРИБЫЛЬ ===
        // Общая себестоимость
        $totalCost = $hookahCost + $tableProductCost + $nonTableCost;
        
        // Чистая прибыль (без зарплаты)
        $profit = $totalRevenue - $totalCost - $expenditures - $fines;
        
        // Проверка суммы
        $calculatedTotal = $tableProductIncome + $hookahIncome + $nonTableIncome;
        
        return [
            // Касса
            'cash_total' => $cashIncome,
            'card_total' => $cardIncome,
            
            // Выручка
            'revenue' => $totalRevenue,
            
            // Столы
            'guests_count' => $guestsCount,
            'hookah_count' => $hookahCount,
            'hookah_income' => $hookahIncome,
            'table_product_income' => $tableProductIncome,
            'average_check' => round($averageCheck, 2),
            
            // Остальные продажи
            'non_table_income' => $nonTableIncome,
            
            // Расходы и прибыль
            'expenses' => $expenditures,
            'salary' => 0, // Пока пусто
            'fines' => $fines,
            'profit' => $profit,
            
            // Дополнительные данные
            'sales_count' => $sales->count(),
            'table_sales' => $tableSalesCount,
            'non_table_sales' => $nonTableSales->count(),
            'total_cost' => $totalCost,
            'hookah_cost' => $hookahCost,
            'revenue_check' => $calculatedTotal,
            'revenue_difference' => $totalRevenue - $calculatedTotal,
        ];
    }
    
    /**
     * Общая статистика за все время
     */
    private function getTotalStats()
    {
        // Все завершенные продажи
        $sales = Sale::where('status', 'completed')->get();
        
        // Наличные
        $cashIncome = $sales->where('payment_method', 'cash')->sum('total');
        
        // Карта
        $cardIncome = $sales->whereIn('payment_method', ['card', 'online', 'terminal'])->sum('total');
        
        // Все расходы
        $expenditures = Expenditure::sum('cost');
        
        // Все штрафы
        $fines = Fine::sum('amount');
        
        // Общая выручка
        $totalRevenue = $sales->sum('total');
        
        // Прибыль (без учета себестоимости для общей статистики)
        $profit = $totalRevenue - $expenditures - $fines;
        
        return [
            'cash_income' => $cashIncome,
            'card_income' => $cardIncome,
            'total_income' => $totalRevenue,
            'expenses' => $expenditures,
            'fines' => $fines,
            'profit' => $profit,
            'sales_count' => $sales->count(),
        ];
    }
    
    /**
     * Детальная статистика по кальянам
     */
    public function hookahStats(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        // Определяем период
        if ($startDate && $endDate) {
            $sales = Sale::with(['hookahs'])
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->where('status', 'completed')
                ->whereNotNull('table_id')
                ->get();
            $periodText = Carbon::parse($startDate)->format('d.m.Y') . ' - ' . Carbon::parse($endDate)->format('d.m.Y');
        } else {
            $sales = Sale::with(['hookahs'])
                ->whereDate('created_at', $date)
                ->where('status', 'completed')
                ->whereNotNull('table_id')
                ->get();
            $periodText = Carbon::parse($date)->format('d.m.Y');
        }
        
        // Собираем статистику по кальянам
        $hookahStats = [];
        foreach ($sales as $sale) {
            foreach ($sale->hookahs as $hookah) {
                $hookahId = $hookah->id;
                if (!isset($hookahStats[$hookahId])) {
                    $hookahStats[$hookahId] = [
                        'id' => $hookahId,
                        'name' => $hookah->name,
                        'count' => 0,
                        'total_amount' => 0,
                        'avg_amount' => 0,
                    ];
                }
                $hookahStats[$hookahId]['count']++;
                $hookahStats[$hookahId]['total_amount'] += $hookah->price;
            }
        }
        
        // Рассчитываем среднюю сумму
        foreach ($hookahStats as &$stat) {
            $stat['avg_amount'] = $stat['count'] > 0 ? $stat['total_amount'] / $stat['count'] : 0;
        }
        
        // Получаем все кальяны для сравнения
        $allHookahs = Hookah::orderBy('name')->get();
        
        // Добавляем кальяны, которые не продавались
        foreach ($allHookahs as $hookah) {
            if (!isset($hookahStats[$hookah->id])) {
                $hookahStats[$hookah->id] = [
                    'id' => $hookah->id,
                    'name' => $hookah->name,
                    'count' => 0,
                    'total_amount' => 0,
                    'avg_amount' => 0,
                ];
            }
        }
        
        // Преобразуем в массив и сортируем
        $hookahStats = array_values($hookahStats);
        usort($hookahStats, function($a, $b) {
            return $a['name'] <=> $b['name'];
        });
        
        return view('accounting.hookah-stats', [
            'stats' => $hookahStats,
            'date' => $date,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'periodText' => $periodText,
            'total_count' => collect($hookahStats)->sum('count'),
            'total_amount' => collect($hookahStats)->sum('total_amount'),
        ]);
    }
    
    /**
     * Статистика по способам оплаты
     */
    public function paymentStats(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        if ($startDate && $endDate) {
            $sales = Sale::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->where('status', 'completed')
                ->get();
            $periodText = Carbon::parse($startDate)->format('d.m.Y') . ' - ' . Carbon::parse($endDate)->format('d.m.Y');
        } else {
            $sales = Sale::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->get();
            $periodText = Carbon::parse($date)->format('d.m.Y');
        }
        
        // Группируем по способам оплаты
        $paymentStats = [
            'cash' => [
                'name' => 'Наличные',
                'count' => 0,
                'amount' => 0,
            ],
            'card' => [
                'name' => 'Карта',
                'count' => 0,
                'amount' => 0,
            ],
            'online' => [
                'name' => 'Онлайн',
                'count' => 0,
                'amount' => 0,
            ],
            'terminal' => [
                'name' => 'Терминал',
                'count' => 0,
                'amount' => 0,
            ],
        ];
        
        foreach ($sales as $sale) {
            $method = $sale->payment_method;
            if (isset($paymentStats[$method])) {
                $paymentStats[$method]['count']++;
                $paymentStats[$method]['amount'] += $sale->total;
            }
        }
        
        // Убираем пустые методы
        $paymentStats = array_filter($paymentStats, function($stat) {
            return $stat['count'] > 0;
        });
        
        return view('accounting.payment-stats', [
            'stats' => $paymentStats,
            'date' => $date,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'periodText' => $periodText,
            'total_count' => $sales->count(),
            'total_amount' => $sales->sum('total'),
        ]);
    }
    
    /**
     * Статистика по бонусам
     */
    public function bonusStats(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        if ($startDate && $endDate) {
            $sales = Sale::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->where('status', 'completed')
                ->where('used_bonus_points', '>', 0)
                ->with('client')
                ->get();
            $periodText = Carbon::parse($startDate)->format('d.m.Y') . ' - ' . Carbon::parse($endDate)->format('d.m.Y');
        } else {
            $sales = Sale::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->where('used_bonus_points', '>', 0)
                ->with('client')
                ->get();
            $periodText = Carbon::parse($date)->format('d.m.Y');
        }
        
        // Общая статистика
        $totalUsedBonuses = $sales->sum('used_bonus_points');
        $totalSalesWithBonuses = $sales->count();
        $totalSalesAmount = $sales->sum('total');
        
        // Статистика по клиентам
        $clientStats = [];
        foreach ($sales as $sale) {
            if ($sale->client) {
                $clientId = $sale->client->id;
                if (!isset($clientStats[$clientId])) {
                    $clientStats[$clientId] = [
                        'client' => $sale->client,
                        'sales_count' => 0,
                        'total_amount' => 0,
                        'used_bonuses' => 0,
                    ];
                }
                $clientStats[$clientId]['sales_count']++;
                $clientStats[$clientId]['total_amount'] += $sale->total;
                $clientStats[$clientId]['used_bonuses'] += $sale->used_bonus_points;
            }
        }
        
        return view('accounting.bonus-stats', [
            'sales' => $sales,
            'date' => $date,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'periodText' => $periodText,
            'totalUsedBonuses' => $totalUsedBonuses,
            'totalSalesWithBonuses' => $totalSalesWithBonuses,
            'totalSalesAmount' => $totalSalesAmount,
            'clientStats' => array_values($clientStats),
        ]);
    }
    
    /**
     * Экспорт данных
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'day');
        $format = $request->get('format', 'csv');
        
        if ($type === 'day') {
            $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));
            $data = $this->getDailyReport($startDate, $endDate);
            $filename = 'отчет_по_дням_' . $startDate . '_' . $endDate . '.' . $format;
        } elseif ($type === 'week') {
            $startDate = $request->get('start_date', now()->subWeeks(8)->startOfWeek()->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->endOfWeek()->format('Y-m-d'));
            $data = $this->getPeriodReport($startDate, $endDate);
            $filename = 'отчет_по_неделям_' . $startDate . '_' . $endDate . '.' . $format;
        } else {
            $month = $request->get('month', now()->format('Y-m'));
            $startDate = Carbon::parse($month)->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::parse($month)->endOfMonth()->format('Y-m-d');
            $data = $this->getPeriodReport($startDate, $endDate);
            $filename = 'отчет_по_месяцам_' . $month . '.' . $format;
        }
        
        if ($format === 'csv') {
            return $this->exportToCsv($data, $filename);
        } else {
            return response()->json([
                'message' => 'PDF экспорт пока не реализован',
                'data' => $data
            ]);
        }
    }
    
    /**
     * Экспорт в CSV
     */
    private function exportToCsv($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Заголовки
            fputcsv($file, [
                'Период',
                'Наличные',
                'Карта',
                'Выручка',
                'Гости',
                'Кальяны',
                'С кальянов',
                'С продаж',
                'Средний чек',
                'Остальные продажи',
                'Расходы',
                'Зарплата',
                'Штрафы',
                'Прибыль'
            ]);
            
            // Данные
            foreach ($data as $row) {
                fputcsv($file, [
                    $row['period'],
                    $row['cash_total'],
                    $row['card_total'],
                    $row['revenue'],
                    $row['guests_count'],
                    $row['hookah_count'],
                    $row['hookah_income'],
                    $row['table_product_income'],
                    $row['average_check'],
                    $row['non_table_income'],
                    $row['expenses'],
                    0, // Зарплата пока пустая
                    $row['fines'],
                    $row['profit']
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Отчет по дням для экспорта
     */
    private function getDailyReport($startDate, $endDate)
    {
        $data = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        $current = $start->copy();
        
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $stats = $this->getDailyStats($dateStr);
            
            $data[] = [
                'period' => $current->format('d.m.Y'),
                'date' => $dateStr,
                ...$stats
            ];
            
            $current->addDay();
        }
        
        return $data;
    }
    
    /**
     * Отчет по периоду для экспорта
     */
    private function getPeriodReport($startDate, $endDate)
    {
        return [
            [
                'period' => Carbon::parse($startDate)->format('d.m.Y') . ' - ' . Carbon::parse($endDate)->format('d.m.Y'),
                ...$this->getPeriodStats($startDate, $endDate)
            ]
        ];
    }
    
    /**
     * Детальный отчет по себестоимости
     */
    public function costReport(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        if ($startDate && $endDate) {
            $sales = Sale::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->where('status', 'completed')
                ->with(['items', 'hookahs'])
                ->get();
            $periodText = Carbon::parse($startDate)->format('d.m.Y') . ' - ' . Carbon::parse($endDate)->format('d.m.Y');
        } else {
            $sales = Sale::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->with(['items', 'hookahs'])
                ->get();
            $periodText = Carbon::parse($date)->format('d.m.Y');
        }
        
        $totalRevenue = $sales->sum('total');
        $totalCost = 0;
        
        // Собираем данные по себестоимости
        $costDetails = [
            'hookahs' => [
                'name' => 'Кальяны',
                'revenue' => 0,
                'cost' => 0,
                'profit' => 0,
            ],
            'table_products' => [
                'name' => 'Продукты для столов',
                'revenue' => 0,
                'cost' => 0,
                'profit' => 0,
            ],
            'non_table_products' => [
                'name' => 'Остальные продукты',
                'revenue' => 0,
                'cost' => 0,
                'profit' => 0,
            ],
        ];
        
        foreach ($sales as $sale) {
            if (!is_null($sale->table_id)) {
                // Продажи со столами
                $costDetails['hookahs']['revenue'] += $sale->hookahs->sum('price');
                $costDetails['hookahs']['cost'] += $sale->hookahs->sum('cost');
                
                $costDetails['table_products']['revenue'] += $sale->total;
                $costDetails['table_products']['cost'] += $sale->items->sum('cost');
            } else {
                // Продажи без столов
                $costDetails['non_table_products']['revenue'] += $sale->total;
                $costDetails['non_table_products']['cost'] += $sale->items->sum('cost');
            }
        }
        
        // Рассчитываем прибыль
        foreach ($costDetails as &$detail) {
            $detail['profit'] = $detail['revenue'] - $detail['cost'];
            $totalCost += $detail['cost'];
        }
        
        // Общая прибыль
        $totalProfit = $totalRevenue - $totalCost;
        
        return view('accounting.cost-report', [
            'costDetails' => $costDetails,
            'totalRevenue' => $totalRevenue,
            'totalCost' => $totalCost,
            'totalProfit' => $totalProfit,
            'date' => $date,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'periodText' => $periodText,
        ]);
    }
}