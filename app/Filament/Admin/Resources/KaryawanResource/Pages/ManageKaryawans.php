<?php

namespace App\Filament\Admin\Resources\KaryawanResource\Pages;

use Exception;
use App\Models\User;
use Filament\Actions;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Admin\Resources\KaryawanResource;
use Filament\Notifications\Notification;

class ManageKaryawans extends ManageRecords
{
    protected static string $resource = KaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->using(function(array $data): User
                {
                    DB::beginTransaction();
                    try
                    {
                        $cabangId = auth()->user()?->hasRole('manager_cabang') 
                            ? auth()->user()->cabang_id 
                            : ($data['cabang_id'] ?? null);

                        $karyawan = User::create([
                            'name' => $data['name'],
                            'username' => $data['username'],
                            'email' => !empty($data['email']) ? $data['email'] : null,
                            'password' => $data['password'],
                            'custom_fields' => [
                                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                            ],
                            'avatar_url' => $data['avatar_url'] ?? null,
                            'cabang_id' => $cabangId,
                            'is_visible' => $data['is_visible'] ?? true,
                            'tipe_karyawan' => $data['tipe_karyawan'] ?? 'helpman',
                        ]);
    
                        $karyawan->assignRole('karyawan');

                        DB::commit();

                        Notification::make()
                            ->title('Sukses!')
                            ->body('Tambah karyawan berhasil!')
                            ->success()
                            ->send();

                        return $karyawan;
                    } catch(Exception $e)
                    {
                        DB::rollBack();

                        Notification::make()
                            ->title('Gagal!')
                            ->body('Tambah karyawan gagal! ' . $e->getMessage())
                            ->danger()
                            ->send();

                        throw $e;
                    }
                }),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Karyawan';
    }
}
