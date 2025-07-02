@php
    use GalleryJsonMedia\JsonMedia\Contracts\HasMedia;
    use function Filament\Support\prepare_inherited_attributes;
        /** @var HasMedia $record */
        $record = $getRecord();
        $direction = 'column';
@endphp
<div x-data="{}"
     x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref("gallery-json-media-styles","webplusm/gallery-json-media"))]"
>
    @if(($record) and $record->hasDocuments($getName()))
        <div
            {{
                $attributes->class([
                    "grid grid-cols-4 gap-4",
                ])
            }}
        >

            @foreach($record->getDocuments($getName()) as $document)
                <a href="{{ $document->getUrl() }}" target="_blank"
                   class="inline-flex items-center gap-x-1 text-xs text-primary-600 hover:text-primary-500"
                >
                    @svg('heroicon-o-document','h-6 w-6') <span class="flex-1">{{ $document->getCustomProperty('alt') }}</span>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-sm">
            {{ __('gallery-json-media::gallery-json-media.infoList.document.nothing-to-show') }}
        </div>
    @endif
</div>
