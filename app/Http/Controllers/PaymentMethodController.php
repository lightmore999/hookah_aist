<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of payment methods
     */
    public function index()
    {
        $paymentMethods = PaymentMethod::orderBy('Name')->get();
        return redirect()->route('settings.index');
    }

    /**
     * Show the form for creating a new payment method
     */
    public function create()
    {
        return redirect()->route('settings.index');
    }

    /**
     * Store a newly created payment method
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Name' => 'required|string|max:100|unique:payment_methods,Name'
        ]);

        PaymentMethod::create($validated);

        return redirect()->route('payment-methods.index')
            ->with('success', 'Способ оплаты успешно создан!');
    }

    /**
     * Display the specified payment method
     */
    public function show(PaymentMethod $paymentMethod)
    {
        return view('payment-methods.show', compact('paymentMethod'));
    }

    /**
     * Show the form for editing the specified payment method
     */
    public function edit(PaymentMethod $paymentMethod)
    {
        return view('payment-methods.edit', compact('paymentMethod'));
    }

    /**
     * Update the specified payment method
     */
    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'Name' => 'required|string|max:100|unique:payment_methods,Name,' . $paymentMethod->IDPaymentMethod . ',IDPaymentMethod'
        ]);

        $paymentMethod->update($validated);

        return redirect()->route('payment-methods.index')
            ->with('success', 'Способ оплаты успешно обновлен!');
    }

    /**
     * Remove the specified payment method
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return redirect()->route('payment-methods.index')
            ->with('success', 'Способ оплаты успешно удален!');
    }
}