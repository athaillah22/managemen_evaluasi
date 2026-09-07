<?php

namespace App\Filament\Pages\Auth;

use Filament\Http\Responses\Auth\Contracts\LoginResponse;   // ← import WAJIB
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected function getCredentials(): array
    {
        $data = $this->form->getState();

        return [
            'email'     => $data['email'],
            'password'  => $data['password'],
            'is_active' => true,   // akun nonaktif tidak bisa login
        ];
    }

    /**
     * Rate limiting: maksimal 5 percobaan login per menit per IP.
     * Mencegah serangan brute-force (Issue #2).
     */
    public function authenticate(): ?LoginResponse          // ← return type disesuaikan
    {
        $key = 'login-attempts:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'data.email' => "Too Many Requests: terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        RateLimiter::hit($key, 60);

        $result = parent::authenticate();

        // Login sukses → reset penghitung
        if (auth()->check()) {
            RateLimiter::clear($key);
        }

        return $result;
    }
}