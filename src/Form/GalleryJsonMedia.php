<?php

namespace WebplusMultimedia\GalleryJsonMedia\Form;

use Bkwld\Croppa\Facades\Croppa;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\Concerns\HasAffixes;
use Filament\Forms\Components\Contracts\HasAffixActions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Closure;
use WebplusMultimedia\GalleryJsonMedia\Form\Concerns\HasCustomProperties;
use WebplusMultimedia\GalleryJsonMedia\Form\Concerns\HasThumbProperties;

class GalleryJsonMedia extends BaseFileUpload implements HasAffixActions
{
    use HasCustomProperties;
    use HasAffixes;
    use HasThumbProperties;

    private string $baseDirectory = 'web-attachements';

    protected string $view = 'gallery-json-media::gallery-file-upload';

    protected string $acceptedFileText = '.jpg, .svg, .png, .webp, .avif';


    public function documents(): static
    {
        $this->acceptedFileTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/wps-office.xlsx', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/wps-office.docx', 'application/pdf'];
        $this->acceptedFileText = '.pdf, .doc(x), .xls(x)';

        return $this;
    }

    public function image(): static
    {
        $this->acceptedFileTypes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp', 'image/avif'];

        return $this;
    }

    public function getAcceptFileText(): string
    {
        return $this->acceptedFileText;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->hiddenLabel();
        $this->columnSpanFull();
        if (! $this->getAcceptedFileTypes()) {
            $this->image();
        }


        $this->suffixActions([
            static fn(GalleryJsonMedia $component): ?Action => $component->customPropertiesAction(),
        ]);

        $this->afterStateHydrated(static function (GalleryJsonMedia $component, array | null $state): void {
            if (blank($state)) {
                $component->state([]);

                return;
            }

            $keys = array_keys($state);

            if (is_string(array_key_first($keys))) {
                return;
            }
            $files = collect($state)
                ->map(static function (array $file) {
                    $file["deleted"] = false;

                    return [(string) \Str::uuid() => $file];
                });

            $component->state($files->collapse()
                ->all());

        });

        $this->afterStateUpdated(static function (GalleryJsonMedia $component, array | null $state) {
            if (blank($state)) {
                return;
            }

            $newState = collect($state)
                ->map(static function ($file, $key) use ($component) {
                    if ($file instanceof TemporaryUploadedFile) {
                        $file = ['file'             => $file,
                                 'size'             => $file->getSize(),
                                 "disk"             => $component->getDiskName(),
                                 "mime_type"        => $file->getMimeType(),
                                 "deleted"          => false,
                                 "customProperties" => ["alt" => str($file->getClientOriginalName())->beforeLast('.')
                                     ->headline()
                                     ->lower()
                                     ->ucfirst()
                                     ->value(), "title"       => NULL]
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
                'name'      => $fileName,
                'size'      => data_get($file, 'size'),
                'mime_type' => $mimeType,
                'url'       => $this->isImageFile($mimeType) and !$this->isSvgFile($mimeType)
                        ? url(Croppa::url($storage->url($fileName), $this->getThumbWidth()))
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

                return NULL;
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

            if ($storedFile === NULL) {
                return NULL;
            }

            $this->storeFileName($storedFile, $file['file']->getClientOriginalName());
            $file['file']->delete();
            $file['file'] = $storedFile;

            return $file;
        }, Arr::wrap($this->getState())));

        // purge files , we dnt want deleted in json
        $state = collect($state)->map(function($file) { unset($file['deleted']); return $file;})->all();
        $this->state($state);
    }

    public function getDirectory(): ?string
    {
        return $this->baseDirectory . '/' . parent::getDirectory();
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

            $files = array_filter($value, fn(array $file): bool => $file['file'] instanceof TemporaryUploadedFile);

            $files = collect($files)->map(fn($val) => $val['file'])->toArray();
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
        $file = $files[$fileKey] ?? NULL;

        if (! $file) {
            return NULL;
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
