<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TableName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableNameController extends Controller
{
    /**
     * Показать список столов
     */
    public function index()
    {
        $tables = TableName::ordered()->get();
        
        // Если это AJAX запрос от loadTableList(), возвращаем только partial
        if (request()->ajax() && request()->header('X-Requested-With') == 'XMLHttpRequest') {
            return view('admin.table-names.partials.table-list', compact('tables'))->render();
        }
        
        return view('admin.table-names.index', compact('tables'));
    }

    /**
     * Создать новый стол
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:table_names,name',
        ]);

        $maxOrder = TableName::max('sort_order') ?? 0;

        TableName::create([
            'name' => $request->name,
            'is_active' => true,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('admin.table-names.index')
            ->with('success', 'Стол успешно добавлен');
    }

    public function updateStatus(Request $request, TableName $table)
    {
        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $table->update([
            'is_active' => $request->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Статус обновлен',
            'table' => $table
        ]);
    }

    public function destroy(TableName $table)
    {
        // Проверяем, есть ли активные бронирования на этот стол
        $hasBookings = \App\Models\Table::where('table_number', $table->name)
            ->whereDate('booking_date', '>=', now()->toDateString())
            ->exists();

        if ($hasBookings) {
            return redirect()->route('admin.table-names.index')
                ->with('error', 'Нельзя удалить стол, на него есть активные бронирования');
        }

        $table->delete();

        return redirect()->route('admin.table-names.index')
            ->with('success', 'Стол удален');
    }

    /**
     * Обновить порядок столов
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:table_names,id'
        ]);

        foreach ($request->order as $index => $tableId) {
            TableName::where('id', $tableId)->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Порядок обновлен'
        ]);

    }
}