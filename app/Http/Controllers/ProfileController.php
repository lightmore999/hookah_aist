<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Показать профиль текущего пользователя
     */
    public function show()
    {
        $user = Auth::user();

        return view('profile.show', compact('user'));
    }

    /**
     * Показать форму редактирования профиля
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Обновить профиль пользователя
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'social_network' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'inn' => 'nullable|string|max:12',
            'current_password' => 'nullable|required_with:password|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'social_network' => $request->social_network,
            'phone' => $request->phone,
            'inn' => $request->inn,
        ];

        // Если пользователь хочет изменить пароль
        if ($request->filled('password')) {
            // Проверяем текущий пароль
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Текущий пароль неверен']);
            }
            
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('profile.show')
            ->with('success', 'Профиль успешно обновлен');
    }
}