<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    @include('gallery-json-media::infolist.document')
</x-dynamic-component>
