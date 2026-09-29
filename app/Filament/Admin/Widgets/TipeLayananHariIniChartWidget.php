<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Layanan;
use App\Models\Transaksi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class TipeLayananHariIniChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Tipe Layanan yang Dibeli Hari Ini';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];
    protected static ?string $maxHeight = '320px';
    protected static ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->hasAnyRole(['super_admin', 'manager_cabang']) ?? false;
    }

    public function getDescription(): ?string
    {
        return 'Distribusi pesanan hari ini (' . Carbon::now('Asia/Jakarta')->translatedFormat('d M Y') . ')';
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole('super_admin');
        $cabangIds = $user?->getCabangIds() ?? [];

        $todayStart = Carbon::today('Asia/Jakarta')->startOfDay();

        $query = Transaksi::query()
            ->where('created_at', '>=', $todayStart);

        if (! $isSuperAdmin) {
            $query->whereIn('cabang_id', $cabangIds);
        }

        $records = $query->selectRaw('jenis, count(*) as total')
            ->groupBy('jenis')
            ->orderByDesc('total')
            ->pluck('total', 'jenis');

        if ($records->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Pesanan Hari Ini',
                        'data' => [1],
                        'backgroundColor' => ['#CBD5E1'],
                    ],
                ],
                'labels' => ['Belum Ada Pesanan Hari Ini'],
            ];
        }

        // Map jenis to nama layanan yang resmi
        $layananMap = Layanan::pluck('nama', 'slug')->toArray();

        $labels = [];
        $data = [];

        foreach ($records as $jenis => $count) {
            $slugClean = trim((string) $jenis, '/');
            $nama = $layananMap[$slugClean]
                ?? $layananMap[$jenis]
                ?? ucwords(str_replace(['-', '_'], ' ', $jenis ?: 'Umum'));

            $labels[] = "{$nama} ({$count})";
            $data[] = $count;
        }

        $palette = [
            '#F59E0B', // Amber
            '#3B82F6', // Blue
            '#10B981', // Emerald
            '#8B5CF6', // Purple
            '#EC4899', // Pink
            '#06B6D4', // Cyan
            '#64748B', // Slate
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pesanan',
                    'data' => $data,
                    'backgroundColor' => array_slice(
                        array_merge($palette, $palette),
                        0,
                        count($data)
                    ),
                    'borderWidth' => 2,
                    'borderColor' => '#FFFFFF',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
