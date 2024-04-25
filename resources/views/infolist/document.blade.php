@php
    use GalleryJsonMedia\JsonMedia\Contracts\HasMedia;
        /** @var HasMedia $record */
        $record = $getRecord();
@endphp
<div x-data="{}"
     x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref("gallery-json-media-styles","webplusm/gallery-json-media"))]"
     class="max-w-max"
>
    @if(($record) and $record->hasDocuments($getName()))
        <div class="grid grid-rows-1 lg:grid-cols-2 xl:grid-cols-3 gap-3 overflow-hidden">
            @foreach(collect($record->getDocuments($getName()))->take($getMaxAvatars())->all() as $document)
                 <a href="{{ $document->getUrl() }}" target="_blank"
                    class="inline-flex items-center gap-x-1 text-xs text-primary-600 hover:text-primary-500"
                 >
                 @svg('heroicon-o-document','h-6 w-6')    {{ $document->getCustomProperty('alt') }}
                 </a>
            @endforeach
        </div>
    @else
        <div>
            {{ __('gallery-json-media::infolist.document.nothing-to-show') }}
        </div>
    @endif
</div>
