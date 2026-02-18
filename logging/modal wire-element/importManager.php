<?php

namespace App\Livewire\Modal;

use App\Factories\ImportStrategyFactory;
use App\Models\Classes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use LivewireUI\Modal\ModalComponent;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class ImportManager extends ModalComponent
{
    use WithFileUploads;

    protected $listeners = [
        'reopenImportManager' => 'mount', // Me-mount ulang komponen
    ];

    public $importType;
    public $step = 1;
    public $file;
    public $previewData = [];
    public $totalData = 0;
    public $progress = 0;
    public $isImporting = false;
    public $batchId = null;
    public $statusMessage = '';
    public $closeModalDelay = 3;
    public $autoCloseTimer = null;

    /*
     * perhitungan user point
     */
    public $newDataCount;
    public $duplicateInFileCount;
    public $currentPoints;
    public $pointsAfter;
    public $databaseDuplicateCount = 0;

    // 🔴 TAMBAHKAN PROPERTY UNTUK DUPLIKAT
    public $importResult = [
        'success' => false,
        'processed_count' => 0,
        'error_count' => 0,
        'duplicate_count' => 0, // 🔴 TAMBAHKAN INI
        'message' => ''
    ];

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }

    public function mount($type = null)
    {
        $this->importType = $type;
        \Log::info('ImportManager mounted', [
            'user_id' => Auth::id(),
            'type' => $type
        ]);
    }

    public function getListeners()
    {
        return [
            'refreshProgress' => 'pollProgress',
            'closeModal' => 'closeModalNow'
        ];
    }

    public function updatedFile()
    {
        // SIZE LIMIT UNTUK SHARED HOSTING
        $maxSize = 2048; // 2MB max untuk shared hosting

        $this->validate([
            'file' => [
                'required',
                'mimes:xlsx,xls,csv',
                'max:' . $maxSize,
                function ($attribute, $value, $fail) {
                    // VALIDASI ROWS COUNT JUGA
                    try {
                        $path = $value->getRealPath();
                        $data = Excel::toArray([], $path)[0];
                        $rowCount = count($data) - 1; // Exclude header

                        if ($rowCount > 1000) {
                            $fail('File terlalu besar (max 1000 baris). Silahkan split file.');
                        }
                    } catch (\Exception $e) {
                        // Silent fail jika tidak bisa baca
                    }
                }
            ]
        ]);

        $this->loadPreview(); // METHOD LAMA TETAP SAMA
    }

    /**
     * Format error message untuk tampilan yang lebih user-friendly
     */
    private function formatErrorMessage(string $errorMessage): string
    {
        // Jika sudah ada [Excel baris X], biarkan seperti itu
        if (str_contains($errorMessage, '[Excel baris')) {
            return $errorMessage;
        }

        $mappings = [
            // Matakuliah Import
            'Program Studi dengan ID:' => '❌ Program Studi tidak ditemukan',
            'Matakuliah Sudah Ada' => '⚠️ Data sudah ada (akan diupdate)',
            'Kode MK Kosong' => '❌ Kolom Kode MK harus diisi',
            'Nama Matakuliah Kosong' => '❌ Kolom Nama Matakuliah harus diisi',
            'Program Studi ID Kosong' => '❌ Kolom Program Studi ID harus diisi',
            'Semester harus angka' => '❌ Kolom Semester harus berupa angka',
            'SKS harus angka' => '❌ Kolom SKS harus berupa angka',

            // Mahasiswa Import
            'NIM Sudah Ada' => '⚠️ NIM sudah terdaftar (akan diupdate)',
            'NIM Kosong' => '❌ Kolom NIM harus diisi',
            'Kelas tidak ditemukan' => '❌ Kelas tidak ditemukan di database',

            // CPMK Import
            'Matakuliah tidak ditemukan' => '❌ Mata Kuliah tidak ditemukan',
            'CPMK Sudah Ada' => '⚠️ CPMK sudah ada untuk mata kuliah ini',

            // Nilai Import - TAMBAHKAN PEMETAAN UNTUK DUPLIKAT
            'Data sudah ada. Nilai akan diupdate.' => '⚠️ Data sudah ada (akan diupdate)',
            'Data sudah ada (akan diupdate)' => '⚠️ Data sudah ada (akan diupdate)',

            // Umum
            'User ID tidak tersedia' => '❌ Sesi login tidak valid',
            'User point tidak ditemukan' => '❌ Data pengguna tidak lengkap',
        ];

        foreach ($mappings as $key => $message) {
            if (str_contains($errorMessage, $key)) {
                // Jika ada ID spesifik, tambahkan ke pesan
                if (str_contains($errorMessage, 'ID:')) {
                    preg_match('/ID: (\d+)/', $errorMessage, $matches);
                    if (isset($matches[1])) {
                        return $message . ' (ID: ' . $matches[1] . ')';
                    }
                }
                return $message;
            }
        }

        // Default: tambahkan emoji jika belum ada
        if (!str_starts_with($errorMessage, '❌') &&
            !str_starts_with($errorMessage, '⚠️') &&
            !str_starts_with($errorMessage, '✅')) {
            return '❌ ' . $errorMessage;
        }

        return $errorMessage;
    }

    public function loadPreview()
    {
        $path = $this->file->getRealPath();
        $data = Excel::toArray([], $path)[0];

        // Filter headers yang null atau kosong
        $rawHeaders = array_filter($data[0], function($header) {
            return $header !== null && trim($header) !== '';
        });

        // Reset array keys
        $rawHeaders = array_values($rawHeaders);

        // Normalisasi headers
        $headers = array_map(function($header) {
            return strtolower(str_replace(' ', '_', trim($header)));
        }, $rawHeaders);

        // Filter baris yang valid
        $rows = array_filter(array_slice($data, 1), function($row) {
            return count(array_filter($row, function($value) {
                    return !empty($value) && trim($value) !== '';
                })) > 0;
        });

        $this->totalData = count($rows);
        $maxPreview = 50;

        // Jika total data lebih kecil dari batas, tampilkan semua.
        $previewSlice = count($rows) <= $maxPreview
            ? $rows
            : array_slice($rows, 0, $maxPreview);

        \Log::info("📊 Preview loading for {$this->importType}", [
            'user_id' => Auth::id(),
            'original_headers_count' => count($data[0]),
            'filtered_headers_count' => count($rawHeaders),
            'total_rows' => count($data),
            'valid_rows' => $this->totalData,
            'preview_rows' => count($previewSlice)
        ]);

        // GUNAKAN STRATEGY PATTERN untuk validasi
        try {
            $strategy = ImportStrategyFactory::make($this->importType, Auth::id());

            // 1. VALIDASI HEADER FILE SEBELUM APA-APA LAIN
            $headerValidation = $strategy->validateFileHeaders($rawHeaders);

            if (!$headerValidation['is_valid']) {
                \Log::warning('❌ [IMPORT MANAGER] File header tidak sesuai', [
                    'import_type' => $this->importType,
                    'user_id' => Auth::id(),
                    'found_headers' => $rawHeaders,
                    'expected_headers' => $strategy->getExpectedHeaders(),
                    'validation_message' => $headerValidation['message']
                ]);

                // Format pesan error yang user-friendly
                $errorMessage = $this->formatHeaderErrorMessage(
                    $headerValidation['message'],
                    $strategy->getImportTypeLabel(),
                    $strategy->getExpectedHeaders(),
                    $rawHeaders
                );

                LivewireAlert::title('Format File Tidak Sesuai')
                    ->text('')
                    ->html($errorMessage)
                    ->position('center')
                    ->error()
                    ->timer(10000)
                    ->show();

                // Reset state
                $this->file = null;
                $this->step = 1;
                $this->previewData = [];
                return;
            }

            // 2. JIKA HEADER VALID, LANJUT KE RESET TRACKING
            $strategiesWithInFileTracking = [
                'cpmkimport',
                'nilaiimport',
                'mahasiswaimport',
                'matakuliahimport',
                'kelasimport',
                'cplimport',
                'prodiimport'
            ];

            // 🔴 RESET TRACKING UNTUK STRATEGY CPMK
            if (in_array($this->importType, $strategiesWithInFileTracking) &&
                method_exists($strategy, 'resetInFileTracking')) {
                $strategy->resetInFileTracking();
                \Log::info("🔄 [IMPORT MANAGER] {$this->importType} in-file tracking reset");
            }

        } catch (\Exception $e) {
            \Log::error('Failed to create strategy: ' . $e->getMessage());
            LivewireAlert::error('Import type tidak didukung');
            return;
        }

        // Hitung offset untuk baris Excel yang benar
        $excelRowNumbers = [];
        $excelData = array_slice($data, 1);

        foreach ($previewSlice as $previewIndex => $previewRow) {
            foreach ($excelData as $excelIndex => $excelRow) {
                if (count(array_filter($excelRow, fn($v) => !empty($v) && trim($v) !== '')) > 0) {
                    if ($excelRow == $previewRow || array_slice($excelRow, 0, count($previewRow)) == $previewRow) {
                        $excelRowNumbers[$previewIndex] = $excelIndex + 2;
                        break;
                    }
                }
            }

            if (!isset($excelRowNumbers[$previewIndex])) {
                $excelRowNumbers[$previewIndex] = $previewIndex + 2;
            }
        }

        $this->previewData = array_map(function($row, $index) use ($headers, $rawHeaders, $strategy, $excelRowNumbers) {
            // Pad row dengan null jika lebih pendek dari headers
            $row = array_pad($row, count($headers), null);

            // Buat array dengan normalized keys
            $combined = [];
            foreach ($headers as $index2 => $normalizedKey) {
                if (isset($rawHeaders[$index2])) {
                    $combined[$normalizedKey] = $row[$index2] ?? null;
                    $combined['_display_' . $normalizedKey] = $rawHeaders[$index2] ?? '';
                }
            }

            // Tambahkan informasi baris Excel
            $excelRow = $excelRowNumbers[$index] ?? ($index + 2);
            $combined['_excel_row'] = $excelRow;

            // Validasi menggunakan strategy dengan info baris Excel
            $validation = [];
            if (method_exists($strategy, 'validateForPreview')) {
                $validation = $strategy->validateForPreview($combined);
            } else {
                $validation = $strategy->validate($combined, $excelRow);
            }

            $combined['_validation'] = $validation['is_valid'] ?? false;

            // Format error message dengan info baris Excel
            $errorMessage = $validation['message'] ?? '';
            if (!empty($errorMessage)) {
                // Tambahkan info baris Excel jika belum ada
                if (!str_contains($errorMessage, '[Excel baris')) {
                    $errorMessage = str_replace(
                        '❌',
                        '❌ [Excel baris ' . $excelRow . ']',
                        $errorMessage
                    );
                    $errorMessage = str_replace(
                        '⚠️',
                        '⚠️ [Excel baris ' . $excelRow . ']',
                        $errorMessage
                    );
                    $errorMessage = str_replace(
                        '✅',
                        '✅ [Excel baris ' . $excelRow . ']',
                        $errorMessage
                    );
                }
            }

            $combined['_validation_message'] = $this->formatErrorMessage($errorMessage);

            // 🔴 TAMBAHKAN: Simpan informasi duplikat jika ada
            if (isset($validation['is_duplicate']) && $validation['is_duplicate']) {
                $combined['is_duplicate'] = true;
            }

            // 🔴 TAMBAHKAN: Simpan informasi duplikat dalam file jika ada
            if (isset($validation['is_in_file_duplicate']) && $validation['is_in_file_duplicate']) {
                $combined['is_in_file_duplicate'] = true;
                $combined['duplicate_of_row'] = $validation['duplicate_of_row'] ?? null;
            }

            return $combined;
        }, $previewSlice, array_keys($previewSlice));

        // NEW DATA COUNT DARI PREVIEW
        $newDataCount = 0;
        $duplicateInFileCount = 0;
        $databaseDuplicateCount = 0;

        foreach ($this->previewData as $row) {
            $isValid = $row['_validation'] ?? false;
            $isInFileDuplicate = $row['is_in_file_duplicate'] ?? false;
            $isDatabaseDuplicate = $row['is_duplicate'] ?? false; // 🔴 Ini adalah duplikat database

            if ($isInFileDuplicate) {
                $duplicateInFileCount++;
            } elseif ($isDatabaseDuplicate) {
                $databaseDuplicateCount++; // 🔴 HITUNG DUPLIKAT DATABASE TERPISAH
            } elseif ($isValid) {
                // 🔴 HANYA data yang VALID dan BUKAN duplikat (baik dalam file maupun database)
                $newDataCount++;
            }
        }

        // Simpan untuk display
        $this->newDataCount = $newDataCount;
        $this->duplicateInFileCount = $duplicateInFileCount;
        $this->databaseDuplicateCount = $databaseDuplicateCount;
        $this->currentPoints = $this->getUserPoints();
        $this->pointsAfter = $this->getPointsAfterImport($newDataCount);

        $this->step = 2;
    }

    private function findClassNameKey($rowData)
    {
        // Cari key yang mungkin untuk nama kelas
        $possibleKeys = ['class_name', 'class', 'nama_kelas', 'kelas', 'classname'];

        foreach ($possibleKeys as $key) {
            if (isset($rowData[$key]) && !empty(trim($rowData[$key]))) {
                return $key;
            }
        }

        // Jika tidak ditemukan, return default
        return 'class_name';
    }

    public function startImport()
    {
        $this->isImporting = true;
        $this->step = 3;
        $this->progress = 0;
        $this->statusMessage = 'Mempersiapkan import...';

        $originalName = $this->file->getClientOriginalName();
        $cleanName = preg_replace('/[^A-Za-z0-9.]/', '_', $originalName);
        $fileName = 'import_' . time() . '_' . $cleanName;

        $path = $this->file->storeAs('temp', $fileName, 'local');

        $fullPathForHeader = Storage::disk('local')->path($path);
        $data = Excel::toArray([], $fullPathForHeader)[0];
        $headers = array_values(array_filter($data[0], fn($h) => !empty($h)));

        $job = null;

        // --- TAMBAHKAN LOGIKA CPL DI SINI ---
        if ($this->importType === 'nilaiimport') {
            $job = new \App\Jobs\NilaiImportJob(auth()->id(), $path, $headers);
        } elseif ($this->importType === 'kelasimport') {
            $job = new \App\Jobs\KelasImportJob(auth()->id(), $path, $headers);
        } elseif ($this->importType === 'cplimport') {
            $job = new \App\Jobs\CPLImportJob(auth()->id(), $path, $headers);
        } elseif ($this->importType === 'mahasiswaimport') {
            $job = new \App\Jobs\MahasiswaImportJob(auth()->id(), $path, $headers);
        } elseif ($this->importType === 'prodiimport') {
            $job = new \App\Jobs\ProdiImportJob(auth()->id(), $path, $headers);
        } elseif ($this->importType === 'cpmkimport') {
            $job = new \App\Jobs\CpmkImportJob(auth()->id(), $path, $headers);
        } elseif ($this->importType === 'matakuliahimport') {
            $job = new \App\Jobs\MatakuliahImportJob(auth()->id(), $path, $headers);
        } else {
            throw new \Exception("Import type tidak didukung");
        }
        // ------------------------------------

        $batch = Bus::batch([$job])
            ->name('Import ' . $this->importType . ' - ' . auth()->user()->name)
            ->allowFailures()
            ->dispatch();

        $this->batchId = $batch->id;
        $this->statusMessage = 'Memproses data...';
    }

    public function pollProgress()
    {
        // 🔴 TAMBAHKAN CHECK: Hentikan polling jika sudah selesai
        if (!$this->batchId || !$this->isImporting || $this->progress >= 100) {
            return;
        }

        \Log::debug('Polling called', [
            'isImporting' => $this->isImporting,
            'progress' => $this->progress,
            'batchId' => $this->batchId,
            'time' => now()->format('H:i:s.u')
        ]);

        try {
            $batch = Bus::findBatch($this->batchId);

            if (!$batch) {
                $this->isImporting = false;
                $this->statusMessage = 'Proses tidak ditemukan';
                return;
            }

            $batchProgress = $batch->progress();
            $this->progress = $batchProgress;

            // Log hanya jika ada perubahan signifikan
            static $lastLoggedProgress = -1;
            if (abs($batchProgress - $lastLoggedProgress) >= 10 || $batch->finished()) {
                \Log::info('Polling progress', [
                    'batch_id' => $this->batchId,
                    'progress' => $batchProgress,
                    'finished' => $batch->finished(),
                    'cancelled' => $batch->cancelled(),
                    'failed' => $batch->failedJobs
                ]);
                $lastLoggedProgress = $batchProgress;
            }

            if ($batch->finished()) {
                $this->finishImport();
            } elseif ($batch->cancelled()) {
                $this->cancelImportUI();
            } elseif ($batch->failedJobs > 0) {
                $this->failImport();
            } else {
                $this->statusMessage = $batchProgress >= 100
                    ? "Menyelesaikan..."
                    : "Memproses data... ({$batchProgress}%)";
            }

        } catch (\Exception $e) {
            \Log::error('Error polling progress: ' . $e->getMessage());
        }
    }

    private function finishImport()
    {
        $this->isImporting = false;
        $this->progress = 100;

        // Ambil stats dari cache
        if ($this->batchId) {
            $stats = Cache::get('import_stats_' . $this->batchId);

            if ($stats && isset($stats['total_rows']) && $stats['total_rows'] > 0) {
                $totalRows = $stats['total_rows'] ?? 0;
                $successCount = $stats['success_count'] ?? 0;
                $skippedCount = $stats['skipped_count'] ?? 0;
                $failedCount = $stats['failed_count'] ?? 0;

                // 🔴 AMBIL SEMUA DATA YANG DIPERLUKAN
                $duplicateCount = $stats['duplicate_count'] ?? 0;
                $updateCount = $stats['update_count'] ?? 0;    // 🔴 TAMBAHKAN INI
                $insertCount = $stats['insert_count'] ?? 0;    // 🔴 TAMBAHKAN INI

                // Simpan hasil import ke property
                $this->importResult = [
                    'success' => true,
                    'processed_count' => $successCount,
                    'error_count' => $failedCount,
                    'duplicate_count' => $duplicateCount,
                    'update_count' => $updateCount,    // 🔴 TAMBAHKAN INI
                    'insert_count' => $insertCount,    // 🔴 TAMBAHKAN INI
                    'total_rows' => $totalRows,
                    'message' => $stats['message'] ?? ''
                ];

                // 🔴 PERBAIKAN PESAN: GUNAKAN UPDATE_COUNT DAN INSERT_COUNT
                if ($updateCount > 0 && $insertCount > 0) {
                    $this->statusMessage = sprintf(
                        'Import selesai! %d data baru ditambahkan, %d data diupdate',
                        $insertCount,
                        $updateCount
                    );
                } elseif ($updateCount > 0) {
                    $this->statusMessage = sprintf(
                        'Import selesai! %d data diupdate',
                        $updateCount
                    );
                } elseif ($insertCount > 0) {
                    $this->statusMessage = sprintf(
                        'Import selesai! %d data baru ditambahkan',
                        $insertCount
                    );
                } else {
                    $this->statusMessage = 'Import selesai!';
                }

                // Tambahkan keterangan jika ada duplikat dalam file
                if ($duplicateCount > 0) {
                    $this->statusMessage .= sprintf(' (%d data duplikat dalam file digabungkan)', $duplicateCount);
                }

                // Tambahkan keterangan jika ada yang benar-benar error
                if ($failedCount > 0) {
                    $this->statusMessage .= sprintf(' (%d gagal)', $failedCount);
                }

                // Tambahkan keterangan jika ada skipped
//                if ($skippedCount > 0) {
//                    $this->statusMessage .= sprintf(' (%d dilewati)', $skippedCount);
//                }
            } else {
                // Jika tidak ada stats, gunakan pesan default
                $this->statusMessage = 'Import selesai!';
            }

            // Bersihkan cache
            Cache::forget('batch_progress_' . $this->batchId);
            Cache::forget('batch_message_' . $this->batchId);
            Cache::forget('import_stats_' . $this->batchId);
        } else {
            $this->statusMessage = 'Import selesai!';
        }

        // Dispatch event khusus berdasarkan type untuk refresh halaman yang sesuai
        $this->dispatchImportCompleted();

        $this->dispatch('stop-polling');
        $this->dispatch('refresh');

        // Dispatch event untuk refresh poin GLOBALLY
        if ($this->importType === 'kelasimport' || $this->importType === 'cplimport') {
            $this->dispatch('points-updated'); // GLOBAL EVENT
        }

        LivewireAlert::title('')
            ->text('Import dari excel berhasil.')
            ->position('top-end')
            ->success()
            ->toast()
            ->timer(3000)
            ->show();
    }

    private function dispatchImportCompleted()
    {
        // Cek apakah import ini memotong poin, jika ya, refresh global
        $pointImpactingTypes = ['kelasimport', 'cplimport', 'nilaiimport', 'mahasiswaimport', 'prodiimport', 'cpmkimport', 'matakuliahimport'];
        if (in_array($this->importType, $pointImpactingTypes)) {
            $this->dispatch('points-updated');
        }

        // Dispatch event spesifik untuk refresh tabel/list
        switch ($this->importType) {
            case 'nilaiimport':
                $this->dispatch('nilai-import-completed');
                break;

            case 'cplimport':
                $this->dispatch('cpl-import-completed');
                break;

            case 'kelasimport':
                $this->dispatch('kelas-import-completed');
                break;

            case 'mahasiswaimport':
                $this->dispatch('mahasiswa-import-completed');
                break;

            case 'prodiimport':
                $this->dispatch('prodi-import-completed');
                break;

            case 'cpmkimport':
                $this->dispatch('cpmk-import-completed');
                break;

            case 'matakuliahimport':
                $this->dispatch('matakuliah-import-completed');
                break;

            default:
                $this->dispatch('import-completed');
                break;
        }
    }

    private function cancelImportUI()
    {
        $this->isImporting = false;
        $this->statusMessage = 'Import dibatalkan';
        $this->dispatch('import-cancelled');

        if ($this->batchId) {
            Cache::forget('batch_progress_' . $this->batchId);
            Cache::forget('batch_message_' . $this->batchId);
        }
    }

    private function failImport()
    {
        $this->isImporting = false;
        $this->statusMessage = 'Import gagal';
        $this->dispatch('import-failed');

        if ($this->batchId) {
            Cache::forget('batch_progress_' . $this->batchId);
            Cache::forget('batch_message_' . $this->batchId);
        }

        LivewireAlert::title('')
            ->text('Terjadi kesalahan saat import.')
            ->position('top-end')
            ->error()
            ->toast()
            ->timer(3000)
            ->show();
    }

    public function cancelImport()
    {
        if (!$this->batchId) {
            return;
        }

        try {
            $batch = Bus::findBatch($this->batchId);

            if (!$batch) {
                $this->isImporting = false;
                $this->statusMessage = 'Batch tidak ditemukan';
                return;
            }

            if ($batch->finished()) {
                LivewireAlert::title('')
                    ->text('Import sudah selesai')
                    ->position('top-end')
                    ->info()
                    ->toast()
                    ->timer(3000)
                    ->show();
                return;
            }

            if ($batch->cancelled()) {
                $this->isImporting = false;
                $this->statusMessage = 'Import sudah dibatalkan';
                return;
            }

            // Set cancellation flag
            Cache::put('batch_cancelled_' . $this->batchId, true, now()->addHours(1));

            // Cancel batch
            $batch->cancel();

            // Update UI
            $this->isImporting = false;
            $this->statusMessage = 'Import dibatalkan';
            Cache::put('batch_progress_' . $this->batchId, 0, now()->addHours(1));

            LivewireAlert::title('')
                ->text('Import telah dibatalkan.')
                ->position('top-end')
                ->warning()
                ->toast()
                ->timer(3000)
                ->show();

            \Log::info('Import cancelled by user', [
                'batch_id' => $this->batchId,
                'user_id' => auth()->id()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error cancelling import: ' . $e->getMessage());
            $this->isImporting = false;
            $this->statusMessage = 'Error saat membatalkan';
        }
    }

    public function closeModalNow()
    {
        $this->cleanup();
        $this->dispatch('closeModal');
    }

    public function cleanup()
    {
        $this->isImporting = false;
        $this->progress = 100;
        $this->batchId = null;
        $this->importResult = [ // 🔴 RESET IMPORT RESULT
            'success' => false,
            'processed_count' => 0,
            'error_count' => 0,
            'duplicate_count' => 0,
            'message' => ''
        ];

        /*
         * Reset properti point
         */
        $this->newDataCount = 0;
        $this->duplicateInFileCount = 0;
        $this->databaseDuplicateCount = 0;

        // Clear any cached data
        if ($this->batchId) {
            Cache::forget('batch_progress_' . $this->batchId);
            Cache::forget('batch_message_' . $this->batchId);
            Cache::forget('import_stats_' . $this->batchId);
        }
    }

    /**
     * Format error message untuk header validation
     */
    private function formatHeaderErrorMessage(string $message, string $importTypeLabel, array $expectedHeaders, array $foundHeaders): string
    {
        // Format expected headers untuk display
        $formattedExpected = array_map(function($header) {
            return "<code>" . htmlspecialchars($header) . "</code>";
        }, $expectedHeaders);

        // Format found headers untuk display
        $formattedFound = array_map(function($header) {
            return "<code>" . htmlspecialchars($header) . "</code>";
        }, $foundHeaders);

        return '
        <div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; max-width: 520px; margin: 0 auto; color: #1f2937;">
            <!-- Header dengan icon error -->
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e5e7eb;">
                <div style="background-color: #fee2e2; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 22px; height: 22px; color: #dc2626;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h2 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 4px 0;">Format File Tidak Sesuai</h2>
                    <p style="font-size: 14px; color: #6b7280; margin: 0;">Periksa struktur kolom file Anda</p>
                </div>
            </div>

            <!-- Informasi yang dibutuhkan -->
            <div style="margin-bottom: 20px;">
                <p style="font-size: 15px; color: #4b5563; margin: 0 0 12px 0;">
                    Untuk import <strong style="color: #111827;">' . $importTypeLabel . '</strong>, file harus memiliki kolom:
                </p>
                <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; margin-left: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                        <svg style="width: 16px; height: 16px; color: #10b981;" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span style="font-size: 14px; font-family: \'JetBrains Mono\', \'Courier New\', monospace; color: #059669; font-weight: 500;">' . implode(', ', $formattedExpected) . '</span>
                    </div>
                </div>
            </div>

            <!-- Kolom yang ditemukan -->
            <div style="margin-bottom: 24px;">
                <p style="font-size: 15px; color: #4b5563; margin: 0 0 12px 0;">
                    File yang Anda upload memiliki kolom:
                </p>
                <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; margin-left: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                        <svg style="width: 16px; height: 16px; color: #ef4444;" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span style="font-size: 14px; font-family: \'JetBrains Mono\', \'Courier New\', monospace; color: #dc2626; font-weight: 500;">' . implode(', ', $formattedFound) . '</span>
                    </div>
                </div>
            </div>

            <!-- Tips section -->
            <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 16px;">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <div style="background-color: #fbbf24; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg style="width: 18px; height: 18px; color: #78350f;" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h4 style="font-size: 15px; font-weight: 600; color: #92400e; margin: 0 0 6px 0;">Tips:</h4>
                        <p style="font-size: 14px; color: #78350f; margin: 0; line-height: 1.5;">
                            Download template yang sesuai atau periksa kembali struktur kolom file Excel Anda.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action button suggestion -->
            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                <p style="font-size: 13px; color: #6b7280; text-align: center; margin: 0;">
                    Pastikan format file sesuai sebelum mengupload kembali
                </p>
            </div>
        </div>';
    }

    private function getUserPoints(): int
    {
        try {
            $userPoint = \App\Models\UserPoint::where('user_id', auth()->id())->first();
            return $userPoint ? $userPoint->balance : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getPointsAfterImport($newDataCount): int
    {
        $currentPoints = $this->getUserPoints();

        // Cek import type mana yang pakai points
        $pointUsingTypes = [
            'mahasiswaimport' => 1,      // 1 point per mahasiswa baru
            'kelasimport' => 1,          // 1 point per kelas baru
            'cplimport' => 1,            // 1 point per CPL baru
            'prodiimport' => 1,          // 1 point per prodi baru
            'cpmkimport' => 1,           // 1 point per CPMK baru
            'matakuliahimport' => 1,     // 1 point per matkul baru
            'nilaiimport' => 0,          // 0 point (tidak pakai point)
        ];

        $pointCost = $pointUsingTypes[$this->importType] ?? 0;
        $pointsToDeduct = $newDataCount * $pointCost;

        return max(0, $currentPoints - $pointsToDeduct);
    }

    public function handleInstructionClosed()
    {
        // Cukup panggil refresh untuk memastikan state modal ini tetap terkunci (locked) di stack
        $this->dispatch('$refresh');
    }

    public function render()
    {
        return view('livewire.modal.import-manager');
    }

    public static function closeModalOnClickAway(): bool
    {
        return false;
    }

    public static function closeModalOnEscape(): bool
    {
        return false; // Mencegah tombol Esc menutup modal utama saat child aktif
    }

    public static function destroyOnClose(): bool
    {
        return false; // Memastikan state parent tetap ada saat child dibuka
    }


}
