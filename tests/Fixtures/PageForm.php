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

    public function mount(?Page $record = null): void
    {
        $this->record = $record;
        $this->form->fill($record?->attributesToArray() ?? []);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                JsonMediaGallery::make('images')
                    ->directory('page')
                    ->maxFiles(2)
                    ->maxSize(1024)
                    ->editableCustomProperties(),
            ])
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
