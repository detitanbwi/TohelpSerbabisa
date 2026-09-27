<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Cabang;
use App\Models\Transaksi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class KomparasiCabangChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Komparasi Omset Antar Cabang';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 1];
    protected static ?string $maxHeight = '320px';

    public ?string $filter = '30';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected function getFilters(): ?array
    {
        return [
            '30' => '30 Hari Terakhir',
            'this_month' => 'Bulan Ini',
            'all' => 'Semua Waktu',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter ?? '30';
        $query = Transaksi::query()
            ->where('status_transaksi', 'sukses')
            ->whereNotNull('cabang_id');

        if ($filter === 'this_month') {
            $query->where('created_at', '>=', Carbon::now('Asia/Jakarta')->startOfMonth());
        } elseif ($filter !== 'all') {
            $days = (int) $filter;
            $query->where('created_at', '>=', Carbon::now('Asia/Jakarta')->subDays($days)->startOfDay());
        }

        $cabangs = Cabang::pluck('nama', 'id')->toArray();

        $records = $query->select(['cabang_id', 'total_harga'])
            ->get()
            ->groupBy('cabang_id')
            ->map(function ($items) {
                return [
                    'omset' => $items->sum('total_harga'),
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('omset')
            ->take(6);

        if ($records->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Total Omset (Rp)',
                        'data' => [0],
                        'backgroundColor' => '#E2E8F0',
                    ],
                ],
                'labels' => ['Belum Ada Transaksi Cabang'],
            ];
        }

        $labels = [];
        $data = [];

        foreach ($records as $cabangId => $stat) {
            $cabangName = $cabangs[$cabangId] ?? "Cabang #{$cabangId}";
            $labels[] = $cabangName;
            $data[] = (int) $stat['omset'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Omset (Rp)',
                    'data' => $data,
                    'backgroundColor' => '#3B82F6',
                    'borderRadius' => 6,
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
