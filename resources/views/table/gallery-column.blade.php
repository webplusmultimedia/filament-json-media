@php
    use WebplusMultimedia\GalleryJsonMedia\JsonMedia\Contracts\HasMedia;
    /** @var HasMedia $record */
    $record = $getRecord();
@endphp
<div style="width: {{ $getThumbWidth() }}px">
    @if(($record))
        {{ $record->getFirstMedia($getName())?->withImageProperties($getThumbWidth(),$getThumbHeight()) }}
    @endif
</div>
