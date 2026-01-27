<?php

namespace App\Http\Controllers;

use App\Models\Expenditure;
use App\Models\ExpenditureType;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class ExpenditureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenditures = Expenditure::with(['expenditureType', 'paymentMethod'])->latest()->get();
        $expenditureTypes = ExpenditureType::all();
        $paymentMethods = PaymentMethod::all(); // Получаем все методы оплаты
        
        // Статистика для отображения
        $totalAmount = $expenditures->sum('cost');
        $cashAmount = $expenditures->where('paymentMethod.Name', 'Наличные')->sum('cost');
        $cardAmount = $expenditures->where('paymentMethod.Name', 'Карта')->sum('cost');
        $monthlyAmount = $expenditures->where('is_monthly_expense', true)->sum('cost');
        
        return view('expenditures.index', compact(
            'expenditures', 
            'expenditureTypes',
            'paymentMethods', // Передаем в шаблон
            'totalAmount',
            'cashAmount',
            'cardAmount',
            'monthlyAmount'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'expenditure_type_id' => 'required|exists:expenditure_types,id',
            'payment_method_id' => 'required|exists:payment_methods,IDPaymentMethod', // Изменено
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'comment' => 'nullable|string|max:1000',
            'expenditure_date' => 'required|date',
            'is_hidden_admin' => 'sometimes|boolean',
            'is_monthly_expense' => 'sometimes|boolean',
        ]);

        $validated['is_hidden_admin'] = $request->boolean('is_hidden_admin');
        $validated['is_monthly_expense'] = $request->boolean('is_monthly_expense');

        // Автоматическое логирование через трейт
        Expenditure::create($validated);

        return redirect()->route('expenditures.index')
            ->with('success', 'Расход успешно добавлен!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expenditure $expenditure)
    {
        $validated = $request->validate([
            'expenditure_type_id' => 'required|exists:expenditure_types,id',
            'payment_method_id' => 'required|exists:payment_methods,IDPaymentMethod', // Изменено
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'comment' => 'nullable|string|max:1000',
            'expenditure_date' => 'required|date',
            'is_hidden_admin' => 'sometimes|boolean',
            'is_monthly_expense' => 'sometimes|boolean',
        ]);

        $validated['is_hidden_admin'] = $request->boolean('is_hidden_admin');
        $validated['is_monthly_expense'] = $request->boolean('is_monthly_expense');

        // Автоматическое логирование через трейт
        $expenditure->update($validated);

        return redirect()->route('expenditures.index')
            ->with('success', 'Расход успешно обновлён!');
    }

    /**
     * Remove the specified resource from storage with comment.
     */
    public function destroy(Request $request, Expenditure $expenditure)
    {
        $request->validate([
            'delete_comment' => 'required|string|min:5|max:500',
        ]);

        // Устанавливаем комментарий для удаления
        $expenditure->setDeleteComment(
            $request->delete_comment . " (Удален расход: {$expenditure->name}, сумма: {$expenditure->cost} руб.)"
        );
        
        // Автоматическое логирование через трейт (с комментарием)
        $expenditure->delete();

        return redirect()->route('expenditures.index')
            ->with('success', 'Расход успешно удалён!');
    }
    
    // ... остальные методы (create, show, edit, confirmDelete) остаются без изменений
    
    /**
     * Быстрое удаление без комментария (для AJAX запросов).
     */
    public function quickDestroy(Expenditure $expenditure)
    {
        // Устанавливаем системный комментарий для быстрого удаления
        $expenditure->setDeleteComment('Быстрое удаление без комментария');
        
        $expenditure->delete();

        return response()->json([
            'success' => true,
            'message' => 'Расход удален'
        ]);
    }

    /**
     * Получить все методы оплаты (для AJAX или API).
     */
    public function getPaymentMethods()
    {
        $paymentMethods = PaymentMethod::all();
        return response()->json($paymentMethods);
    }
}