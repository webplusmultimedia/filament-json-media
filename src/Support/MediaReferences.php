<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

/**
 * The files used by the json media fields of the models declared in gallery-json-media.maintenance.models.
 */
final class MediaReferences
{
    /**
     * @param  array<string, array<string, true>>  $paths  the used paths by disk, '' for the entries saved without disk
     */
    private function __construct(
        private readonly array $paths,
        private readonly bool $known,
    ) {}

    public static function fromDeclaredModels(): self
    {
        /** @var array<class-string<Model>, array<int, string>> $models */
        $models = config('gallery-json-media.maintenance.models', []);
        $paths = [];

        foreach ($models as $model => $fields) {
            $key = (new $model)->getKeyName();

            // Every row counts : a soft deleted or a scoped record keeps its files
            $model::query()
                ->withoutGlobalScopes()
                ->select([$key, ...$fields])
                ->lazyById(column: $key)
                ->each(function (Model $record) use ($fields, &$paths): void {
                    foreach ($fields as $field) {
                        foreach (collect($record->{$field}) as $entry) {
                            $entry = $entry instanceof Arrayable ? $entry->toArray() : $entry;

                            if (is_array($entry) && is_string($entry['file'] ?? null)) {
                                $paths[(string) ($entry['disk'] ?? '')][$entry['file']] = true;
                            }
                        }
                    }
                });
        }

        return new self($paths, $models !== []);
    }

    /**
     * Without declared models, a file nobody seems to use may be used.
     */
    public function areKnown(): bool
    {
        return $this->known;
    }

    public function uses(string $disk, string $path): bool
    {
        // An entry saved without disk may be on any disk
        return isset($this->paths[$disk][$path]) || isset($this->paths[''][$path]);
    }
}
