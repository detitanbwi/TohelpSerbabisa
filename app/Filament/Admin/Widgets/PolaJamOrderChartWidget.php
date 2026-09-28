<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaksi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class PolaJamOrderChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Pola Jam Sibuk Pesanan (Peak Hours)';
    protected static ?int $sort = 6;
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

        $query = Transaksi::query();

        if (! $isSuperAdmin) {
            $query->whereIn('cabang_id', $cabangIds);
        }

        if ($filter !== 'all') {
            $days = (int) $filter;
            $query->where('created_at', '>=', Carbon::now('Asia/Jakarta')->subDays($days)->startOfDay());
        }

        $transaksis = $query->select(['id', 'created_at'])->get();

        // Hitung transaksi per jam (rentang 06:00 - 22:00)
        $hourCounts = array_fill(6, 17, 0);

        foreach ($transaksis as $item) {
            $hour = (int) Carbon::parse($item->created_at)->timezone('Asia/Jakarta')->format('G');
            if ($hour >= 6 && $hour <= 22) {
                $hourCounts[$hour]++;
            }
        }

        $labels = [];
        $data = [];
        $backgroundColors = [];
        $maxCount = max(array_values($hourCounts) ?: [0]);

        for ($h = 6; $h <= 22; $h++) {
            $labels[] = sprintf('%02d:00', $h);
            $val = $hourCounts[$h];
            $data[] = $val;

            // Highlight jam dengan order terbanyak dengan warna oranye terang
            if ($maxCount > 0 && $val === $maxCount) {
                $backgroundColors[] = '#EF4444'; // Red highlight peak
            } elseif ($maxCount > 0 && $val >= $maxCount * 0.7) {
                $backgroundColors[] = '#F59E0B'; // Amber high traffic
            } else {
                $backgroundColors[] = '#818CF8'; // Indigo normal traffic
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Pesanan Masuk',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
