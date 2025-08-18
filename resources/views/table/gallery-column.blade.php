@php
    use GalleryJsonMedia\JsonMedia\Contracts\HasMedia;
        /** @var HasMedia $record */

@endphp
<div class="flex gap-x-1 items-center -space-x-5 p-2 overflow-x-auto overflow-y-hidden" style="height: {{ $getThumbHeight() + 16 }}px">
    @if(($record) and !$record->hasDocuments($getName()))
        @if(!$hasAvatars())
            {{ $record->getFirstMedia($getName())?->withImageProperties($getThumbWidth(),$getThumbHeight()) }}
        @else
            @foreach(collect($record->getMedias($getName()))->take($getMaxAvatars())->all() as $media)
                <img  class="wm-image ring-white dark:ring-gray-900 inline-block rounded-full object-cover {{ $getRing() }}"
                      style="height: {{ $getThumbHeight() }}px;width: {{ $getThumbHeight() }}px"
                      src="{{ $media->getCropUrl($getThumbWidth(),$getThumbHeight()) }}"
                      alt="{{ $media->getCustomProperty('alt') }}"
                      width="{{ $getThumbWidth() }}"
                      height="{{ $getThumbHeight() }}"
                      loading="lazy"
                />
            @endforeach
            @if(($nb = ($record->mediasCount($getName()) - collect($record->getMedias($getName()))->take($getMaxAvatars())->count())) > 0)
                    <div class="pl-5">
                        <x-filament::badge color="info" size="xs">+ {{ $nb }}</x-filament::badge>
                    </div>
            @endif
        @endif
    @endif
</div>
