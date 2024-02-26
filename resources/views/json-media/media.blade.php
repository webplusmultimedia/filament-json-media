<div class="flex max-w-max">
    <figure class="" style="width: {{ $media->width }}px">
        <img class="object-cover w-full aspect-video" loading="lazy"
             src="{{ $media->getCropUrl(width: $media->width,height: $media->height) }}"
             alt="{{ $media->getCustomProperty('alt') }}"
             width="{{ $media->width }}"
             height="{{ $media->height }}"
        >
    </figure>
</div>

