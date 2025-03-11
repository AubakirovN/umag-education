<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\PhoneRole;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $credentials = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'string', 'unique:users', 'email'],
            'password' => [
                'required', 
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
            'phone' => ['required']
        ]);

        $phoneRole = PhoneRole::where('phone', $credentials['phone'])->first();

        if (!$phoneRole) {
            return response()->json([
                'message' => 'Номер телефона не найден.'
            ]);
        }

        $user = User::create([
            'name' => $credentials['name'],
            'email' => $credentials['email'],
            'password' => Hash::make($credentials['password']),
            'role' => $phoneRole->role
        ]);

        return response()->json([
            'message' => 'Пользователь зарегистрирован.'
        ]);
    }
}
