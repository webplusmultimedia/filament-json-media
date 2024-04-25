@php
    use GalleryJsonMedia\JsonMedia\Contracts\HasMedia;
        /** @var HasMedia $record */
        $record = $getRecord();
@endphp
<div class="flex gap-x-1 items-center max-w-max">
    @if(($record) and !$record->hasDocuments($getName()))
        @if(!$hasAvatars())
            {{ $record->getFirstMedia($getName())?->withImageProperties($getThumbWidth(),$getThumbHeight()) }}
        @else
            <div class="flex -space-x-3 p-2 overflow-hidden" style="max-height: {{ $getThumbHeight()+16 }}px">
                @foreach(collect($record->getMedias($getName()))->take($getMaxAvatars())->all() as $media)
                    <img class="inline-block rounded-full ring-2 ring-white object-cover" src="{{ $media->getCropUrl($getThumbWidth(),$getThumbHeight()) }}"
                         alt="{{ $media->getCustomProperty('alt') }}" width="{{ $getThumbWidth() }}"
                         height="{{ $getThumbHeight() }}"
                         loading="lazy"
                    />
                @endforeach
            </div>
            @if(($nb = ($record->mediasCount($getName()) - collect($record->getMedias($getName()))->take($getMaxAvatars())->count())) > 0)
                <x-filament::badge color="info" size="xs">+ {{ $nb }}</x-filament::badge>
            @endif

        @endif
    @endif
</div>
