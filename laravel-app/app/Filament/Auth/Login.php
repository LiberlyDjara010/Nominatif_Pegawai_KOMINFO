<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as FilamentLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class Login extends FilamentLogin
{
    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                $this->getUsernameFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->required()
            ->autofocus()
            ->autocomplete();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => __('Username atau Password salah.'),
        ]);
    }

    public function getTitle(): \Illuminate\Contracts\Support\Htmlable|string
    {
        return __('Masuk');
    }

    public function getHeading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        return __('Silakan masuk untuk melanjutkan');
    }
}
