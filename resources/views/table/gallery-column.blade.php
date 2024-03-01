@php
    use WebplusMultimedia\GalleryJsonMedia\JsonMedia\Contracts\HasMedia;
    /** @var HasMedia $record */
    $record = $getRecord();
@endphp
<div class="flex gap-x-1 items-center max-w-max">
    @if(($record))
        @if(!$hasAvatars())
            {{ $record->getFirstMedia($getName())?->withImageProperties($getThumbWidth(),$getThumbHeight()) }}
        @else
            <div class="flex -space-x-3 overflow-hidden">
                @foreach(collect($record->getMedias($getName()))->take($getMaxAvatars())->all() as $media)
                    <img class="inline-block rounded-full ring-2 ring-white" src="{{ $media->getCropUrl($getThumbWidth(),$getThumbHeight()) }}"
                         alt="{{ $media->getCustomProperty('alt') }}" />
                @endforeach
            </div>
            @if(($nb = ($record->mediasCount($getName()) - collect($record->getMedias($getName()))->take($getMaxAvatars())->count())) > 0)
                <x-filament::badge color="info" size="xs">+ {{ $nb }}</x-filament::badge>
            @endif

        @endif
    @endif
</div>
