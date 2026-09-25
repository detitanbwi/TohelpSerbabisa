<?php

namespace App\Console\Commands;

use App\Services\AbsensiService;
use Illuminate\Console\Command;

class SyncHelpmanPresensi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'helpman:sync-presensi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi status keaktifan helpman (is_visible) berdasarkan presensi hari ini';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai sinkronisasi status keaktifan presensi karyawan...');

        $result = AbsensiService::syncDailyEmployeeActiveStatus();

        $this->info("Sinkronisasi selesai.");
        $this->info("- Diaktifkan (Sudah presensi): {$result['activated']}");
        $this->info("- Dinonaktifkan (Belum/Tidak presensi): {$result['deactivated']}");
        $this->info("- Total presensi hari ini: {$result['present_count']}");

        return Command::SUCCESS;
    }
}
