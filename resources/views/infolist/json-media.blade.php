<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    @include('gallery-json-media::table.gallery-column')
</x-dynamic-component>
