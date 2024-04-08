<div class="flex">
    <figure class="w-full" >
        <img class="object-cover w-full" loading="lazy"
             src="{{ $media->getCropUrl(width: $media->width,height: $media->height) }}"
             alt="{{ $media->getCustomProperty('alt') }}"
             width="{{ $media->width }}"
             height="{{ $media->height }}"
        >
    </figure>
</div>

