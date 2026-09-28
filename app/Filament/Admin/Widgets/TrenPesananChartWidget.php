<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaksi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class TrenPesananChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Tren Volume Pesanan Harian';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = ['md' => 1, 'xl' => 2];
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
            '90' => '90 Hari Terakhir',
        ];
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?? 30);
        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole('super_admin');
        $cabangIds = $user?->getCabangIds() ?? [];

        $startDate = Carbon::now('Asia/Jakarta')->subDays($days - 1)->startOfDay();
        $endDate = Carbon::now('Asia/Jakarta')->endOfDay();

        $query = Transaksi::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        if (! $isSuperAdmin) {
            $query->whereIn('cabang_id', $cabangIds);
        }

        $records = $query->select(['id', 'status_transaksi', 'created_at'])
            ->get()
            ->groupBy(fn ($item) => Carbon::parse($item->created_at)->timezone('Asia/Jakarta')->format('Y-m-d'));

        $labels = [];
        $totalOrdersData = [];
        $suksesOrdersData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $targetDate = Carbon::now('Asia/Jakarta')->subDays($i);
            $key = $targetDate->format('Y-m-d');
            $labels[] = $targetDate->translatedFormat($days > 30 ? 'd/m' : 'd M');

            $dailyItems = $records->get($key, collect());
            $totalOrdersData[] = $dailyItems->count();
            $suksesOrdersData[] = $dailyItems->where('status_transaksi', 'sukses')->count();
        }

        $datasets = [
            [
                'label' => 'Total Pesanan Masuk',
                'data' => $totalOrdersData,
                'borderColor' => '#3B82F6',
                'backgroundColor' => 'rgba(59, 130, 246, 0.12)',
                'fill' => true,
                'tension' => 0.35,
            ],
            [
                'label' => 'Pesanan Selesai / Sukses',
                'data' => $suksesOrdersData,
                'borderColor' => '#10B981',
                'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                'fill' => true,
                'tension' => 0.35,
            ],
        ];

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
