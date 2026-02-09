<?php

namespace App\Http\Controllers;

use App\Models\TableName;
use App\Models\PaymentMethod;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the unified settings page with tabs.
     */
    public function index(): View
    {
        // Get data for both tabs
        $tables = TableName::ordered()->get();
        $paymentMethods = PaymentMethod::orderBy('created_at')->get();

        // Determine which tab should be active
        $activeTab = session('active_tab', 'tables');

        return view('settings.index', [
            'tables' => $tables,
            'paymentMethods' => $paymentMethods,
            'activeTab' => $activeTab
        ]);
    }

    /**
     * Get tables data for AJAX requests.
     */
    public function getTablesData()
    {
        $tables = TableName::ordered()->get();
        
        return response()->json([
            'success' => true,
            'tables' => $tables,
            'html' => view('settings.partials.tables-content', compact('tables'))->render()
        ]);
    }

    /**
     * Get payment methods data for AJAX requests.
     */
    public function getPaymentMethodsData()
    {
        $paymentMethods = PaymentMethod::orderBy('created_at')->get();
        
        return response()->json([
            'success' => true,
            'paymentMethods' => $paymentMethods,
            'html' => view('settings.partials.payment-methods-content', compact('paymentMethods'))->render()
        ]);
    }
}