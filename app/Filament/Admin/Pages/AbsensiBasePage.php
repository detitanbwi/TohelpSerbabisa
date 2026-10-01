<?php

namespace App\Filament\Admin\Pages;

use App\Models\AbsensiBase;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class AbsensiBasePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Batas Waktu Presensi';
    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.admin.pages.absensi-base-page';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Hanya Admin Pusat (super_admin / owner) yang bisa mengakses.
        // Branch Manager (manager_cabang) disembunyikan penuh dan dilarang mengakses.
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('owner') && ! $user->hasRole('manager_cabang');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403, 'Hanya Admin Pusat yang berhak mengakses halaman ini.');

        $data = AbsensiBase::first();
        if ($data) {
            $this->data = [
                'jam_masuk' => $data->jam_masuk ? Carbon::parse($data->jam_masuk)->format('H:i') : '07:30',
                'jam_keluar' => $data->jam_keluar ? Carbon::parse($data->jam_keluar)->format('H:i') : '09:30',
            ];
        } else {
            $this->data = [
                'jam_masuk' => '07:30',
                'jam_keluar' => '09:30',
            ];
        }

        $this->form->fill($this->data);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Pengaturan Batas Waktu Presensi';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Ketentuan Jam Presensi Harian')
                    ->description('Atur batas awal dan batas akhir toleransi presensi kehadiran harian bagi personil/karyawan.')
                    ->schema([
                        TimePicker::make('jam_masuk')
                            ->label('Batas Awal Presensi')
                            ->required()
                            ->seconds(false)
                            ->live(onBlur: true)
                            ->helperText('Waktu paling awal personil dapat mulai melakukan presensi (Format 24 Jam, contoh: 07:30).')
                            ->rules([
                                'required',
                                function (Forms\Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $jamKeluar = $get('jam_keluar');
                                        if (! $value || ! $jamKeluar) {
                                            return;
                                        }

                                        try {
                                            $masuk = Carbon::parse($value);
                                            $keluar = Carbon::parse($jamKeluar);

                                            if ($masuk->greaterThanOrEqualTo($keluar)) {
                                                $fail("Batas awal presensi harus lebih kecil dari batas akhir presensi ({$keluar->format('H:i')}).");
                                            }
                                        } catch (\Exception $e) {
                                            $fail('Format waktu batas awal presensi tidak valid.');
                                        }
                                    };
                                },
                            ]),

                        TimePicker::make('jam_keluar')
                            ->label('Batas Akhir Presensi')
                            ->required()
                            ->seconds(false)
                            ->live(onBlur: true)
                            ->helperText('Batas waktu paling akhir presensi harian (Maksimal pukul 23:59 WIB pada hari yang sama).')
                            ->rules([
                                'required',
                                function (Forms\Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $jamMasuk = $get('jam_masuk');
                                        if (! $value || ! $jamMasuk) {
                                            return;
                                        }

                                        try {
                                            $masuk = Carbon::parse($jamMasuk);
                                            $keluar = Carbon::parse($value);

                                            if ($keluar->lessThanOrEqualTo($masuk)) {
                                                $fail("Batas akhir presensi harus lebih besar dari batas awal presensi ({$masuk->format('H:i')}). Contoh: jika batas awal 08:00, batas akhir tidak boleh 06:00.");
                                                return;
                                            }

                                            $maxTime = Carbon::createFromTime(23, 59, 0, 'Asia/Jakarta');
                                            if ($keluar->greaterThan($maxTime)) {
                                                $fail('Batas waktu maksimal presensi harian adalah setiap pukul 23:59 WIB.');
                                                return;
                                            }
                                        } catch (\Exception $e) {
                                            $fail('Format waktu batas akhir presensi tidak valid.');
                                        }
                                    };
                                },
                            ]),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403, 'Hanya Admin Pusat yang berhak mengubah batas waktu presensi.');

        // Eksekusi validasi form Filament
        $validatedData = $this->form->getState();

        try {
            $masuk = Carbon::parse($validatedData['jam_masuk']);
            $keluar = Carbon::parse($validatedData['jam_keluar']);

            if ($keluar->lessThanOrEqualTo($masuk)) {
                Notification::make()
                    ->title('Validasi Gagal')
                    ->body("Batas akhir presensi ({$keluar->format('H:i')}) harus lebih besar dari batas awal presensi ({$masuk->format('H:i')}).")
                    ->danger()
                    ->send();
                return;
            }

            AbsensiBase::updateOrCreate([
                'id' => 1,
            ], [
                'jam_masuk' => $masuk->format('H:i:s'),
                'jam_keluar' => $keluar->format('H:i:s'),
            ]);

            Notification::make()
                ->title('Berhasil Disimpan')
                ->body('Pengaturan batas waktu presensi berhasil disimpan.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Terjadi kesalahan saat memproses waktu presensi: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
