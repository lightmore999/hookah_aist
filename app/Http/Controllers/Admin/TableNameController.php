<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TableName;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TableNameController extends Controller
{
    /**
     * Display a listing of tables.
     * Теперь редиректим на единую страницу настроек
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    public function update(Request $request, TableName $table): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Метод не поддерживается. Используйте updateStatus или updateOrder'
        ], 405);
    }

    /**
     * Store a newly created table.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:table_names,name',
        ]);

        // Определяем следующий порядковый номер
        $maxOrder = TableName::max('sort_order') ?? 0;

        TableName::create([
            'name' => $validated['name'],
            'is_active' => true,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('settings.index')
            ->with('success_tables', 'Стол успешно добавлен')
            ->with('active_tab', 'tables');
    }

    /**
     * Update table status (active/inactive).
     */
    public function updateStatus(Request $request, TableName $table): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean'
        ]);

        try {
            $table->update(['is_active' => $validated['is_active']]);

            return response()->json([
                'success' => true,
                'message' => 'Статус стола обновлен',
                'table' => [
                    'id' => $table->id,
                    'name' => $table->name,
                    'is_active' => $table->is_active
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении статуса: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update tables order.
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'required|integer|exists:table_names,id'
        ]);

        foreach ($validated['order'] as $index => $tableId) {
            TableName::where('id', $tableId)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Порядок столов обновлен',
            'order' => $validated['order']
        ]);
    }

    /**
     * Remove the specified table.
     */
    public function destroy($id): RedirectResponse
    {
        // Находим стол напрямую по ID
        $table = TableName::findOrFail($id);
        
        $tableName = $table->name;
        $table->delete();
        
        return redirect()
            ->route('settings.index')
            ->with('success_tables', 'Стол "' . $tableName . '" успешно удален')
            ->with('active_tab', 'tables');
    }

    /**
     * For AJAX requests - get tables list partial.
     */
    public function getTablesPartial(): string
    {
        $tables = TableName::ordered()->get();
        return view('settings.partials.tables-list', compact('tables'))->render();
    }
}