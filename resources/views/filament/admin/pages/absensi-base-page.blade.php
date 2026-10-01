<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <x-filament::button class="mt-5" type="submit" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">Simpan Batas Waktu Presensi</span>
            <span wire:loading wire:target="save" class="inline-flex items-center gap-1.5">
                <x-filament::loading-indicator class="h-4 w-4" /> Menyimpan...
            </span>
        </x-filament::button>
    </form>
</x-filament-panels::page>
