<?php

namespace App\Livewire\Component;

use Livewire\Component;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TemplateExcel extends Component
{
    public $importType;

    public function mount($importType)
    {
        $this->importType = $importType;
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Tentukan header berdasarkan tipe import
        // Ini harus SINKRON dengan keyword di validateFileHeaders tadi
        $headers = match ($this->importType) {
            'mahasiswaimport' => ['nim', 'nama', 'jk', 'prodi', 'kelas', 'angkatan'],
            'kelasimport'     => ['nama_kelas'],
            'prodiimport'     => ['program_studi'],
            default           => ['data']
        };

        // Isi header ke baris pertama
        foreach ($headers as $key => $header) {
            $sheet->setCellValueByColumnAndRow($key + 1, 1, $header);
        }

        $fileName = 'Template_' . ucfirst($this->importType) . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName);
    }

    public function render()
    {
        return view('livewire.component.template-excel');
    }
}
