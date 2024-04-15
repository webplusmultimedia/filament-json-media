<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Form;

use Closure;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\BaseFileUpload;
use GalleryJsonMedia\Form\Concerns\HasCustomProperties;
use GalleryJsonMedia\JsonMedia\ImageManipulation\Croppa;
use GalleryJsonMedia\Support\Concerns\HasThumbProperties;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class JsonMediaGallery extends BaseFileUpload
{
    use HasCustomProperties;
    use HasThumbProperties;

    protected string $view = 'gallery-json-media::forms.gallery-file-upload';

    protected ?string $acceptedFileText = null;

    public function document(): static
    {
        $this->acceptedFileTypes = config('gallery-json-media.form.default.document_accepted_file_type');
        $this->acceptedFileText = config('gallery-json-media.form.default.document_accepted_text');

        return $this;
    }

    public function image(): static
    {
        $this->acceptedFileTypes = config('gallery-json-media.form.default.image_accepted_file_type');
        $this->acceptedFileText = config('gallery-json-media.form.default.image_accepted_text');

        return $this;
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

        $this->multiple();

        $this->registerActions(
            actions: [
                static fn (JsonMediaGallery $component): ?Action => $component->getCustomPropertiesAction(),
            ]
        );

        $this->afterStateHydrated(static function (JsonMediaGallery $component, ?array $state): void {
            if (blank($state)) {
                $component->state([]);

                return;
            }

            /** @var array<string,mixed>|array<int,mixed> $keys */
            $keys = array_keys($state);

            if (is_string(array_key_first($keys))) {
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

        $this->afterStateUpdated(static function (JsonMediaGallery $component, ?array $state) {
            if (blank($state)) {
                return;
            }

            $newState = collect($state)
                ->map(static function ($file, $key) use ($component) {
                    if ($file instanceof TemporaryUploadedFile) {
                        $file = ['file' => $file,
                            'size' => $file->getSize(),
                            'disk' => $component->getDiskName(),
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
            $component->state($newState);
        });

    }

    /**
     * @return array<array{name: string, size: int, mime_type: string, url: string} | null>
     */
    public function getUploadedFiles(): array
    {
        $storage = $this->getDisk();
        $url = [];
        foreach ($this->getState() ?? [] as $fileKey => $file) {
            if (! isset($file['deleted'])) {
                $file['deleted'] = false;
            }
            if ($file['deleted']) {
                continue;
            }

            try {
                if (! $storage->exists(data_get($file, 'file'))) {
                    continue;
                }
            } catch (UnableToCheckFileExistence $exception) {
                continue;
            }

            $fileName = data_get($file, 'file');
            $mimeType = data_get($file, 'mime_type');
            $url[$fileKey] = [
                'name' => $fileName,
                'size' => data_get($file, 'size'),
                'mime_type' => $mimeType,
                'url' => ($this->isImageFile($mimeType) and ! $this->isSvgFile($mimeType))
                    ? (new Croppa(filesystem: $storage, filePath: $fileName, width: $this->getThumbWidth()))
                        ->url()
                    : $storage->url($fileName),
            ];
        }

        return $url;
    }

    public function saveUploadedFiles(): void
    {
        $storage = $this->getDisk();
        if (blank($this->getState())) {
            $this->state([]);

            return;
        }

        if (! $this->shouldStoreFiles()) {
            return;
        }

        $state = array_filter(array_map(function (array $file) use ($storage) {
            if (isset($file['deleted']) and $file['deleted']) {
                try {
                    Croppa::reset($storage->url($file['file'])); // remove all thumbs
                } catch (\Throwable) {
                    //never mind if file doesn't exist
                }

                $storage->delete($file['file']);

                return null;
            }

            if (! $file['file'] instanceof TemporaryUploadedFile) {
                return $file;
            }

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
        }, Arr::wrap($this->getState())));

        // purge files , we dnt want deleted in json
        $state = collect($state)->map(function ($file) {
            unset($file['deleted']);

            return $file;
        })->all();
        $this->state($state);
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

        $rules[] = function (string $attribute, array $value, Closure $fail): void {

            $files = array_filter($value, fn (array $file): bool => $file['file'] instanceof TemporaryUploadedFile);

            $files = collect($files)->map(fn ($val) => $val['file'])->toArray();
            $name = $this->getName();
            $validator = Validator::make(
                [$name => $files],
                ["{$name}.*.file" => ['file', ...parent::getValidationRules()]],
                [],
                ["{$name}.*" => $this->getValidationAttribute()],
            );
            if (! $validator->fails()) {
                return;
            }
            $fail($validator->errors()->first());
        };

        return $rules;
    }

    public function removeUploadedFile(string $fileKey): string | TemporaryUploadedFile | null
    {
        $files = $this->getState();
        $file = $files[$fileKey] ?? null;

        if (! $file) {
            return null;
        }

        if (is_string($file['file'])) {
            //$this->removeStoredFileName($file['file']);
            $file['deleted'] = true;
            $files[$fileKey] = $file;
        } elseif ($file['file'] instanceof TemporaryUploadedFile) {
            $file['file']->delete();
            unset($files[$fileKey]);
        }
        $this->state($files);

        return $file['file'];
    }

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
