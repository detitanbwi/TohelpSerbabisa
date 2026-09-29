<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Cabang;
use App\Models\Transaksi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class KomparasiCabangChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Komparasi Volume Pesanan Antar Cabang';
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
            ->whereNotNull('cabang_id');

        if ($filter === 'this_month') {
            $query->where('created_at', '>=', Carbon::now('Asia/Jakarta')->startOfMonth());
        } elseif ($filter !== 'all') {
            $days = (int) $filter;
            $query->where('created_at', '>=', Carbon::now('Asia/Jakarta')->subDays($days)->startOfDay());
        }

        $cabangs = Cabang::pluck('nama', 'id')->toArray();

        $records = $query->selectRaw("cabang_id, count(*) as total, sum(case when status_transaksi = 'sukses' then 1 else 0 end) as sukses")
            ->groupBy('cabang_id')
            ->orderByDesc('total')
            ->take(6)
            ->get();

        if ($records->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Total Pesanan Masuk',
                        'data' => [0],
                        'backgroundColor' => '#CBD5E1',
                    ],
                ],
                'labels' => ['Belum Ada Transaksi Cabang'],
            ];
        }

        $labels = [];
        $totalData = [];
        $suksesData = [];

        foreach ($records as $stat) {
            $cabangId = $stat->cabang_id;
            $cabangName = $cabangs[$cabangId] ?? "Cabang #{$cabangId}";
            $labels[] = $cabangName;
            $totalData[] = (int) $stat->total;
            $suksesData[] = (int) $stat->sukses;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Pesanan Masuk',
                    'data' => $totalData,
                    'backgroundColor' => '#3B82F6',
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Pesanan Sukses',
                    'data' => $suksesData,
                    'backgroundColor' => '#10B981',
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
