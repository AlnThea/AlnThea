<div class="p-6">
    <div class="flex justify-between items-center mb-4 border-b pb-2">
        <h3 class="text-lg font-bold text-gray-800">Petunjuk Import {{ strtoupper($label) }}</h3>
        <button wire:click="$dispatch('closeModal')" class="text-gray-400 hover:text-gray-600">&times;</button>
    </div>

    <div class="bg-gray-50 border text-sm border-gray-200 rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">📌 PETUNJUK UPLOAD FILE</h2>
        <p>Silakan upload file data Anda dalam format <strong>.xlsx</strong> atau <strong>.xls</strong>.
            Jika file tidak tersedia, Anda dapat mendownload contoh yang sudah disediakan di bawah.</p>

        <h3 class="text-lg font-semibold mt-4 text-green-700">✅ YANG DITERIMA:</h3>
        <ul class="list-disc list-inside space-y-2">
            <li>File Excel (.xlsx atau .xls) dengan data rapi dan terstruktur.</li>
            <li>Pastikan kolom header sesuai dengan template.</li>
        </ul>

        <h3 class="text-lg font-semibold mt-4 text-blue-700">📂 CONTOH FILE:</h3>
        <p class="mt-2">
            Unduh contoh format file Excel yang benar ➡️
            <a href="{{ asset('storage/dummy/' . $fileName) }}" target="_blank"
               class="text-blue-600 underline font-bold hover:text-blue-800">
                Download Contoh File {{ strtoupper($label) }}
            </a>
        </p>

        <div class="mt-6 p-4 bg-yellow-50 border-l-4 border-yellow-400">
            <h3 class="text-sm font-bold text-yellow-800">Catatan:</h3>
            <p class="text-gray-600 mt-1 italic">*Pastikan struktur kolom dan data sesuai contoh untuk memudahkan proses import*</p>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="button"
                wire:click="backToImport"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold shadow-md">
            ✅ SAYA MENGERTI, LANJUTKAN IMPORT
        </button>
    </div>
</div>

{{-- SOAL MODAL CHILD UNTUK DOWNLOAD TEMPLATE--}}
