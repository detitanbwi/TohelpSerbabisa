<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class OrganizePresensiStorageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:organize-presensi 
                            {--dry-run : Jalankan simulasi pemindahan folder tanpa memodifikasi file fisik}
                            {--force : Jalankan proses migrasi langsung tanpa meminta konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merapikan folder angka bukti presensi dari root storage/app/public ke dalam storage/app/public/presensi/';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');

        $this->info('===========================================================');
        $this->info('      MIGRASI STRUKTUR FOLDER BUKTI PRESENSI KE presensi/   ');
        $this->info('===========================================================');

        if ($isDryRun) {
            $this->warn('[PERINGATAN] Mode SIMULASI (--dry-run) aktif. Tidak ada file yang akan dipindahkan.');
        }

        $publicPath = storage_path('app/public');
        if (!File::isDirectory($publicPath)) {
            $this->error("Direktori storage publik tidak ditemukan: {$publicPath}");
            return Command::FAILURE;
        }

        $targetPresensiDir = $publicPath . DIRECTORY_SEPARATOR . 'presensi';

        // Cari seluruh subdirektori numerik (misal: 1, 2, ..., 8188) langsung di dalam storage/app/public/
        $allDirs = File::directories($publicPath);
        $numericDirs = [];

        foreach ($allDirs as $dir) {
            $folderName = basename($dir);
            if (ctype_digit($folderName)) {
                $numericDirs[] = [
                    'id' => (int) $folderName,
                    'name' => $folderName,
                    'full_path' => $dir,
                    'file_count' => count(File::allFiles($dir)),
                ];
            }
        }

        // Urutkan berdasarkan ID
        usort($numericDirs, fn ($a, $b) => $a['id'] <=> $b['id']);

        $totalFolders = count($numericDirs);

        if ($totalFolders === 0) {
            $this->info('Tidak ditemukan folder angka di root storage/app/public/. Struktur folder sudah rapi!');
            return Command::SUCCESS;
        }

        $this->line("Ditemukan <comment>{$totalFolders}</comment> folder numerik presensi di root public storage.");

        if (!$isDryRun && !$isForce) {
            if (!$this->confirm("Apakah Anda yakin ingin memindahkan {$totalFolders} folder ke dalam {$targetPresensiDir}?", true)) {
                $this->warn('Proses migrasi dibatalkan oleh pengguna.');
                return Command::SUCCESS;
            }
        }

        // Pastikan folder tujuan presensi/ tersedia
        if (!$isDryRun && !File::isDirectory($targetPresensiDir)) {
            File::makeDirectory($targetPresensiDir, 0755, true);
        }

        $progressBar = $this->output->createProgressBar($totalFolders);
        $progressBar->start();

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;

        foreach ($numericDirs as $item) {
            $destDir = $targetPresensiDir . DIRECTORY_SEPARATOR . $item['name'];

            if ($isDryRun) {
                $successCount++;
                $progressBar->advance();
                continue;
            }

            try {
                // Jika folder tujuan sudah ada (misal sebelumnya pernah sebagian dipindah)
                if (File::isDirectory($destDir)) {
                    $files = File::allFiles($item['full_path']);
                    foreach ($files as $file) {
                        $destFilePath = $destDir . DIRECTORY_SEPARATOR . $file->getFilename();
                        if (!File::exists($destFilePath)) {
                            File::copy($file->getRealPath(), $destFilePath);
                            File::delete($file->getRealPath());
                        }
                    }
                    if (count(File::allFiles($item['full_path'])) === 0) {
                        File::deleteDirectory($item['full_path']);
                    }
                    $successCount++;
                } else {
                    $moved = @File::moveDirectory($item['full_path'], $destDir);
                    if (!$moved) {
                        File::copyDirectory($item['full_path'], $destDir);
                        File::deleteDirectory($item['full_path']);
                    }
                    $successCount++;
                }
            } catch (\Exception $e) {
                $failCount++;
                $this->error("\nGagal memindahkan folder {$item['name']}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total Folder Terdeteksi', $totalFolders],
                ['Berhasil Dipindahkan', $successCount],
                ['Gagal', $failCount],
                ['Mode Eksekusi', $isDryRun ? 'Simulasi (--dry-run)' : 'Migrasi Nyata'],
            ]
        );

        // Cek apakah ada sisa file staging di folder bukti-absensi
        $stagingDir = $publicPath . DIRECTORY_SEPARATOR . 'bukti-absensi';
        if (File::isDirectory($stagingDir)) {
            $stagingFiles = File::allFiles($stagingDir);
            if (count($stagingFiles) > 0) {
                $this->warn("Catatan: Ditemukan " . count($stagingFiles) . " file sementara di folder 'bukti-absensi'.");
                if (!$isDryRun) {
                    $this->info("Membersihkan file sementara di 'bukti-absensi'...");
                    File::cleanDirectory($stagingDir);
                    $this->info("Folder 'bukti-absensi' berhasil dibersihkan.");
                }
            }
        }

        $this->info('Selesai! Seluruh folder bukti presensi kini tersusun rapi di: storage/app/public/presensi/');
        return Command::SUCCESS;
    }
}
