<div class="space-y-4 p-2 text-sm">
    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <p><span class="text-gray-500">Tugas:</span> <b>{{ $record->task?->title ?? '-' }}</b></p>
        <p><span class="text-gray-500">Penerima Tugas:</span> {{ $record->task?->assignedTo?->name ?? '-' }}</p>
        <p><span class="text-gray-500">Penilai (Atasan/HR):</span> {{ $record->manager?->name ?? '-' }}</p>
        <p><span class="text-gray-500">Nilai Evaluasi:</span> <b>{{ $record->score }}/5</b></p>
    </div>

    {{-- Catatan evaluasi dari atasan --}}
    <div>
        <h4 class="mb-1 font-semibold">Catatan Evaluasi (dari Atasan/HR)</h4>
        <p class="whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
            {{ $record->note ?: '-' }}
        </p>
    </div>

    {{-- Feedback balik karyawan --}}
    <div>
        <h4 class="mb-1 font-semibold">Feedback Karyawan untuk Atasan</h4>
        <p class="whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
            {{ $record->employee_feedback ?: 'Belum ada feedback.' }}
        </p>
        @if ($record->employee_feedback_at)
            <p class="mt-1 text-xs text-gray-500">
                Dikirim: {{ $record->employee_feedback_at->format('d M Y H:i') }}
            </p>
        @endif
    </div>
</div>