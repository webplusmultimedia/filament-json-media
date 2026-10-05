<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Form;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Concerns\CanBeSecondary;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use GalleryJsonMedia\Enums\GalleryType;
use GalleryJsonMedia\JsonMedia\Document;
use GalleryJsonMedia\JsonMedia\ImageManipulation\Croppa;
use GalleryJsonMedia\JsonMedia\Media;
use GalleryJsonMedia\Support\Concerns\HasThumbProperties;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Attributes\Renderless;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class JsonMediaGallery extends BaseFileUpload
{
    use CanBeSecondary;
    use Concerns\CanHorizontalMouseScrolling;
    use Concerns\HasCustomProperties;
    use HasThumbProperties;

    protected string $view = 'gallery-json-media::forms.gallery-file-upload';

    protected ?string $acceptedFileText = null;

    protected Closure | bool $hasAltToName = false;

    protected GalleryType $galleryType = GalleryType::Image;

    public function image(): static
    {
        $this->galleryType = GalleryType::Image;
        $this->acceptedFileTypes(config('gallery-json-media.form.default.image_accepted_file_type'));
        $this->acceptedFileText = config('gallery-json-media.form.default.image_accepted_text');

        return $this;
    }

    public function document(): static
    {
        $this->galleryType = GalleryType::Document;
        $this->acceptedFileTypes(config('gallery-json-media.form.default.document_accepted_file_type'));
        $this->acceptedFileText = config('gallery-json-media.form.default.document_accepted_text');
        /** Why not just show alt against filename */
        $this->replaceTitleByAlt();

        return $this;
    }

    public function galleryType(): GalleryType
    {
        return $this->galleryType;
    }

    /**
     * Replace the title in a gallery by alt against filename
     */
    public function replaceTitleByAlt(bool | Closure $hasAltToName = true): static
    {
        $this->hasAltToName = $hasAltToName;

        return $this;
    }

    /**
     * @deprecated use replaceTitleByAlt instead
     */
    public function replaceNameByTitle(bool | Closure $hasAltToName = true): JsonMediaGallery
    {
        return $this->replaceTitleByAlt($hasAltToName);
    }

    public function hasNameReplaceByTitle(): bool
    {
        return $this->evaluate($this->hasAltToName);
    }

    public function getAcceptFileText(): string
    {
        return $this->acceptedFileText ?? config('gallery-json-media.form.default.image_accepted_text');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->columnSpanFull();
        if (! $this->getAcceptedFileTypes()) {
            $this->image();
        }
        $this->displayOnList();
        $this->multiple();

        $this->diskName = config('gallery-json-media.disk');
        // Set here so that Filament does not guess it from the disk name
        $this->visibility = config('gallery-json-media.visibility', 'public');

        $this->registerActions(
            actions: [
                static function (JsonMediaGallery $component): ?Action {
                    return $component->editCustomPropertiesAction();
                },
            ]
        );

        $this->afterStateHydrated(static function (JsonMediaGallery $component, ?array $state, ?array $rawState): void {
            if (blank($state)) {
                $component->state([]);

                return;
            }

            /** @type array<int|string,mixed> $keys */
            $keys = array_keys($state);

            if (is_string(array_key_first($keys))) { // @phpstan-ignore  function.impossibleType
                return;
            }

            $files = collect($state)
                ->map(static function (array $file) {
                    $file['deleted'] = false;

                    return [(string) Str::uuid() => $file];
                });

            $component->state($files->collapse()
                ->all());
        });

        $this->afterStateUpdated(static function (JsonMediaGallery $component, ?array $state, ?array $rawState): void {
            if (blank($state)) {
                return;
            }

            $newState = collect($rawState)
                ->map(static function ($file, $key) use ($component) {
                    if ($file instanceof TemporaryUploadedFile) {
                        $file = ['file' => $file,
                            'size' => $file->getSize(),
                            'disk' => $component->getDiskName(),
                            'visibility' => $component->getVisibility(),
                            'mime_type' => $file->getMimeType(),
                            'deleted' => false,
                            'customProperties' => ['alt' => str($file->getClientOriginalName())->beforeLast('.')
                                ->headline()
                                ->lower()
                                ->ucfirst()
                                ->value(), 'title' => null],
                        ];
                    }

                    return [$key => $file];
                })->collapse()
                ->all();

            $component->rawState($newState);
        });

    }

    /**
     * @return array<array{name: string, size: int, mime_type: string, url: string} | null>
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function getUploadedFiles(): array
    {
        $originalEntries = $this->getOriginalEntries();
        $url = [];
        foreach ($this->getRawState() ?? [] as $fileKey => $file) {
            if (! isset($file['deleted'])) {
                $file['deleted'] = false;
            }
            if ($file['deleted']) {
                continue;
            }

            // The state can be tampered with : only the files of the record get a preview url
            $entry = is_string($file['file'] ?? null) ? ($originalEntries[$file['file']] ?? null) : null;
            if ($entry === null) {
                continue;
            }
            $entry['disk'] ??= $this->getDiskName();

            try {
                if (! Storage::disk($entry['disk'])->exists($entry['file'])) {
                    continue;
                }
            } catch (UnableToCheckFileExistence $exception) {
                continue;
            }

            $mimeType = (string) data_get($entry, 'mime_type');
            $url[$fileKey] = [
                'name' => $entry['file'],
                'size' => data_get($entry, 'size'),
                'alt' => data_get($file, 'customProperties.alt'),
                'mime_type' => $mimeType,
                'url' => $this->isImageFile($mimeType)
                    ? Media::make($entry)->getCropUrl($this->getThumbWidth(), $this->getThumbHeight())
                    : (string) Document::make($entry)->getUrl(),
            ];
        }

        return $url;
    }

    public function saveUploadedFiles(): void
    {
        if (blank($this->getRawState())) {
            $this->rawState([]);

            return;
        }

        if (! $this->shouldStoreFiles()) {
            return;
        }
        $originalEntries = $this->getOriginalEntries();
        $rawState = array_filter(array_map(function (array $file) use ($originalEntries) {
            if (! $file['file'] instanceof TemporaryUploadedFile) {
                $original = is_string($file['file']) ? ($originalEntries[$file['file']] ?? null) : null;

                // The state can be tampered with : a path the record did not have is neither kept nor deleted
                if ($original === null) {
                    return null;
                }

                if (isset($file['deleted']) and $file['deleted']) {
                    $storage = Storage::disk($original['disk'] ?? $this->getDiskName());

                    try {
                        (new Croppa($storage, $original['file']))->reset(); // remove all thumbs
                    } catch (Throwable) {
                        // never mind if file doesn't exist
                    }

                    $storage->delete($original['file']);

                    return null;
                }

                // Only the custom properties are editable, the file metadata stay the stored ones
                return [
                    ...$original,
                    'customProperties' => $file['customProperties'] ?? $original['customProperties'] ?? [],
                ];
            }

            // The client can also change the state of a new upload : its metadata come from the server
            $file = [
                ...$file,
                'size' => $file['file']->getSize(),
                'disk' => $this->getDiskName(),
                'visibility' => $this->getVisibility(),
                'mime_type' => $file['file']->getMimeType(),
            ];

            $callback = $this->saveUploadedFileUsing;

            if (! $callback) {
                $file['file']->delete();

                return $file;
            }

            $storedFile = $this->evaluate($callback, [
                'file' => $file['file'],
            ]);

            if ($storedFile === null) {
                return null;
            }

            $this->storeFileName($storedFile, $file['file']->getClientOriginalName());
            $file['file']->delete();
            $file['file'] = $storedFile;

            return $file;
        }, Arr::wrap($this->getRawState())));

        // purge files , we dnt want deleted in json
        $rawState = collect($rawState)->map(function ($file) {
            unset($file['deleted']);

            return $file;
        })->all();
        $this->rawState($rawState);
    }

    /**
     * The entries the record has in database, keyed by their file path.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getOriginalEntries(): array
    {
        $record = $this->getRecord();

        if (! $record instanceof Model) {
            return [];
        }

        // collect() also reads the AsCollection and AsArrayObject casts
        return collect($record->getOriginal($this->getName()))
            ->filter(fn (mixed $entry): bool => is_array($entry) && is_string($entry['file'] ?? null) && filled($entry['file']))
            ->keyBy('file')
            ->all();
    }

    /**
     * @return array<string>
     */
    public function getOriginalFilePaths(): array
    {
        return array_keys($this->getOriginalEntries());
    }

    public function getDirectory(): ?string
    {
        return config('gallery-json-media.root_directory', 'web-attachments') . '/' . parent::getDirectory();
    }

    public function getValidationRules(): array
    {
        $rules = [
            $this->getRequiredValidationRule(),
            'array',
        ];

        if (filled($count = $this->getMaxFiles())) {
            $rules[] = "max:{$count}";
        }

        if (filled($count = $this->getMinFiles())) {
            $rules[] = "min:{$count}";
        }

        $arrayRules = [];
        $fileRules = [];

        // Same split as BaseFileUpload, but from the field rules : the parent ones are built for a list of paths
        foreach (Field::getValidationRules() as $rule) {
            if ($this->isArrayValidationRule($rule)) {
                $arrayRules[] = $rule;
            } else {
                $fileRules[] = $rule;
            }
        }

        $rules = [
            ...$rules,
            ...$arrayRules,
        ];

        // Always on, unlike preventFilePathTampering() : this field deletes the files removed from its state
        $rules[] = function (string $attribute, mixed $value, Closure $fail): void {
            $originalPaths = $this->getOriginalFilePaths();

            foreach (Arr::wrap($value) as $entry) {
                $file = is_array($entry) ? ($entry['file'] ?? null) : $entry;

                if ($file instanceof TemporaryUploadedFile) {
                    continue;
                }

                if (is_string($file) && in_array($file, $originalPaths, strict: true)) {
                    continue;
                }

                $fail(__($this->getValidationMessages()['tampered'] ?? 'filament-forms::validation.tampered_file_path', [
                    'attribute' => $this->getValidationAttribute(),
                ]));

                return;
            }
        };

        $rules[] = function (string $attribute, array $value, Closure $fail) use ($fileRules): void {
            $files = collect($value)
                ->pluck('file')
                ->filter(fn (mixed $file): bool => $file instanceof TemporaryUploadedFile)
                ->values()
                ->all();

            $name = $this->getName();
            $validationMessages = $this->getValidationMessages();
            $validator = Validator::make(
                [$name => $files],
                ["{$name}.*" => ['file', ...$fileRules]],
                $validationMessages ? ["{$name}.*" => $validationMessages] : [],
                ["{$name}.*" => $this->getValidationAttribute()],
            );
            if (! $validator->fails()) {
                return;
            }
            $fail($validator->errors()->first());
        };

        return $rules;
    }

    #[ExposedLivewireMethod]
    #[Renderless]
    public function removeUploadedFile(string $fileKey): string | TemporaryUploadedFile | null
    {
        $files = $this->getRawState();
        $file = $files[$fileKey] ?? null;

        if (! $file) {
            return null;
        }

        if (is_string($file['file'])) {
            // $this->removeStoredFileName($file['file']);
            $file['deleted'] = true;
            $files[$fileKey] = $file;
        } elseif ($file['file'] instanceof TemporaryUploadedFile) {
            $file['file']->delete();
            unset($files[$fileKey]);
        }
        $this->rawState($files);

        return $file['file'];
    }

    #[ExposedLivewireMethod]
    #[Renderless]
    public function deleteUploadedFile(string $fileKey): static
    {
        $file = $this->removeUploadedFile($fileKey);

        if (blank($file)) {
            return $this;
        }

        $callback = $this->deleteUploadedFileUsing;

        if (! $callback) {
            return $this;
        }

        $this->evaluate($callback, [
            'file' => $file,
        ]);

        return $this;
    }
}
