<x-filament-widgets::widget>
    <x-filament::section
        heading="Dashboard Beban Kerja (Burnout Risk)"
        description="Weighted sum prioritas tugas aktif + Z-Score per staff. Merah = kritis, kuning = pantau, hijau = aman.">

        <div x-data="{ overloadOnly: false }">
            {{-- Ringkasan μ & σ + filter overload --}}
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $scope }} ·
                    Rata-rata beban tim (μ): <span class="font-semibold text-gray-900 dark:text-white">{{ $mean }}</span> ·
                    Standar deviasi (σ): <span class="font-semibold text-gray-900 dark:text-white">{{ $stddev }}</span>
                </p>

                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <input type="checkbox" x-model="overloadOnly"
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600">
                    Hanya tampilkan yang overload
                </label>
            </div>

            @if ($employees->isNotEmpty())
                {{-- Grid kartu responsif (inline CSS = pasti rapi) --}}
                <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                    @foreach ($employees as $e)
                        @php
                            $style = match ($e['color']) {
                                'warning' => ['border' => '#fbbf24', 'bg' => '#fffbeb', 'label' => 'PERLU PANTAUAN'],
                                'danger'  => ['border' => '#f87171', 'bg' => '#fef2f2', 'label' => 'BERISIKO'],
                                default   => ['border' => '#34d399', 'bg' => '#ecfdf5', 'label' => 'AMAN'],
                            };
                        @endphp

                        <div x-show="!overloadOnly || {{ $e['color'] !== 'success' ? 'true' : 'false' }}"
                             class="rounded-xl p-4"
                             style="border: 2px solid {{ $style['border'] }}; background-color: {{ $style['bg'] }};">

                            {{-- Nama + badge status sejajar --}}
                            <div class="flex items-start justify-between gap-2">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $e['name'] }}</div>
                                <x-filament::badge :color="$e['color']">{{ $style['label'] }}</x-filament::badge>
                            </div>

                            {{-- Statistik compact 2 kolom --}}
                            <dl class="mt-3 text-sm"
                                style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem 1rem;">
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Tugas aktif</dt>
                                    <dd class="font-semibold text-gray-900 dark:text-white">{{ $e['count'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Weighted Sum (W)</dt>
                                    <dd class="font-semibold text-gray-900 dark:text-white">{{ $e['score'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Z-Score</dt>
                                    <dd class="font-semibold text-gray-900 dark:text-white">{{ $e['z_score'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Lewat deadline</dt>
                                    <dd class="font-semibold text-gray-900 dark:text-white">{{ $e['overdue'] }}</dd>
                                </div>
                            </dl>

                            {{-- Badge CRITICAL hanya bila Z > 2.0 --}}
                            @if ($e['critical'])
                                <div class="mt-3">
                                    <x-filament::badge color="danger">CRITICAL BURNOUT RISK</x-filament::badge>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">Belum ada karyawan dengan tugas aktif pada cakupan ini.</p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>