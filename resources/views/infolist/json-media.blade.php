<x-filament-infolists::entry-wrapper >
    <x-filament-infolists::entry-wrapper.label >
        {{ $getLabel() }}
    </x-filament-infolists::entry-wrapper.label>
    @include('gallery-json-media::table.gallery-column')
</x-filament-infolists::entry-wrapper>
