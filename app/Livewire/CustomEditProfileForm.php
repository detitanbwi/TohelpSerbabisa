<?php

namespace App\Livewire;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Joaopaulolndev\FilamentEditProfile\Livewire\EditProfileForm as BaseEditProfileForm;

class CustomEditProfileForm extends BaseEditProfileForm
{
    public function mount(): void
    {
        $this->user = $this->getUser();
        $this->userClass = get_class($this->user);
        $this->form->fill($this->user->only(config('filament-edit-profile.avatar_column', 'avatar_url'), 'name', 'username', 'email'));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('filament-edit-profile::default.profile_information'))
                    ->aside()
                    ->description(__('filament-edit-profile::default.profile_information_description'))
                    ->schema([
                        FileUpload::make(config('filament-edit-profile.avatar_column', 'avatar_url'))
                            ->label(__('filament-edit-profile::default.avatar'))
                            ->avatar()
                            ->imageEditor()
                            ->disk(config('filament-edit-profile.disk', 'public'))
                            ->visibility(config('filament-edit-profile.visibility', 'public'))
                            ->directory(filament('filament-edit-profile')->getAvatarDirectory())
                            ->rules(filament('filament-edit-profile')->getAvatarRules())
                            ->hidden(! filament('filament-edit-profile')->getShouldShowAvatarForm()),
                        TextInput::make('name')
                            ->label(__('filament-edit-profile::default.name'))
                            ->required(),
                        TextInput::make('username')
                            ->label('Username Unik')
                            ->placeholder('Contoh: andiganteng01 atau 001_ANDI')
                            ->unique($this->userClass, ignorable: $this->user)
                            ->required(),
                        TextInput::make('email')
                            ->label(__('filament-edit-profile::default.email') . ' (Opsional)')
                            ->email()
                            ->nullable()
                            ->unique($this->userClass, ignorable: $this->user),
                    ]),
            ])
            ->statePath('data');
    }
}
