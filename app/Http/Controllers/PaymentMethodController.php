<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of payment methods.
     * Теперь редиректим на единую страницу настроек
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    /**
     * Store a newly created payment method.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'Name' => 'required|string|max:100|unique:payment_methods,Name'
        ], [
            'Name.required' => 'Название способа оплаты обязательно для заполнения',
            'Name.max' => 'Название не должно превышать 100 символов',
            'Name.unique' => 'Способ оплаты с таким названием уже существует'
        ]);

        try {
            PaymentMethod::create($validated);

            return redirect()
                ->route('settings.index')
                ->with('success_payment', 'Способ оплаты "' . $validated['Name'] . '" успешно создан')
                ->with('active_tab', 'payment-methods');

        } catch (\Exception $e) {
            return redirect()
                ->route('settings.index')
                ->with('error_payment', 'Ошибка при создании способа оплаты: ' . $e->getMessage())
                ->with('active_tab', 'payment-methods');
        }
    }

    /**
     * Update the specified payment method.
     */
    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $validated = $request->validate([
            'Name' => 'required|string|max:100|unique:payment_methods,Name,' . $paymentMethod->IDPaymentMethod . ',IDPaymentMethod'
        ], [
            'Name.required' => 'Название способа оплаты обязательно для заполнения',
            'Name.max' => 'Название не должно превышать 100 символов',
            'Name.unique' => 'Способ оплаты с таким названием уже существует'
        ]);

        try {
            $oldName = $paymentMethod->Name;
            $paymentMethod->update($validated);

            return redirect()
                ->route('settings.index')
                ->with('success_payment', 'Способ оплаты "' . $oldName . '" обновлен на "' . $validated['Name'] . '"')
                ->with('active_tab', 'payment-methods');

        } catch (\Exception $e) {
            return redirect()
                ->route('settings.index')
                ->with('error_payment', 'Ошибка при обновлении способа оплаты: ' . $e->getMessage())
                ->with('active_tab', 'payment-methods');
        }
    }

    /**
     * Remove the specified payment method.
     */
    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        try {
            $paymentMethodName = $paymentMethod->Name;
            $paymentMethod->delete();

            return redirect()
                ->route('settings.index')
                ->with('success_payment', 'Способ оплаты "' . $paymentMethodName . '" успешно удален')
                ->with('active_tab', 'payment-methods');

        } catch (\Exception $e) {
            return redirect()
                ->route('settings.index')
                ->with('error_payment', 'Ошибка при удалении способа оплаты: ' . $e->getMessage())
                ->with('active_tab', 'payment-methods');
        }
    }

    /**
     * For compatibility - show method (not used in new design)
     */
    public function show(PaymentMethod $paymentMethod): RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    /**
     * For compatibility - edit method (not used in new design)
     */
    public function edit(PaymentMethod $paymentMethod): RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    /**
     * For compatibility - create method (not used in new design)
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('settings.index');
    }
}