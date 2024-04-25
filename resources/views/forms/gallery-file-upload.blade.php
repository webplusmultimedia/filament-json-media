@php
    use Illuminate\Support\Arr;
    $editPropertiesAction = $getAction($getCustomPropertiesActionName()) ;

@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :label-sr-only="$isLabelHidden()"
>
    <div
        @if (\Filament\Support\Facades\FilamentView::hasSpaMode())
            ax-load="visible"
        @else
            ax-load
        @endif
        ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc("gallery-json-media","webplusm/gallery-json-media") }}"
        x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref("gallery-json-media-styles","webplusm/gallery-json-media"))]"
        x-data="galleryFileUpload({
                     state : $wire.$entangle('{{ $getStatePath() }}'),
                     statePath : @js($getStatePath()),
                     minSize : @js($getMinSize()) ,
                     maxSize : @js($getMaxSize()),
                     maxFiles : @js($getMaxFiles()),
                     isReorderable: @js($isReorderable()),
                     isDeletable: @js($isDeletable()),
                     isDisabled: @js($isDisabled),
                     isDownloadable: @js($isDownloadable()),
                     hasCustomPropertiesAction : @js($hasCustomPropertiesAction()) ,
                     isMultiple : @js($isMultiple()),
                     acceptedFileTypes : @js($getAcceptedFileTypes()),
                     uploadingMessage: @js($getUploadingMessage()),
                     changeNameToAlt : @js($hasNameReplaceByTitle()),
                     removeUploadedFileUsing: async (fileKey) => {
                        return await $wire.removeFormUploadedFile(@js($getStatePath()), fileKey)
                    },
                     deleteUploadedFileUsing: async (fileKey) => {
                        return await $wire.deleteUploadedFile(@js($getStatePath()), fileKey)
                    },
                    getUploadedFilesUsing: async () => {
                        return await $wire.getFormUploadedFiles(@js($getStatePath()))
                    },
                    reorderUploadedFilesUsing: async (files) => {
                        return await $wire.reorderFormUploadedFiles(@js($getStatePath()), files)
                    },
                    customPropertyActionName : @js($getCustomPropertiesActionName()),

         })"
        wire:ignore
        x-ignore
        class="grid gap-y-2 "
        x-id="['file-input']"
    >
        <input type="file" :id="$id('file-input')"
               x-bind="laFileInput"
               x-ref="laFileInput"
               class="hidden"
               {{ $isMultiple()?'multiple':'' }}
               {{ $isDisabled()?'disabled':'' }}
               accept="{{  implode(',',Arr::wrap($getAcceptedFileTypes())) }}"
        >
        <div @class([
            "wm-json-media-dropzone flex items-center justify-center w-full py-3 border border-dashed rounded-lg border-gray-300  text-gray-400 transition
    hover:border-primary-400 dark:border-gray-400/50 dark:bg-gray-800 dark:hover:border-primary-600 dark:text-white/80",
        ])
             :class="{'pointer-events-none opacity-40' : startUpload}"
             role="button"
             x-ref="dropzone"
             x-cloak
             x-bind="dropZone"
             x-show="canUpload"
        >
            <div class="flex gap-3 pointer-events-none" x-ref="ladroptitle">
                @svg(name: 'heroicon-o-document-arrow-up',class:"w-10 h-auto text-slate-500" )
                <div class="flex flex-col x-space-y-2">
                    <span>{{ trans('gallery-json-media::gallery-json-media.Drag&Drop') }}</span>
                    <span x-text="@js($getAcceptFileText())"></span>
                </div>
            </div>
        </div>
        <div class="flex justify-self-end space-y-2"
            x-show="uploadFiles.length"
        >
            <div class="flex gap-x-4 mt-2">
                <button type="button" x-bind="leftArrow"
                        class="wm-btn"
                >
                    @svg(name: 'heroicon-c-chevron-left',class: 'w-5 h-5')
                </button>
                <button type="button" x-bind="rightArrow"
                        class="wm-btn"
                >
                    @svg(name: 'heroicon-c-chevron-right',class: 'w-5 h-5')
                </button>
            </div>
        </div>

        <div class="gallery-file-upload-wrapper "
             x-ref="galleryImages"
             x-bind="onScrolling"
        >
            <ul role="list"
                class="flex gap-2 transition-all duration-200"
                @keydown.window.tab="usedKeyboard = true"
                @dragenter.stop.prevent="dropcheck++"
                @dragleave="dropcheck--;dropcheck || rePositionPlaceholder()"
                @dragover.stop.prevent
                @dragend="revertState()"
                @drop.stop.prevent="getSort();resetState()"
                x-ref="ulGalleryWrapper"
                {{--:class="{'flex-wrap' : !stopDragging }"--}}
            >
               @include('gallery-json-media::forms.content.gallery-content')
            </ul>
        </div>
    </div>
</x-dynamic-component>
