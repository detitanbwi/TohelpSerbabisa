<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Absensi;
use App\Models\Cabang;
use App\Models\Layanan;
use App\Models\SubLayanan;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->hasAnyRole(['super_admin', 'manager_cabang']) ?? false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $isSuperAdmin = $user->hasRole('super_admin');
        $cabangIds = $user->getCabangIds();

        $now = Carbon::now('Asia/Jakarta');
        $todayStart = $now->copy()->startOfDay();
        $startThisMonth = $now->copy()->startOfMonth();

        // Helper function for base transaction query
        $transaksiBaseQuery = function () use ($isSuperAdmin, $cabangIds) {
            $q = Transaksi::query();
            if (! $isSuperAdmin) {
                $q->whereIn('cabang_id', $cabangIds);
            }

            return $q;
        };

        // 1. Card 1: Jumlah Layanan & Sub-Layanan Aktif
        if ($isSuperAdmin) {
            $totalLayanan = Layanan::where('is_active', true)->count();
            $totalSubLayanan = SubLayanan::where('is_active', true)->count();

            $statCard1 = Stat::make('Katalog Layanan Aktif', "{$totalLayanan} Layanan")
                ->description("{$totalSubLayanan} sub-layanan / paket siap dipesan")
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('primary');
        } else {
            $totalLayananCabang = Layanan::where('is_active', true)->count();
            $totalSubLayananCabang = SubLayanan::where('is_active', true)->count();

            $statCard1 = Stat::make('Layanan Aktif Cabang', "{$totalLayananCabang} Layanan")
                ->description("{$totalSubLayananCabang} sub-layanan tersedia untuk pelanggan")
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary');
        }

        // 2. Card 2: Jumlah Cabang & Personil (Pusat) ATAU Helpman Siaga Hari Ini (Cabang)
        if ($isSuperAdmin) {
            $totalCabang = Cabang::count();
            $totalPersonil = User::whereHas('roles', fn ($q) => $q->where('name', 'karyawan'))->count();

            $statCard2 = Stat::make('Jaringan Cabang', "{$totalCabang} Cabang")
                ->description("Didukung {$totalPersonil} personil aktif di seluruh cabang")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info');
        } else {
            $todayDate = Carbon::today('Asia/Jakarta')->toDateString();

            $totalHelpmanCabang = User::whereHas('roles', fn ($q) => $q->where('name', 'karyawan'))
                ->whereIn('cabang_id', $cabangIds)
                ->count();

            $helpmanCabangIds = User::whereHas('roles', fn ($q) => $q->where('name', 'karyawan'))
                ->whereIn('cabang_id', $cabangIds)
                ->pluck('id')
                ->toArray();

            $helpmanSiagaHariIni = ! empty($helpmanCabangIds)
                ? Absensi::whereDate('tanggal', $todayDate)
                    ->whereIn('karyawan_id', $helpmanCabangIds)
                    ->distinct('karyawan_id')
                    ->count('karyawan_id')
                : 0;

            $persenSiaga = $totalHelpmanCabang > 0
                ? round(($helpmanSiagaHariIni / $totalHelpmanCabang) * 100, 1)
                : 0;

            $statCard2 = Stat::make('Helpman Siaga Hari Ini', "{$helpmanSiagaHariIni} / {$totalHelpmanCabang} Personil")
                ->description("{$persenSiaga}% armada telah presensi & siap bertugas")
                ->descriptionIcon('heroicon-m-user-group')
                ->color($persenSiaga >= 70 ? 'success' : ($persenSiaga >= 40 ? 'warning' : 'danger'));
        }

        // 3. Card 3: Pesanan Masuk Hari Ini
        $ordersHariIni = (clone $transaksiBaseQuery())
            ->where('created_at', '>=', $todayStart)
            ->count();

        $ordersSuksesHariIni = (clone $transaksiBaseQuery())
            ->where('status_transaksi', 'sukses')
            ->where('created_at', '>=', $todayStart)
            ->count();

        $ordersProsesHariIni = (clone $transaksiBaseQuery())
            ->where('created_at', '>=', $todayStart)
            ->whereIn('status_tugas', ['belum', 'proses'])
            ->where('status_transaksi', '!=', 'batal')
            ->count();

        // Sparkline 7 hari terakhir untuk volume pesanan
        $sparklineOrders7Hari = [];
        for ($i = 6; $i >= 0; $i--) {
            $dayStart = $now->copy()->subDays($i)->startOfDay();
            $dayEnd = $now->copy()->subDays($i)->endOfDay();
            $sparklineOrders7Hari[] = (clone $transaksiBaseQuery())
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
        }

        $statCard3 = Stat::make('Pesanan Masuk Hari Ini', "{$ordersHariIni} Pesanan")
            ->description("{$ordersSuksesHariIni} selesai · {$ordersProsesHariIni} dalam proses")
            ->descriptionIcon('heroicon-m-calendar-days')
            ->chart($sparklineOrders7Hari)
            ->color('warning');

        // 4. Card 4: Order Sukses & Completion Rate Bulan Ini
        $totalOrderBulanIni = (clone $transaksiBaseQuery())
            ->where('created_at', '>=', $startThisMonth)
            ->count();

        $orderSuksesBulanIni = (clone $transaksiBaseQuery())
            ->where('status_transaksi', 'sukses')
            ->where('created_at', '>=', $startThisMonth)
            ->count();

        $orderBatalBulanIni = (clone $transaksiBaseQuery())
            ->where('status_transaksi', 'batal')
            ->where('created_at', '>=', $startThisMonth)
            ->count();

        $completionRate = $totalOrderBulanIni > 0
            ? round(($orderSuksesBulanIni / $totalOrderBulanIni) * 100, 1)
            : 0;

        $sparklineSukses7Hari = [];
        for ($i = 6; $i >= 0; $i--) {
            $dayStart = $now->copy()->subDays($i)->startOfDay();
            $dayEnd = $now->copy()->subDays($i)->endOfDay();
            $sparklineSukses7Hari[] = (clone $transaksiBaseQuery())
                ->where('status_transaksi', 'sukses')
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
        }

        $statCard4 = Stat::make('Order Sukses Bulan Ini', "{$orderSuksesBulanIni} Pesanan")
            ->description("Tingkat penyelesaian {$completionRate}% ({$orderBatalBulanIni} batal)")
            ->descriptionIcon('heroicon-m-check-badge')
            ->chart($sparklineSukses7Hari)
            ->color($completionRate >= 80 ? 'success' : ($completionRate >= 60 ? 'warning' : 'danger'));

        return [
            $statCard1,
            $statCard2,
            $statCard3,
            $statCard4,
        ];
    }
}
