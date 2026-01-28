<?php

namespace App\Http\Controllers;

use App\Models\TableName;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Отображение страницы настроек со всеми разделами
     */
    public function index()
    {
        // Получаем данные для обоих разделов
        $tables = TableName::ordered()->get();
        $paymentMethods = PaymentMethod::orderBy('created_at')->get();
        
        return view('settings.index', [
            'tables' => $tables,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}