<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Layanan;
use App\Models\Transaksi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class DistribusiLayananChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Jenis Layanan (Historis)';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];
    protected static ?string $maxHeight = '320px';

    public ?string $filter = '30';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->hasAnyRole(['super_admin', 'manager_cabang']) ?? false;
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 Hari Terakhir',
            '30' => '30 Hari Terakhir',
            'all' => 'Semua Waktu',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter ?? '30';
        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole('super_admin');
        $cabangIds = $user?->getCabangIds() ?? [];

        $query = Transaksi::query()->where('status_transaksi', 'sukses');

        if (! $isSuperAdmin) {
            $query->whereIn('cabang_id', $cabangIds);
        }

        if ($filter !== 'all') {
            $days = (int) $filter;
            $startDate = Carbon::now('Asia/Jakarta')->subDays($days)->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }

        $records = $query->select('jenis')
            ->get()
            ->groupBy('jenis')
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        if ($records->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#CBD5E1'],
                    ],
                ],
                'labels' => ['Belum Ada Data Pesanan'],
            ];
        }

        $layananMap = Layanan::pluck('nama', 'slug')->toArray();

        // Ambil Top 5, sisanya jadikan 'Lainnya'
        $top5 = $records->take(5);
        $otherCount = $records->slice(5)->sum();

        $labels = [];
        $data = [];

        foreach ($top5 as $jenis => $count) {
            $slugClean = trim((string) $jenis, '/');
            $nama = $layananMap[$slugClean]
                ?? $layananMap[$jenis]
                ?? ucwords(str_replace(['-', '_'], ' ', $jenis ?: 'Umum'));

            $labels[] = "{$nama} ({$count})";
            $data[] = $count;
        }

        if ($otherCount > 0) {
            $labels[] = "Layanan Lainnya ({$otherCount})";
            $data[] = $otherCount;
        }

        $palette = [
            '#F59E0B', // Amber
            '#3B82F6', // Blue
            '#10B981', // Emerald
            '#8B5CF6', // Purple
            '#EC4899', // Pink
            '#64748B', // Slate
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pesanan Sukses',
                    'data' => $data,
                    'backgroundColor' => array_slice($palette, 0, count($data)),
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
