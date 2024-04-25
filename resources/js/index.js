import { checkFile, checkMaxFile, humanFileSize, normalizeFileToShow, uuid } from './support/FileInfo.js'
import { contentFile } from './support/svgDocumentFiles.js'

export function galleryFileUpload(
    {
        state,
        statePath,
        minSize,
        maxSize,
        maxFiles,
        isMultiple,
        isDeletable,
        isDisabled,
        isDownloadable,
        uploadingMessage,
        changeNameToAlt,
        isReorderable,
        acceptedFileTypes,
        hasCustomPropertiesAction,
        deleteUploadedFileUsing,
        getUploadedFilesUsing,
        removeUploadedFileUsing,
        customPropertyActionName,
        reorderUploadedFilesUsing,
    },
) {
    return {
        state,
        statePath,
        customPropertyActionName,
        hasCustomPropertiesAction,
        isDeletable,
        isReorderable,
        lastState: null,
        uploadFiles: [],
        uploadedFileIndex: {},
        editingFile: {},
        startUpload: false,
        fileKeyIndex: {},
        progress: 0,
        _startSwipeX: 0,
        stopDragging: true,
        getHumanSize(size) {
            return humanFileSize(size)
        },
        uploadUsing: (fileKey, file, success, error, progress, index) => {
            this.$wire.upload(
                `${statePath}.${fileKey}`,
                file,
                (uploaFilename) => {
                    //success
                    success(index)
                },
                () => {
                    // error
                    error(index)
                },
                (progressEvent) => {
                    progress(index, progressEvent)
                },
            )
        },
        /**@param {Object : { name : string,alt : string}} file */
        getFileName: function(file) {
            if (file.name.startsWith('blob:') || file.name.startsWith('livewire:')) {
                return file.name
            }
            if(changeNameToAlt){
                return file.alt
            }
            let last_slash = file.name.lastIndexOf('/')
            if (last_slash !== -1) {
                return file.name.slice(last_slash + 1)
            }
            return file.name
        },
        getContentImage(file) {
            return contentFile(file).getFile()
        },
        /**@param {FileList} filesList */
        saveFilesUsing(filesList) {
            /**@type {HTMLElement} */
            const wrapper = this.$refs.galleryImages
            const stopUploading = function(component) {
                let rest = component.uploadFiles.filter(f => f.is_success === false).length,
                    numberErrors = component.uploadFiles.filter(f => f.error === true).length

                if ((rest - numberErrors) === 0) {
                    component.dispatchFormEvent('form-processing-finished')
                    if(numberErrors) {
                        // Removed thumbnails with no upload
                        component.uploadFiles = component.uploadFiles
                            .filter(file => !file.error)
                            .map(file =>{ file.error = false; return file })
                    }
                    component.startUpload = false
                }
            }
            const success = (fileKey) => {
                this.uploadFiles[fileKey].is_success = true
                this.uploadFiles[fileKey].progress = 0
                stopUploading(this)
            }
            const error = (fileKey) => {
                this.uploadFiles[fileKey].progress = 0
                this.uploadFiles[fileKey].error = true
                stopUploading(this)
            }
            const progress = (fileKey, progressEvent) => {
                this.uploadFiles[fileKey].progress = progressEvent.detail.progress
            }

            const nbFilesUpload = filesList.length,
                nbFiles = this.uploadFiles.length
            let filesUpload = 0
            if (nbFilesUpload) {
                if (!checkMaxFile(nbFiles, maxFiles, nbFilesUpload)) {
                    new FilamentNotification().title(`Max [${maxFiles}] Files reach `).danger().send()
                    return
                }
                if (!this.startUpload) {
                    this.dispatchFormEvent('form-processing-started', {
                        message: uploadingMessage,
                    })
                }
                this.startUpload = true

                for (const file of Array.from(filesList)) {
                    if (checkFile(file)
                        .fileType(acceptedFileTypes)
                        .maxSize(maxSize)
                        .minSize(minSize)
                        .check()
                    ) {
                        new FilamentNotification().title(`File "${file.name}" invalid`).warning().send()
                        continue
                    }

                    let newFile = normalizeFileToShow(file, uuid())
                    if (newFile) {
                        this.uploadFiles.push({ ...newFile })
                        this.uploadUsing(newFile.filekey, file, success, error, progress, this.uploadFiles.length - 1)
                        filesUpload++
                    }
                }
                setTimeout(() => {
                    wrapper.scrollTo({
                        left: wrapper.scrollWidth,
                        behavior: 'smooth',
                    })
                }, 30)
                if (filesUpload === 0) {
                    this.startUpload = false // prevent blocking drag & drop + click on area
                    this.dispatchFormEvent('form-processing-finished')
                }
            }

        },
        laFileInput: {
            async ['@change']() {
                /**@var {FileList} filesList */
                const filesList = this.$event.target.files

                await this.saveFilesUsing(filesList)
            },
        },
        onScrolling: {
            ['@wheel.stop'](e) {
                const nbFiles = Object.entries(this.uploadFiles).length
                /** @var {HTMLElement} wrapper */
                const wrapper = this.$refs.galleryImages,
                    /** @var {HTMLElement} ulWrapper */
                    ulWrapper = this.$refs.ulGalleryWrapper

                if ((nbFiles * 320) > wrapper.clientWidth) {

                    let delta = e.deltaY < 0 ? -280 : 280

                    if ((e.deltaY > 0 && wrapper.scrollLeft >= 0 && (wrapper.scrollLeft + wrapper.clientWidth) < ulWrapper.clientWidth)
                        || (e.deltaY < 0 && wrapper.scrollLeft > 0)
                    ) {
                        e.preventDefault()
                        wrapper.scrollTo({
                            left: wrapper.scrollLeft + delta,
                            behavior: 'smooth',
                        })
                    }
                }

            },
        },
        pointerNone: {
            ['@pointerenter'](e) {
                /**@var {HTMLElement} wrapper */
                const wrapper = this.$refs.galleryImages
                wrapper.style.pointerEvents = 'none'
            },
            ['@pointerleave'](e) {
                /**@var {HTMLElement} wrapper */
                const wrapper = this.$refs.galleryImages
                wrapper.style.pointerEvents = 'auto'
            },
        },
        dropZone: {
            ['@click.prevent.stop']() {
                this.$refs.laFileInput.click()
            },
            ['@dragover.prevent.stop']() {
                if (this.$event.target.classList.contains('wm-json-media-dropzone')) {

                }
            },
            async ['@drop.prevent.stop']() {
                if (this.$event.target.classList.contains('wm-json-media-dropzone')) {

                    await this.saveFilesUsing(this.$event.dataTransfer.files)
                    this.$refs.dropzone.classList.remove('wm-dropzone')
                }
            },
            ['@dragenter.prevent.stop']() {
                this.$refs.dropzone.classList.add('wm-dropzone')
            },
            ['@dragleave.prevent.stop']() {
                if (this.$event.target === this.$refs.dropzone) {
                    this.$refs.dropzone.classList.remove('wm-dropzone')
                }
                return false
            },
        },
        leftArrow: {
            ['@click.stop']() {
                const nbFiles = Object.entries(this.uploadFiles).length
                /**@var {HTMLElement} wrapper */
                const wrapper = this.$refs.galleryImages
                if (wrapper.scrollLeft > 0) {
                    wrapper.scroll({
                        left: wrapper.scrollLeft - 300,
                        behavior: 'smooth',
                    })
                }
            },
        },
        rightArrow: {
            ['@click.stop']() {
                /**@var {HTMLElement} wrapper */
                const wrapper = this.$refs.galleryImages,
                    /**@var {Number} totalScroll */
                    totalScroll = wrapper.scrollLeft + wrapper.clientWidth
                if (totalScroll < wrapper.scrollWidth) {
                    wrapper.scroll({
                        left: wrapper.scrollLeft + 300,
                        behavior: 'smooth',
                    })
                }
            },
        },
        removeUploadFile: async function(filekey, index) {
            await removeUploadedFileUsing(filekey)
            this.uploadFiles.splice(index, 1)
        },
        deleteUploadFile: async function(filekey, index) {
            await deleteUploadedFileUsing(filekey)
            this.uploadFiles.splice(index, 1)
        },
        /**
         * @param {string} fileKey
         * @return {HTMLElement}
         * */
        getImageWrapperElementFromKey(fileKey) {
            const wrapper = this.$refs.galleryImages
            return wrapper.querySelector(`div[id="id_${fileKey.replaceAll('-', '')}"]`)
        },
        getUploadedFiles: async function() {
            const uploadedFiles = await getUploadedFilesUsing()

            this.fileKeyIndex = uploadedFiles ?? {}

            this.uploadedFileIndex = Object.entries(this.fileKeyIndex)
                .filter(([key, value]) => value?.url)
                .reduce((obj, [key, value]) => {
                    obj[value.url] = key

                    return obj
                }, {})

        },
        async getFiles() {
            await this.getUploadedFiles()
            return Object.entries(this.fileKeyIndex)
                .reduce((obj, [key, value]) => {
                    value.error = false
                    value.is_success = true
                    value.is_new = false
                    value.filekey = key
                    obj.push({ ...value })
                    return obj
                }, [])
        },
        getUpdateFileEntries: function() {
            return Object.entries(this.state)
                .map((value, key) => {
                    delete value.deleted
                    return { key: value }
                })
        },
        dispatchFormEvent: function(name, detail = {}) {
            this.$el.closest('form')?.dispatchEvent(
                new CustomEvent(name, {
                    composed: true,
                    cancelable: true,
                    detail,
                }),
            )
        },
        canUpload: function() {
            if (!maxFiles) {
                return true
            }
            return Object.entries(this.uploadFiles).length < maxFiles
        },
        init() {
            this.$watch('state', async () => {
                if (this.state === undefined) {
                    return
                }

                if (
                    this.state !== null &&
                    Object.values(this.state).filter((file) => {
                            return file.file.startsWith('livewire-file:')
                        },
                    ).length
                ) {
                    this.lastState = null

                    return
                }
                // Don't do anything if the state hasn't changed
                if (JSON.stringify(this.getUpdateFileEntries()) === this.lastState) {
                    return
                }

                this.lastState = JSON.stringify(this.getUpdateFileEntries())
                this.uploadFiles = await this.getFiles()
            })

            this.$watch('sortKeys', async () => {
                await reorderUploadedFilesUsing(this.sortKeys)
            })
            this.$nextTick(async () => {
                this.uploadFiles = await this.getFiles()
            })

        },
        /* Drag & Drop Reorder part*/
        dropcheck: 0,
        usedKeyboard: false,
        originalIndexBeingDragged: null,
        indexBeingDragged: null,
        indexBeingDraggedOver: null,
        preDragOrder: null,
        sortKeys: null,
        dragstart(event) {
            // Store a copy for when we drag out of range
            this.preDragOrder = [...this.uploadFiles]
            // The index is continuously updated to reorder live and also keep a placeholder
            this.indexBeingDragged = event.target.getAttribute('x-ref')
            // The original is needed for then the drag leaves the container
            this.originalIndexBeingDragged = event.target.getAttribute('x-ref')
            // Not entirely sure this is needed but moz recommended it (?)
            event.dataTransfer.dropEffect = 'copy'
        },
        updateListOrder(event) {
            // This fires every time you drag over another list item
            // It reorders the items array but maintains the placeholder
            if (this.indexBeingDragged) {
                this.indexBeingDraggedOver = event.target.getAttribute('x-ref')
                let from = this.indexBeingDragged
                let to = this.indexBeingDraggedOver
                if (this.indexBeingDragged === to) return
                if (from === to) return

                this.move(from, to)
                this.indexBeingDragged = to
            }
        },
        // These are needed for the handle effect
        setParentDraggable(event) {
            event.target.closest('li').setAttribute('draggable', true)
        },
        setParentNotDraggable(event) {
            event.target.closest('li').setAttribute('draggable', false)
        },
        resetState() {
            this.dropcheck = 0
            this.indexBeingDragged = null
            this.preDragOrder = [...this.uploadFiles]
            this.indexBeingDraggedOver = null
            this.originalIndexBeingDragged = null
        },
        // This acts as a cancelled event, when the item is dropped outside the container
        revertState() {
            this.uploadFiles = this.preDragOrder.length ? this.preDragOrder : this.uploadFiles
            this.resetState()
        },
        // Just repositions the placeholder when we move out of range of the container
        rePositionPlaceholder() {
            this.uploadFiles = [...this.preDragOrder]
            this.indexBeingDragged = this.originalIndexBeingDragged
        },
        move(from, to) {
            let items = this.uploadFiles
            if (to >= items.length) {
                let k = to - items.length + 1
                while (k--) {
                    items.push(undefined)
                }
            }
            items.splice(to, 0, items.splice(from, 1)[0])
            this.uploadFiles = items
        },
        getSort() {
            if (this.indexBeingDragged === this.originalIndexBeingDragged) {
                return null
            }
            this.sortKeys = this.uploadFiles.map(file => file.filekey)
        },
    }
}
