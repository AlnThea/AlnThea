<div class="p-6 flex flex-col" style="height: 80vh; max-height: 80vh;">
    {{-- Header --}}
    <div class="mb-4 border-b pb-4 flex justify-between items-center flex-shrink-0">
        <h3 class="text-lg font-bold text-gray-800">
            Import Data <span class="text-blue-600">{{ strtoupper($importType) }}</span>
        </h3>

        @livewire('component.template-excel', ['importType' => $importType])
    </div>

    {{-- STEP 1: PILIH FILE --}}
    @if($step == 1)
        <div x-transition.opacity
             class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 p-10 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors duration-300 flex-grow">
            <input type="file" wire:model="file" id="file_upload" class="hidden">

            <div class="mb-4 text-gray-400">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
            </div>

            <label for="file_upload"
                   class="cursor-pointer bg-blue-600 text-white px-6 py-2.5 rounded-lg shadow hover:bg-blue-700 transition active:scale-95 font-semibold">
                Pilih File Excel
            </label>

            <p class="mt-4 text-xs text-gray-500 uppercase tracking-wider font-medium">Maksimal 10MB (xlsx, xls,
                csv)</p>

            <div wire:loading wire:target="file"
                 class="mt-4 flex items-center justify-center text-blue-600 font-semibold animate-pulse">
                <svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                            fill="none"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Membaca file... mohon tunggu...
            </div>
        </div>
        <div class="mt-4 flex justify-end space-x-3">
            <button wire:click="$dispatch('closeModal')"
                    class="px-5 py-2.5 bg-red-600 border border-red-300 rounded-lg text-white hover:bg-red-400 transition font-medium text-sm shadow-sm hover:shadow cursor-pointer">
                Batal
            </button>
        </div>

    @endif

    {{-- STEP 2: PREVIEW DATA --}}
    @if($step == 2)
        <div x-transition class="space-y-6 flex flex-col flex-grow overflow-hidden" x-data="{ activeTab: 'preview' }">

            {{-- TAB NAVIGASI --}}
            <div class="border-b border-gray-200 flex-shrink-0">
                <nav class="-mb-px flex space-x-8">
                    {{-- TAB PREVIEW --}}
                    <button @click="activeTab = 'preview'"
                            :class="activeTab === 'preview'
                            ? 'border-blue-500 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex items-center py-3 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2" :class="activeTab === 'preview' ? 'text-blue-500' : 'text-gray-400'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Preview Data
                        @php
                            $errorCount = 0;
                            $warningCount = 0;
                            $inFileDuplicateCount = 0; // 🔴 TAMBAHKAN INI

                            foreach ($previewData as $row) {
                                if (!$row['_validation']) {
                                    $errorCount++;
                                } elseif (isset($row['is_in_file_duplicate']) && $row['is_in_file_duplicate']) {
                                    $inFileDuplicateCount++; // 🔴 HITUNG DUPLIKAT DALAM FILE
                                    $warningCount++;
                                } elseif (isset($row['is_duplicate']) && $row['is_duplicate']) {
                                    $warningCount++;
                                } elseif (str_contains($row['_validation_message'] ?? '', '⚠️')) {
                                    $warningCount++;
                                }
                            }
                            $totalIssues = $errorCount + $warningCount;
                        @endphp
                        @if($totalIssues > 0)
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $errorCount > 0 ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $totalIssues }} issue
                        </span>
                        @endif
                    </button>

                    {{-- TAB PESAN VALIDASI --}}
                    @if($totalIssues > 0)
                        <button @click="activeTab = 'messages'"
                                :class="activeTab === 'messages'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="flex items-center py-3 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2"
                                 :class="activeTab === 'messages' ? 'text-blue-500' : 'text-gray-400'" fill="none"
                                 stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            Pesan Validasi
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $errorCount > 0 ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $totalIssues }}
                            </span>
                        </button>
                    @endif

                    {{-- TAB RINGKASAN --}}
                    <button @click="activeTab = 'summary'"
                            :class="activeTab === 'summary'
                            ? 'border-blue-500 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="flex items-center py-3 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2" :class="activeTab === 'summary' ? 'text-blue-500' : 'text-gray-400'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Ringkasan
                    </button>
                </nav>
            </div>

            {{-- KONTEN TAB DENGAN SCROLL --}}
            <div class="tab-contents flex-grow overflow-hidden">
                {{-- TAB 1: PREVIEW DATA DENGAN SCROLL --}}
                <div x-show="activeTab === 'preview'" x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="h-full flex flex-col space-y-4">

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <!-- Total Rows Card -->
                        <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500 font-medium">Total Preview</div>
                                    <div class="text-lg font-bold text-gray-800">{{ count($previewData) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- New Data Card -->
                        @if($newDataCount > 0)
                            <div class="bg-white border border-green-100 rounded-lg p-3 shadow-sm">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 font-medium">Data Baru</div>
                                        <div class="text-lg font-bold text-green-700">{{ $newDataCount }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Database Duplicate Card -->
                        @if($databaseDuplicateCount > 0)
                            <div class="bg-white border border-amber-100 rounded-lg p-3 shadow-sm">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center mr-3">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 font-medium">Duplikat DB</div>
                                        <div
                                            class="text-lg font-bold text-amber-700">{{ $databaseDuplicateCount }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- File Duplicate Card -->
                        @if($duplicateInFileCount > 0)
                            <div class="bg-white border border-purple-100 rounded-lg p-3 shadow-sm">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center mr-3">
                                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 font-medium">Duplikat File</div>
                                        <div class="text-lg font-bold text-purple-700">{{ $duplicateInFileCount }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Points Information -->
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-4">
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-between space-y-3 md:space-y-0">
                            <div class="flex items-center space-x-4">
                                <div class="bg-white p-2 rounded-lg shadow-sm border border-blue-100">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-blue-800">Saldo Poin</div>
                                    <div class="flex items-center space-x-3">
                                        <div class="text-2xl font-bold text-blue-900">{{ $currentPoints }}</div>
                                        @if($newDataCount > 0 && $pointsAfter < $currentPoints)
                                            <div class="flex items-center text-blue-700">
                                                <svg class="w-4 h-4 mx-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                          d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z"
                                                          clip-rule="evenodd"></path>
                                                </svg>
                                                <div class="text-xl font-bold text-green-600">{{ $pointsAfter }}</div>
                                                <span class="text-sm font-semibold text-red-600 ml-1">(-{{ $currentPoints - $pointsAfter }})</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($newDataCount > 0 && $pointsAfter < $currentPoints)
                                <div class="bg-white rounded-lg p-3 border border-blue-200 shadow-sm">
                                    <div class="text-sm font-medium text-gray-700 mb-1">Perhitungan Poin:</div>
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">Data baru:</span>
                                            <span class="font-semibold text-green-700">{{ $newDataCount }} × 1 = {{ $currentPoints - $pointsAfter }} point</span>
                                        </div>
                                        @if($databaseDuplicateCount > 0)
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-600">Duplikat database:</span>
                                                <span class="font-semibold text-amber-600">{{ $databaseDuplicateCount }} data (0 point)</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="flex-grow overflow-hidden flex flex-col">
                        <div
                            class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden flex-grow flex flex-col">
                            <div class="overflow-x-auto flex-grow">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider bg-gray-50">
                                            #
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider bg-gray-50">
                                            Status
                                        </th>
                                        @php
                                            $displayKeys = array_filter(array_keys($previewData[0] ?? []), function($key) {
                                                return !str_starts_with($key, '_');
                                            });
                                        @endphp
                                        @foreach($displayKeys as $key)
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider bg-gray-50">
                                                {{ $previewData[0]['_display_' . $key] ?? ucfirst(str_replace('_', ' ', $key)) }}
                                            </th>
                                        @endforeach
                                    </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($previewData as $index => $row)
                                        @php
                                            $rowNumber = $index + 1;
                                            $isValid = $row['_validation'] ?? false;
                                            $isError = !$isValid;
                                            $isDuplicate = $row['is_duplicate'] ?? false;
                                            $isDatabaseDuplicate = $row['is_database_duplicate'] ?? false;
                                            $isInFileDuplicate = $row['is_in_file_duplicate'] ?? false;
                                            $hasWarning = str_contains($row['_validation_message'] ?? '', '⚠️');

                                            // Determine status with better styling
                                            if ($isError) {
                                                $statusClass = 'border-l-4 border-red-500 bg-red-50';
                                                $statusIcon = '<svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
                                                $statusText = 'Error';
                                                $statusColor = 'text-red-700';
                                            } elseif ($isInFileDuplicate) {
                                                $statusClass = 'border-l-4 border-purple-500 bg-purple-50';
                                                $statusIcon = '<svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 6a1 1 0 112 0 1 1 0 01-2 0z" clip-rule="evenodd"></path><path d="M10 12h4a1 1 0 001-1v-3a1 1 0 00-1-1h-4v5z"></path></svg>';
                                                $statusText = 'Duplikat File';
                                                $statusColor = 'text-purple-700';
                                            } elseif ($isDatabaseDuplicate) {
                                                $statusClass = 'border-l-4 border-amber-500 bg-amber-50';
                                                $statusIcon = '<svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
                                                $statusText = 'Duplikat DB';
                                                $statusColor = 'text-amber-700';
                                            } elseif ($hasWarning) {
                                                $statusClass = 'border-l-4 border-amber-500 bg-amber-50';
                                                $statusIcon = '<svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
                                                $statusText = 'Peringatan';
                                                $statusColor = 'text-amber-700';
                                            } else {
                                                $statusClass = 'border-l-4 border-green-500 bg-green-50';
                                                $statusIcon = '<svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
                                                $statusText = 'Valid';
                                                $statusColor = 'text-green-700';
                                            }
                                        @endphp

                                        <tr class="{{ $statusClass }} hover:bg-opacity-80 transition-colors">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-center">
                                        <span
                                            class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">
                                            {{ $rowNumber }}
                                        </span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="flex items-center space-x-2">
                                                    <div class="flex-shrink-0">
                                                        {!! $statusIcon !!}
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span
                                                            class="text-xs font-semibold {{ $statusColor }}">{{ $statusText }}</span>
                                                        @if($isInFileDuplicate || $isDatabaseDuplicate)
                                                            <span
                                                                class="text-xs {{ $isInFileDuplicate ? 'text-purple-600' : 'text-amber-600' }}">
                                                    {{ $isInFileDuplicate ? '(Dalam file)' : '(Database)' }}
                                                </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            @foreach($displayKeys as $key)
                                                <td class="px-4 py-3">
                                                    <div class="max-w-xs truncate group relative">
                                                        @php
                                                            $cellValue = $row[$key] ?? '-';
                                                            $isBooleanKey = in_array($key, ['is_duplicate', 'is_in_file_duplicate', 'is_database_duplicate', '_validation']);
                                                            $showTooltip = !$isBooleanKey && $cellValue !== '-' && is_string($cellValue) && strlen($cellValue) > 30;
                                                        @endphp

                                                        <span class="text-sm text-gray-800">
                @if($isBooleanKey)
                                                                @if($cellValue === true || $cellValue === '1')
                                                                    <span
                                                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">Ya</span>
                                                                @elseif($cellValue === false || $cellValue === '0' || $cellValue === '')
                                                                    <span
                                                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-50 text-gray-500">Tidak</span>
                                                                @else
                                                                    {{ $cellValue }}
                                                                @endif
                                                            @else
                                                                {{ $cellValue }}
                                                            @endif
            </span>

                                                        @if($showTooltip)
                                                            <div
                                                                class="absolute invisible group-hover:visible z-20 left-0 bottom-full mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg whitespace-nowrap">
                                                                {{ $cellValue }}
                                                                <div
                                                                    class="absolute left-4 top-full w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-900"></div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Information -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-2 md:space-y-0">
                        <div class="text-sm text-gray-500">
                            @if($totalData > count($previewData))
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Menampilkan <span
                                        class="font-semibold text-gray-700">{{ count($previewData) }}</span> dari
                                    <span class="font-semibold text-gray-700">{{ number_format($totalData) }}</span>
                                    baris
                                </div>
                            @else
                                <div class="flex items-center text-green-600">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                              clip-rule="evenodd"></path>
                                    </svg>
                                    Semua <span class="font-semibold mx-1">{{ number_format($totalData) }}</span> baris
                                    ditampilkan
                                </div>
                            @endif
                        </div>

                        <!-- Quick Stats -->
                        <div class="flex flex-wrap gap-2">
                            @if($newDataCount > 0)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"
                                              clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $newDataCount }} data baru
                                </span>
                            @endif
                            @if($databaseDuplicateCount > 0)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                              clip-rule="evenodd"></path>
                    </svg>
                    {{ $databaseDuplicateCount }} duplikat DB
                </span>
                            @endif
                            @if($duplicateInFileCount > 0)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                              clip-rule="evenodd"></path>
                    </svg>
                    {{ $duplicateInFileCount }} duplikat file
                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- TAB 2: PESAN VALIDASI DENGAN SCROLL (SEMUA PESAN: ERROR & PERINGATAN) --}}
                @if($totalIssues > 0)
                    <div x-show="activeTab === 'messages'" x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="h-full overflow-y-auto pr-2">
                        <div class="space-y-4 pb-4">

                            {{-- HEADER INFORMASI --}}
                            <div
                                class="bg-gradient-to-r from-gray-50 to-gray-100 border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-bold text-gray-800 text-lg">Pesan Validasi</h3>
                                        <p class="text-sm text-gray-600 mt-1">
                                            Total {{ $totalIssues }} pesan yang perlu diperhatikan
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        @if($errorCount > 0)
                                            <div class="flex items-center">
                                                <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                                                <span
                                                    class="text-sm font-medium text-gray-700">{{ $errorCount }} Error</span>
                                            </div>
                                        @endif
                                        @if($warningCount > 0)
                                            <div class="flex items-center">
                                                <span class="w-3 h-3 bg-amber-500 rounded-full mr-2"></span>
                                                <span class="text-sm font-medium text-gray-700">{{ $warningCount }} Peringatan</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- DAFTAR SEMUA PESAN (CAMPUR ERROR DAN WARNING) --}}
                            <div class="space-y-3">
                                @foreach($previewData as $index => $row)
                                    @php
                                        $rowNum = $index + 1;
                                        $isError = !($row['_validation'] ?? false);

                                        // 🔴 PERBAIKAN: Tambah pengecekan duplikasi dalam file
                                        $isDuplicate = $row['is_duplicate'] ?? false;
                                        $isInFileDuplicate = $row['is_in_file_duplicate'] ?? false;
                                        $hasWarning = str_contains($row['_validation_message'] ?? '', '⚠️') || $isDuplicate || $isInFileDuplicate;

                                        // Skip jika tidak ada issue
                                        if (!$isError && !$hasWarning) continue;

                                        $message = $row['_validation_message'] ?? '';
                                        if ($isInFileDuplicate && empty($message)) {
                                            $message = '🔄 Duplikat dalam file (tidak akan diproses)';
                                        } elseif ($isDuplicate && empty($message)) {
                                            $message = '⚠️ Data sudah ada (akan diupdate)';
                                        }
                                    @endphp

                                    <div class="bg-white border rounded-lg overflow-hidden
                                        {{ $isError ? 'border-red-200' : ($isInFileDuplicate ? 'border-purple-200' : 'border-amber-200') }}
                                        hover:shadow-md transition-shadow">
                                        <div
                                            class="px-4 py-3 flex items-start {{ $isError ? 'bg-red-50' : ($isInFileDuplicate ? 'bg-purple-50' : 'bg-amber-50') }}">
                                            <div class="flex-shrink-0 mr-3">
                                                @if($isError)
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-red-600" fill="currentColor"
                                                             viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                                  clip-rule="evenodd"></path>
                                                        </svg>
                                                    </div>
                                                @elseif($isInFileDuplicate)
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-purple-600" fill="currentColor"
                                                             viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                  d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 6a1 1 0 112 0 1 1 0 01-2 0z"
                                                                  clip-rule="evenodd"></path>
                                                            <path d="M10 12h4a1 1 0 001-1v-3a1 1 0 00-1-1h-4v5z"></path>
                                                        </svg>
                                                    </div>
                                                @else
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-amber-600" fill="currentColor"
                                                             viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                                  clip-rule="evenodd"></path>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <div class="flex items-center">
                                                            <span
                                                                class="font-bold {{ $isError ? 'text-red-800' : ($isInFileDuplicate ? 'text-purple-800' : 'text-amber-800') }}">
                                                                {{ $isError ? '❌ ERROR' : ($isInFileDuplicate ? '🔄 DUPLIKAT FILE' : '⚠️ PERINGATAN') }}
                                                            </span>
                                                            <span
                                                                class="ml-3 px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-mono rounded">
                                                                Baris {{ $rowNum }}
                                                            </span>
                                                            @if($isInFileDuplicate)
                                                                <span
                                                                    class="ml-2 px-2 py-0.5 bg-purple-100 text-purple-800 text-xs font-medium rounded">
                                                                    Duplikat dalam file
                                                                </span>
                                                            @elseif($isDuplicate)
                                                                <span
                                                                    class="ml-2 px-2 py-0.5 bg-amber-100 text-amber-800 text-xs font-medium rounded">
                                                                    Duplikat database
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <p class="mt-1 {{ $isError ? 'text-red-700' : ($isInFileDuplicate ? 'text-purple-700' : 'text-amber-700') }} font-medium">
                                                            {{ $message }}
                                                        </p>
                                                    </div>
                                                    <div
                                                        class="text-xs {{ $isError ? 'text-red-600' : 'text-amber-600' }} font-medium">
                                                        {{ $isError ? 'Harus diperbaiki' : 'Akan diupdate' }}
                                                    </div>
                                                </div>

                                                {{-- DETAIL DATA BARIS --}}
                                                <div class="mt-3 p-2 bg-white border rounded text-xs">
                                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                                        @if(isset($row['nim_mahasiswa']))
                                                            <div>
                                                                <span class="font-semibold text-gray-600">NIM:</span>
                                                                <span class="ml-1">{{ $row['nim_mahasiswa'] }}</span>
                                                            </div>
                                                        @endif
                                                        @if(isset($row['cpmk']))
                                                            <div>
                                                                <span class="font-semibold text-gray-600">CPMK:</span>
                                                                <span class="ml-1">{{ $row['cpmk'] }}</span>
                                                            </div>
                                                        @endif
                                                        @if(isset($row['matkul_id']))
                                                            <div>
                                                                <span
                                                                    class="font-semibold text-gray-600">Matkul ID:</span>
                                                                <span class="ml-1">{{ $row['matkul_id'] }}</span>
                                                            </div>
                                                        @endif
                                                        @if(isset($row['class_id']))
                                                            <div>
                                                                <span class="font-semibold text-gray-600">Kelas:</span>
                                                                <span class="ml-1">{{ $row['class_id'] }}</span>
                                                            </div>
                                                        @endif
                                                        @if(isset($row['kode_mk']))
                                                            <div>
                                                                <span
                                                                    class="font-semibold text-gray-600">Kode MK:</span>
                                                                <span class="ml-1">{{ $row['kode_mk'] }}</span>
                                                            </div>
                                                        @endif
                                                        @if(isset($row['semester']))
                                                            <div>
                                                                <span
                                                                    class="font-semibold text-gray-600">Semester:</span>
                                                                <span class="ml-1">{{ $row['semester'] }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                {{-- TINDAKAN YANG DIPERLUKAN --}}
                                                <div
                                                    class="mt-2 flex items-center text-sm {{ $isError ? 'text-red-600 bg-red-50' : 'text-amber-600 bg-amber-50' }} px-3 py-2 rounded">
                                                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="currentColor"
                                                         viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                              clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span>
                                                        @if($isError)
                                                            @if(str_contains($message, 'Kode MK tidak sesuai'))
                                                                <span class="font-semibold">Tindakan Diperlukan:</span>
                                                                Perbaiki kode MK agar sesuai dengan database.
                                                            @elseif(str_contains($message, 'CPMK tidak ditemukan'))
                                                                <span class="font-semibold">Tindakan Diperlukan:</span>
                                                                Pastikan CPMK sudah dibuat untuk matakuliah ini.
                                                            @elseif(str_contains($message, 'Matkul tidak ditemukan'))
                                                                <span class="font-semibold">Tindakan Diperlukan:</span>
                                                                Pastikan matakuliah sudah dibuat atau gunakan ID yang
                                                                benar.
                                                            @else
                                                                <span class="font-semibold">Tindakan Diperlukan:</span>
                                                                Periksa dan perbaiki data di baris ini.
                                                            @endif
                                                        @else
                                                            <span class="font-semibold">Tindakan:</span> Data akan
                                                            diupdate dengan nilai baru saat import.
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    </div>
                @endif

                {{-- TAB 3: RINGKASAN DENGAN SCROLL --}}
                <div x-show="activeTab === 'summary'" x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="h-full overflow-y-auto pr-2">
                    <div class="space-y-6 pb-4">

                        {{-- KARTU STATISTIK --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            @php
                                $validCount = 0;
                                $duplicateCount = 0;
                                $inFileDuplicateCount = 0; // 🔴 TAMBAHKAN INI
                                $errorCount = 0;

                                foreach ($previewData as $row) {
                                    if (!($row['_validation'] ?? false)) {
                                        $errorCount++;
                                    } elseif ($row['is_in_file_duplicate'] ?? false) {
                                        $inFileDuplicateCount++; // 🔴 HITUNG DUPLIKAT DALAM FILE
                                    } elseif ($row['is_duplicate'] ?? false) {
                                        $duplicateCount++;
                                    } else {
                                        $validCount++;
                                    }
                                }
                            @endphp

                            <div
                                class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                      clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-blue-600">Baris Preview</p>
                                        <p class="text-2xl font-bold text-blue-800">{{ count($previewData) }}</p>
                                        <p class="text-xs text-blue-500">dari {{ number_format($totalData) }} total</p>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                      clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-green-600">Baris Valid</p>
                                        <p class="text-2xl font-bold text-green-800">{{ $validCount }}</p>
                                        <p class="text-xs text-green-500">{{ $totalData > 0 ? round(($validCount/$totalData)*100) : 0 }}
                                            % dari total</p>
                                    </div>
                                </div>
                            </div>

                            @if($duplicateCount > 0)
                                <div
                                    class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-xl p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-amber-600" fill="currentColor"
                                                     viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                          d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                          clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-amber-600">Duplikat</p>
                                            <p class="text-2xl font-bold text-amber-800">{{ $duplicateCount }}</p>
                                            <p class="text-xs text-amber-500">Akan diupdate</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($inFileDuplicateCount > 0)
                                <div
                                    class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-xl p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-purple-600" fill="currentColor"
                                                     viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                          d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 6a1 1 0 112 0 1 1 0 01-2 0z"
                                                          clip-rule="evenodd"></path>
                                                    <path d="M10 12h4a1 1 0 001-1v-3a1 1 0 00-1-1h-4v5z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-purple-600">Duplikat dalam File</p>
                                            <p class="text-2xl font-bold text-purple-800">{{ $inFileDuplicateCount }}</p>
                                            <p class="text-xs text-purple-500">Tidak akan diproses</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($errorCount > 0)
                                <div
                                    class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-red-600" fill="currentColor"
                                                     viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                          d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                          clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-red-600">Error</p>
                                            <p class="text-2xl font-bold text-red-800">{{ $errorCount }}</p>
                                            <p class="text-xs text-red-500">Harus diperbaiki</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- RINGKASAN IMPORT --}}
                        <div class="bg-white border border-gray-200 rounded-xl p-6">
                            <h3 class="font-bold text-gray-800 text-lg mb-4">Ringkasan Import</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                    <span class="text-gray-600">Total baris di file</span>
                                    <span class="font-semibold text-gray-800">{{ number_format($totalData) }}</span>
                                </div>
                                <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                    <span class="text-gray-600">Baris di preview</span>
                                    <span class="font-semibold text-gray-800">{{ count($previewData) }}</span>
                                </div>
                                <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                <span class="flex items-center text-green-600">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                              clip-rule="evenodd"></path>
                                    </svg>
                                    Baris valid
                                </span>
                                    <span class="font-semibold text-green-700">{{ $validCount }}</span>
                                </div>
                                @if($duplicateCount > 0)
                                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                        <span class="flex items-center text-amber-600">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                      clip-rule="evenodd"></path>
                                            </svg>
                                            Duplikat
                                        </span>
                                        <span class="font-semibold text-amber-700">{{ $duplicateCount }}</span>
                                    </div>
                                @endif

                                @if($inFileDuplicateCount > 0)
                                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                        <span class="flex items-center text-purple-600">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 6a1 1 0 112 0 1 1 0 01-2 0z"
                                                      clip-rule="evenodd"></path>
                                                <path d="M10 12h4a1 1 0 001-1v-3a1 1 0 00-1-1h-4v5z"></path>
                                            </svg>
                                            Duplikat dalam file
                                        </span>
                                        <span class="font-semibold text-purple-700">{{ $inFileDuplicateCount }}</span>
                                    </div>
                                @endif
                                @if($errorCount > 0)
                                    <div class="flex items-center justify-between py-3">
                                <span class="flex items-center text-red-600">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                              clip-rule="evenodd"></path>
                                    </svg>
                                    Error
                                </span>
                                        <span class="font-semibold text-red-700">{{ $errorCount }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- STATUS IMPORT --}}
                        <div class="bg-white border border-gray-200 rounded-xl p-6">
                            <h3 class="font-bold text-gray-800 text-lg mb-4">Status Import</h3>
                            @if($errorCount > 0)
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <svg class="w-6 h-6 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                  clip-rule="evenodd"></path>
                                        </svg>
                                        <div>
                                            <h4 class="font-bold text-red-800">Import Diblokir</h4>
                                            <p class="text-sm text-red-600 mt-1">
                                                Terdapat {{ $errorCount }} error yang harus diperbaiki sebelum
                                                melanjutkan.
                                                Silakan periksa tab "Pesan Validasi" untuk detailnya.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @elseif(isset($importResult['duplicate_count']) && $importResult['duplicate_count'] > 0)
                                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <svg class="w-6 h-6 text-amber-500 mr-3" fill="currentColor"
                                             viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                  clip-rule="evenodd"></path>
                                        </svg>
                                        <div>
                                            <h4 class="font-bold text-amber-800">Selesai
                                                ({{ $importResult['duplicate_count'] }} data diupdate)</h4>
                                            <p class="text-sm text-amber-600 mt-1">
                                                {{ $importResult['duplicate_count'] }} data duplikat telah diupdate
                                                dengan nilai baru.
                                                {{ $importResult['processed_count'] }} data baru telah ditambahkan.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor"
                                             viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                  clip-rule="evenodd"></path>
                                        </svg>
                                        <div>
                                            <h4 class="font-bold text-green-800">Siap Diimport</h4>
                                            <p class="text-sm text-green-600 mt-1">
                                                Semua data valid. {{ $validCount }} data baru akan ditambahkan ke
                                                sistem.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

            </div>

            {{-- TOMBOL AKSI (tetap di bawah) --}}
            <div class="flex justify-between items-center pt-4 border-t border-gray-200 flex-shrink-0">
                <div>
                    @if($totalData > count($previewData))
                        <div class="text-sm text-gray-500 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                      clip-rule="evenodd"></path>
                            </svg>
                            Menampilkan {{ count($previewData) }} dari {{ number_format($totalData) }} baris
                        </div>
                    @endif

                        <div class="flex justify-start items-center">
                            <button wire:click="$dispatch('closeModal')"
                                    class="px-5 py-2.5 bg-red-600 border border-red-300 rounded-lg text-white hover:bg-red-400 transition font-medium text-sm shadow-sm hover:shadow cursor-pointer">
                                Batal
                            </button>
                        </div>
                </div>

                <div class="flex gap-3">
                    <button wire:click="$set('step', 1)"
                            class="px-5 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition font-medium text-sm shadow-sm hover:shadow">
                        Ganti File
                    </button>
                    @if($errorCount > 0)
                        <button
                            class="px-6 py-2.5 bg-red-50 text-red-700 rounded-lg font-semibold text-sm cursor-not-allowed border border-red-200 flex items-center gap-2"
                            disabled>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                      clip-rule="evenodd"></path>
                            </svg>
                            Perbaiki Error Dulu
                        </button>
                    @else
                        <button wire:click="startImport"
                                wire:loading.attr="disabled"
                                class="px-6 py-2.5 rounded-lg font-semibold transition-all duration-200 shadow text-sm
                                {{ $isImporting
                                    ? 'bg-gray-400 cursor-not-allowed text-gray-200'
                                    : 'bg-gradient-to-r from-blue-600 to-blue-700 text-white hover:from-blue-700 hover:to-blue-800 active:scale-[0.98] cursor-pointer shadow-lg hover:shadow-xl' }}"
                                @if($isImporting) disabled @endif>

                            <div class="flex items-center justify-center">
                        <span wire:loading wire:target="startImport" class="mr-2">
                            <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                        fill="none"></circle>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </span>
                                <span>{{ $isImporting ? 'Memproses...' : 'Lanjutkan Import' }}</span>
                            </div>
                        </button>
                    @endif
                </div>
            </div>

        </div>
    @endif

    {{-- Di view - STEP 3: PROGRESS & COMPLETION --}}
    @if($step == 3)
        <div x-transition class="py-8 text-center flex flex-col flex-grow"
             x-data="{ timeLeft: 3 }">

            {{-- Polling Signal --}}
            @if($isImporting && $progress < 100)
                <div wire:poll.2s="pollProgress"></div>
            @endif

            {{-- Progress Bar (Tampil saat jalan ATAU saat dibatalkan agar user lihat posisi terakhir) --}}
            @if($progress > 0)
                <div class="max-w-md mx-auto mb-8 px-4">
                    <div class="flex justify-between mb-2">
                        <span
                            class="text-sm font-semibold text-blue-700 {{ $isImporting ? 'animate-pulse' : '' }}">{{ $statusMessage }}</span>
                        <span class="text-sm font-bold text-blue-700">{{ round($progress) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden border shadow-inner">
                        <div
                            class="bg-blue-600 h-4 rounded-full transition-all duration-500 ease-out shadow-[0_0_10px_rgba(37,99,235,0.4)]"
                            style="width: {{ $progress }}%"></div>
                    </div>
                </div>
            @endif

            {{-- 1. Loading State (Hanya tampil jika sedang proses) --}}
            @if($isImporting && $progress < 100)
                <div class="flex flex-col items-center justify-center flex-grow">
                    <div
                        class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent shadow-sm"></div>
                    <p class="text-gray-500 mt-4 font-medium italic">Data sedang diproses, mohon tidak menutup jendela
                        ini...</p>

                    <button wire:click="cancelImport"
                            class="mt-8 text-sm text-red-500 hover:text-red-700 font-semibold underline decoration-dotted">
                        Batalkan Import
                    </button>
                </div>
            @endif

            {{-- 2. Cancelled State --}}
            @if(!$isImporting && $progress < 100 && str_contains(strtolower($statusMessage), 'batal'))
                <div x-transition class="flex flex-col items-center justify-center flex-grow">
                    <div class="bg-amber-100 p-4 rounded-full mb-4 shadow-sm text-amber-600">
                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800">Import Dibatalkan</h2>
                    <p class="mt-2 text-gray-600 font-medium">{{ $statusMessage }}</p>

                    <div class="flex gap-4 mt-8">
                        <button wire:click="$set('step', 1)"
                                class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-bold text-sm">
                            Coba Lagi
                        </button>
                        <button wire:click="closeModalNow"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-lg font-bold text-sm">
                            Tutup
                        </button>
                    </div>
                </div>
            @endif

            {{-- 3. Success State --}}
            @if($progress >= 100)
                <div x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 scale-90"
                     class="flex flex-col items-center justify-center flex-grow">

                    {{-- Icon berdasarkan ada/tidaknya update --}}
                    @if(($importResult['update_count'] ?? 0) > 0)
                        <div class="bg-amber-100 p-4 rounded-full mb-4 shadow-sm">
                            <svg class="w-16 h-16 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Import Selesai!</h2>
                        <p class="text-amber-600 font-medium mt-2">(dengan update data yang sudah ada)</p>
                    @else
                        <div class="bg-green-100 p-4 rounded-full mb-4 shadow-sm">
                            <svg class="w-16 h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                      d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Import Selesai!</h2>
                    @endif

                    <div class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-100 text-sm text-gray-600">
                        {{ $statusMessage }}
                    </div>

                    {{-- 🔴 TAMBAHKAN: Tampilkan detail jika ada update/insert --}}
                    @if(($importResult['insert_count'] ?? 0) > 0 || ($importResult['update_count'] ?? 0) > 0)
                        <div class="mt-4 p-4 bg-white rounded-lg border border-gray-200 shadow-sm max-w-md">
                            <div class="flex items-center mb-3">
                                <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                          d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                          clip-rule="evenodd"></path>
                                </svg>
                                <h3 class="font-bold text-gray-800">Detail Import</h3>
                            </div>

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total data di file:</span>
                                    <span class="font-semibold">{{ $importResult['total_rows'] ?? 0 }}</span>
                                </div>

                                @if(($importResult['insert_count'] ?? 0) > 0)
                                    <div class="flex justify-between items-center">
                        <span class="flex items-center text-green-600">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                      clip-rule="evenodd"></path>
                            </svg>
                            Data baru ditambahkan:
                        </span>
                                        <span
                                            class="font-bold text-green-700">{{ $importResult['insert_count'] }}</span>
                                    </div>
                                @endif

                                @if(($importResult['update_count'] ?? 0) > 0)
                                    <div class="flex justify-between items-center">
                        <span class="flex items-center text-amber-600">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                      clip-rule="evenodd"></path>
                            </svg>
                            Data diupdate:
                        </span>
                                        <span
                                            class="font-bold text-amber-700">{{ $importResult['update_count'] }}</span>
                                    </div>
                                @endif

                                @if(($importResult['duplicate_count'] ?? 0) > 0)
                                    <div class="flex justify-between items-center">
                        <span class="flex items-center text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z"
                                      clip-rule="evenodd"></path>
                            </svg>
                            Duplikat dalam file:
                        </span>
                                        <span
                                            class="font-bold text-gray-600">{{ $importResult['duplicate_count'] }}</span>
                                    </div>
                                @endif

                                @if(($importResult['error_count'] ?? 0) > 0)
                                    <div class="flex justify-between items-center">
                        <span class="flex items-center text-red-600">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                      clip-rule="evenodd"></path>
                            </svg>
                            Gagal diproses:
                        </span>
                                        <span class="font-bold text-red-700">{{ $importResult['error_count'] }}</span>
                                    </div>
                                @endif
                            </div>

                            @if(($importResult['update_count'] ?? 0) > 0)
                                <div
                                    class="mt-3 p-2 bg-amber-50 rounded text-xs text-amber-800 border border-amber-200">
                                    <div class="flex items-start">
                                        <svg class="w-4 h-4 mt-0.5 mr-2 flex-shrink-0" fill="currentColor"
                                             viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                  clip-rule="evenodd"></path>
                                        </svg>
                                        <p><strong>Catatan:</strong> Data yang sudah ada di database telah diupdate
                                            dengan nilai baru dari file Excel.</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <button wire:click="closeModalNow"
                            class="mt-8 px-8 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-lg active:scale-95 font-bold">
                        Tutup Jendela
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>
