<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Absensi;
use App\Models\Cabang;
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
        $startThisMonth = $now->copy()->startOfMonth();
        $startLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Helper function for base transaction query
        $transaksiBaseQuery = function () use ($isSuperAdmin, $cabangIds) {
            $q = Transaksi::query();
            if (! $isSuperAdmin) {
                $q->whereIn('cabang_id', $cabangIds);
            }

            return $q;
        };

        // 1. Omset Bulan Ini & Bulan Lalu
        $omsetBulanIni = (clone $transaksiBaseQuery())
            ->where('status_transaksi', 'sukses')
            ->where('created_at', '>=', $startThisMonth)
            ->sum('total_harga') ?? 0;

        $omsetBulanLalu = (clone $transaksiBaseQuery())
            ->where('status_transaksi', 'sukses')
            ->whereBetween('created_at', [$startLastMonth, $endLastMonth])
            ->sum('total_harga') ?? 0;

        $omsetDiffPercent = $omsetBulanLalu > 0
            ? round((($omsetBulanIni - $omsetBulanLalu) / $omsetBulanLalu) * 100, 1)
            : ($omsetBulanIni > 0 ? 100 : 0);

        // Sparkline 7 hari terakhir untuk Omset
        $sparklineOmset = [];
        for ($i = 6; $i >= 0; $i--) {
            $dayStart = $now->copy()->subDays($i)->startOfDay();
            $dayEnd = $now->copy()->subDays($i)->endOfDay();
            $sparklineOmset[] = (int) ((clone $transaksiBaseQuery())
                ->where('status_transaksi', 'sukses')
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->sum('total_harga') ?? 0);
        }

        $statOmsetLabel = $isSuperAdmin ? 'Total Omset (GMV) Bulan Ini' : 'Omset Cabang Bulan Ini';
        $statOmset = Stat::make($statOmsetLabel, 'Rp ' . number_format($omsetBulanIni, 0, ',', '.'))
            ->description(($omsetDiffPercent >= 0 ? "+{$omsetDiffPercent}%" : "{$omsetDiffPercent}%") . ' dibanding bulan lalu')
            ->descriptionIcon($omsetDiffPercent >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->chart($sparklineOmset)
            ->color($omsetDiffPercent >= 0 ? 'success' : 'danger');

        // 2. Card 2: Komisi Bersih (Pusat) ATAU Total Tip Mitra (Cabang)
        if ($isSuperAdmin) {
            $komisiBulanIni = (clone $transaksiBaseQuery())
                ->where('status_transaksi', 'sukses')
                ->where('created_at', '>=', $startThisMonth)
                ->sum('komisi_admin') ?? 0;

            $sparklineKomisi = [];
            for ($i = 6; $i >= 0; $i--) {
                $dayStart = $now->copy()->subDays($i)->startOfDay();
                $dayEnd = $now->copy()->subDays($i)->endOfDay();
                $sparklineKomisi[] = (int) ((clone $transaksiBaseQuery())
                    ->where('status_transaksi', 'sukses')
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->sum('komisi_admin') ?? 0);
            }

            $statCard2 = Stat::make('Pendapatan Komisi Platform', 'Rp ' . number_format($komisiBulanIni, 0, ',', '.'))
                ->description('Total komisi bersih terkumpul bulan ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($sparklineKomisi)
                ->color('emerald');
        } else {
            $tipBulanIni = (clone $transaksiBaseQuery())
                ->where('status_transaksi', 'sukses')
                ->where('created_at', '>=', $startThisMonth)
                ->sum('tip') ?? 0;

            $sparklineTip = [];
            for ($i = 6; $i >= 0; $i--) {
                $dayStart = $now->copy()->subDays($i)->startOfDay();
                $dayEnd = $now->copy()->subDays($i)->endOfDay();
                $sparklineTip[] = (int) ((clone $transaksiBaseQuery())
                    ->where('status_transaksi', 'sukses')
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->sum('tip') ?? 0);
            }

            $statCard2 = Stat::make('Total Tip Mitra Cabang', 'Rp ' . number_format($tipBulanIni, 0, ',', '.'))
                ->description('Apresiasi pelanggan kepada personil bulan ini')
                ->descriptionIcon('heroicon-m-heart')
                ->chart($sparklineTip)
                ->color('amber');
        }

        // 3. Card 3: Pesanan Sukses & Completion Rate
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

        $sparklineOrders = [];
        for ($i = 6; $i >= 0; $i--) {
            $dayStart = $now->copy()->subDays($i)->startOfDay();
            $dayEnd = $now->copy()->subDays($i)->endOfDay();
            $sparklineOrders[] = (clone $transaksiBaseQuery())
                ->where('status_transaksi', 'sukses')
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
        }

        $statCard3 = Stat::make('Order Sukses Bulan Ini', number_format($orderSuksesBulanIni, 0, ',', '.') . ' Pesanan')
            ->description("Keberhasilan {$completionRate}% ({$orderBatalBulanIni} batal)")
            ->descriptionIcon('heroicon-m-check-badge')
            ->chart($sparklineOrders)
            ->color($completionRate >= 80 ? 'success' : ($completionRate >= 60 ? 'warning' : 'danger'));

        // 4. Card 4: Cabang & Personil (Pusat) ATAU Helpman Siaga Hari Ini (Cabang)
        if ($isSuperAdmin) {
            $totalCabang = Cabang::count();
            $totalPersonil = User::whereHas('roles', fn ($q) => $q->where('name', 'karyawan'))->count();

            $statCard4 = Stat::make('Jaringan Operasional', "{$totalCabang} Cabang")
                ->description("Didukung {$totalPersonil} personil aktif di seluruh cabang")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary');
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

            $statCard4 = Stat::make('Helpman Siaga Hari Ini', "{$helpmanSiagaHariIni} / {$totalHelpmanCabang} Personil")
                ->description("{$persenSiaga}% armada telah presensi & siap bertugas")
                ->descriptionIcon('heroicon-m-user-group')
                ->color($persenSiaga >= 70 ? 'success' : ($persenSiaga >= 40 ? 'warning' : 'danger'));
        }

        return [
            $statOmset,
            $statCard2,
            $statCard3,
            $statCard4,
        ];
    }
}
