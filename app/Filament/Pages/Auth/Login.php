<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';
    protected static string $layout = 'filament-panels::components.layout.base';

    public function getHeading(): string | Htmlable
    {
        return 'Masuk ke Akun Anda';
    }

    public function getSubHeading(): string | Htmlable | null
    {
        return 'Portal internal manajemen & operasional ToHelp SerbaBisa';
    }

    public function mount(): void
    {
        if (! Filament::getCurrentPanel()) {
            Filament::setCurrentPanel(Filament::getPanel('admin'));
        }

        if (Filament::auth()->check()) {
            $user = Filament::auth()->user();
            if ($user && $user->hasAnyRole(['super_admin', 'manager_cabang'])) {
                redirect()->to('/admin');
                return;
            }
            if ($user && $user->hasRole('karyawan')) {
                redirect()->to('/karyawan');
                return;
            }
            redirect()->to('/');
            return;
        }

        $this->form->fill();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Nama Pengguna')
            ->placeholder('Masukkan username atau email')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata Sandi')
            ->placeholder('Masukkan kata sandi')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('Ingat saya');
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $loginInput = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $remember = $data['remember'] ?? false;

        // Search user by username OR email
        $user = User::where(function ($query) use ($loginInput) {
            $query->where('email', $loginInput)
                  ->orWhere('username', $loginInput);
        })->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            $this->throwFailureValidationException();
        }

        // Authenticate user in session
        Filament::auth()->login($user, $remember);

        session()->regenerate();

        // Redirect dynamically based on user role
        return new class($user) implements LoginResponse {
            public function __construct(private User $user) {}

            public function toResponse($request)
            {
                if ($this->user->hasAnyRole(['super_admin', 'manager_cabang'])) {
                    return redirect()->to('/admin');
                }

                if ($this->user->hasRole('karyawan')) {
                    return redirect()->to('/karyawan');
                }

                return redirect()->to('/');
            }
        };
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => 'Nama Pengguna / Email atau Kata Sandi yang Anda masukkan salah.',
        ]);
    }
}
