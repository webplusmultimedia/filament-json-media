@php
    use GalleryJsonMedia\JsonMedia\Contracts\HasMedia;
        /** @var HasMedia $record */
        $record = $getRecord();
@endphp
<div x-data="{}"
     x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref("gallery-json-media-styles","webplusm/gallery-json-media"))]"
>
    @if(($record) and $record->hasDocuments($getName()))
        <x-filament::grid
            :default="$getColumns('default')"
            :sm="$getColumns('sm')"
            :md="$getColumns('md')"
            :lg="$getColumns('lg')"
            :xl="$getColumns('xl')"
            :two-xl="$getColumns('2xl')"
            :attributes="
            \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
                ->class(['fi-fo-component-ctn gap-6'])
        "
        >
            @foreach($record->getDocuments($getName()) as $document)
                <a href="{{ $document->getUrl() }}" target="_blank"
                   class="inline-flex items-center gap-x-1 text-xs text-primary-600 hover:text-primary-500"
                >
                    @svg('heroicon-o-document','h-6 w-6')  <span class="flex-1">{{ $document->getCustomProperty('alt') }}</span>
                </a>
            @endforeach
        </x-filament::grid>
    @else
        <div class="text-sm">
            {{ __('gallery-json-media::gallery-json-media.infoList.document.nothing-to-show') }}
        </div>
    @endif
</div>
