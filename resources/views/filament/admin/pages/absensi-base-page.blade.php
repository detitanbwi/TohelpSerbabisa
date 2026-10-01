<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <x-filament::button class="mt-5" type="submit">
            Simpan Batas Waktu Presensi
        </x-filament::button>
    </form>
</x-filament-panels::page>
