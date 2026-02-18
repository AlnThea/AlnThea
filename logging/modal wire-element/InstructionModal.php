<?php

namespace App\Livewire\Component;

use LivewireUI\Modal\ModalComponent;

class InstructionModal extends ModalComponent
{
    public $importType;

    public function mount($importType)
    {
        $this->importType = $importType;
    }

    // 🔴 PASTIKAN NAMA FUNGSI INI SAMA DENGAN DI BLADE
    public function backToImport()
    {
        // Kita panggil kembali modal parent dengan melempar parameter importType
        // Pastikan namespace 'modal.import-manager' sesuai dengan file ImportManager.php Anda
        $this->dispatch('openModal', component: 'modal.import-manager', arguments: [
            'type' => $this->importType
        ]);
    }

    public static function modalMaxWidth(): string
    {
        return '2xl';
    }

    public function render()
    {
        $exampleFiles = [
            'mahasiswaimport' => 'import mahasiswa.xlsx',
            'kelasimport'     => 'import kelas.xlsx',
            'prodiimport'     => 'import prodi.xlsx',
        ];

        $fileName = $exampleFiles[$this->importType] ?? 'template.xlsx';
        $label = str_replace('import', '', $this->importType);

        return view('livewire.component.instruction-modal', [
            'fileName' => $fileName,
            'label'    => $label
        ]);
    }
}
