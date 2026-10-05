<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Tests\Fixtures;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use GalleryJsonMedia\Form\JsonMediaGallery;
use GalleryJsonMedia\Tests\Fixtures\Models\Page;
use Livewire\Component;

/**
 * Edits the given page, or creates one when no page is given.
 */
class PageForm extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?Page $record = null;

    public ?array $data = [];

    public ?string $disk = null;

    public ?string $visibility = null;

    public function mount(?Page $record = null, ?string $disk = null, ?string $visibility = null): void
    {
        $this->record = $record;
        $this->disk = $disk;
        $this->visibility = $visibility;
        $this->form->fill($record?->attributesToArray() ?? []);
    }

    public function form(Schema $schema): Schema
    {
        $gallery = JsonMediaGallery::make('images')
            ->directory('page')
            ->maxFiles(2)
            ->maxSize(1024)
            ->editableCustomProperties();

        // Only when given, so that the package defaults apply otherwise
        if ($this->disk !== null) {
            $gallery->disk($this->disk);
        }
        if ($this->visibility !== null) {
            $gallery->visibility($this->visibility);
        }

        return $schema
            ->components([$gallery])
            ->statePath('data')
            ->model($this->record ?? Page::class);
    }

    public function save(): void
    {
        if ($this->record) {
            $this->record->update($this->form->getState());

            return;
        }

        $this->record = Page::create($this->form->getState());
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}
