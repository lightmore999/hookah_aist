<?php

namespace App\Http\Controllers;

use App\Models\OperationHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationHistoryController extends Controller
{
    /**
     * Display a listing of all operations.
     */
    public function index(Request $request)
    {
        $query = OperationHistory::with('user')
            ->orderBy('created_at', 'desc');

        // Фильтры
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('entity_type', 'like', "%{$search}%")
                ->orWhere('action_type', 'like', "%{$search}%")
                ->orWhere('comment', 'like', "%{$search}%")
                ->orWhere('entity_id', 'like', "%{$search}%");
            });
        }

        // Статистика
        $stats = $this->getStatistics($request);

        // Пагинация
        $history = $query->paginate(50)->withQueryString();

        // Типы действий для фильтра
        $actionTypes = [
            OperationHistory::ACTION_CREATE => 'Создание',
            OperationHistory::ACTION_UPDATE => 'Изменение',
            OperationHistory::ACTION_DELETE => 'Удаление',
            OperationHistory::ACTION_CLOSE => 'Закрытие',
            OperationHistory::ACTION_OPEN => 'Открытие',
            OperationHistory::ACTION_ADD_HOOKAH => 'Добавление кальяна',
            OperationHistory::ACTION_REMOVE_HOOKAH => 'Удаление кальяна',
        ];

        // Типы сущностей для фильтра
        $entityTypes = [
            OperationHistory::ENTITY_TABLE => 'Столы',
            OperationHistory::ENTITY_SALE => 'Продажи',
            OperationHistory::ENTITY_EXPENSE => 'Расходы',
            OperationHistory::ENTITY_HOOKAH => 'Кальяны',
        ];

        // Пользователи для фильтра
        $users = \App\Models\User::orderBy('name')->get();

        $stats = $this->getStatistics($request);

        return view('operation-history.index', compact(
            'history',
            'stats', // добавляем эту строку
            'actionTypes',
            'entityTypes',
            'users'
        ));
    }

    /**
     * Display operation details.
     */
    public function show(OperationHistory $operationHistory)
    {
        $operationHistory->load('user');
        
        // Получаем связанную сущность если она еще существует
        $entity = null;
        if ($operationHistory->action_type !== OperationHistory::ACTION_DELETE) {
            try {
                $modelClass = $this->getModelClass($operationHistory->entity_type);
                if (class_exists($modelClass)) {
                    $entity = $modelClass::find($operationHistory->entity_id);
                }
            } catch (\Exception $e) {
                // Игнорируем ошибки при поиске удаленных сущностей
            }
        }

        return view('operation-history.show', compact('operationHistory', 'entity'));
    }

    /**
     * Get statistics for the current filter.
     */
    private function getStatistics(Request $request): array
    {
        $query = OperationHistory::query();

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Общее количество операций
        $totalOperations = $query->count();

        // Операции по типам действий
        $actionsByType = $query->clone()
            ->select('action_type', DB::raw('count(*) as count'))
            ->groupBy('action_type')
            ->pluck('count', 'action_type')
            ->toArray();

        // Операции по типам сущностей
        $entitiesByType = $query->clone()
            ->select('entity_type', DB::raw('count(*) as count'))
            ->groupBy('entity_type')
            ->pluck('count', 'entity_type')
            ->toArray();

        // Самые активные пользователи
        $topUsers = $query->clone()
            ->select('user_id', DB::raw('count(*) as count'))
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // Операции по дням (последние 7 дней)
        $operationsByDay = $query->clone()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total_operations' => $totalOperations,
            'actions_by_type' => $actionsByType,
            'entities_by_type' => $entitiesByType,
            'top_users' => $topUsers,
            'operations_by_day' => $operationsByDay,
        ];
    }

    /**
     * Get model class by entity type.
     */
    private function getModelClass(string $entityType): ?string
    {
        $mapping = [
            OperationHistory::ENTITY_TABLE => \App\Models\Table::class,
            OperationHistory::ENTITY_SALE => \App\Models\Sale::class,
            OperationHistory::ENTITY_EXPENSE => \App\Models\Expenditure::class,
            OperationHistory::ENTITY_HOOKAH => \App\Models\Hookah::class,
        ];

        return $mapping[$entityType] ?? null;
    }
}