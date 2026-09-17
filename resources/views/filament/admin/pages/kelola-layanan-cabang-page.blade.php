<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Pilih Cabang / Kota:</span>
                    <x-filament::input.wrapper class="min-w-[240px]">
                        <x-filament::input.select wire:model.live="selectedCabangId" :disabled="!auth()->user()->hasRole('super_admin')">
                            @foreach($this->cabangOptions as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    💡 Menampilkan ketersediaan & tarif khusus untuk: <strong class="text-primary-600 dark:text-primary-400 text-sm">{{ $this->selectedCabangNama }}</strong>
                </div>
            </div>
        </x-filament::section>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
