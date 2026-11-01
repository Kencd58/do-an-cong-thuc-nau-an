<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function register(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);

        try {
            $data = $request->validate([
                'name' => ['required', 'string', 'min:2', 'max:100'],
                'email' => ['required', 'email', 'max:254', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ], ['email.unique' => 'Email này đã được sử dụng.']);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
        } catch (QueryException $exception) {
            // Only the email index collision is a validation error.
            if (($exception->errorInfo[1] ?? null) === 1062
                && str_contains($exception->errorInfo[2] ?? '', 'users_email_unique')) {
                throw ValidationException::withMessages(['email' => 'Email này đã được sử dụng.']);
            }

            return $this->databaseError($request, $exception, ['name', 'email']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($this->homeUrl())->with('success', 'Đăng ký thành công. Chào mừng bạn đến với Bếp Nhà!');
    }

    public function login(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'string'],
        ]);

        try {
            $authenticated = Auth::attempt($credentials);
        } catch (QueryException $exception) {
            return $this->databaseError($request, $exception, ['email']);
        }

        if (! $authenticated) {
            throw ValidationException::withMessages(['email' => 'Thông tin đăng nhập không đúng']);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->homeUrl())->with('success', 'Đăng nhập thành công.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($this->homeUrl())->with('success', 'Bạn đã đăng xuất.');
    }

    private function normalizeEmail(Request $request): void
    {
        // TrimStrings trims names and excludes both password fields.
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }
    }

    private function homeUrl(): string
    {
        return route('home');
    }

    private function databaseError(Request $request, QueryException $exception, array $fields): RedirectResponse
    {
        // Exception messages/bindings can contain account data; log codes only.
        Log::error('Auth database operation failed.', [
            'sqlState' => $exception->errorInfo[0] ?? null,
            'driverCode' => $exception->errorInfo[1] ?? null,
        ]);

        return back()->withInput($request->only($fields))
            ->with('error', 'Chưa thể xử lý yêu cầu. Vui lòng thử lại sau.');
    }
}
