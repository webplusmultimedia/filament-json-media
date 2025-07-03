<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 *
 * @category    Category
 *
 * @author      daniel
 *
 * @link        http://webplusm.net
 * Date: 14/02/2024 11:05
 */

namespace GalleryJsonMedia\Form\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use GalleryJsonMedia\Form\JsonMediaGallery;

trait HasCustomProperties
{
    protected string $customPropertiesActionName = 'edit-custom-properties';

    protected array | Closure $customPropertiesSchema = [];

    protected bool | Closure $editCustomPropertiesOnSlideOver = false;

    protected null | string | Closure $editCustomPropertiesTitle = null;

    protected bool | Closure $canEditCustomProperties = false;

    public function withCustomProperties(
        array | Closure $customPropertiesSchema,
        bool | Closure $editCustomPropertiesOnSlideOver = false,
        null | string | Closure $editCustomPropertiesTitle = null
    ): static {
        $this->customPropertiesSchema = $customPropertiesSchema;
        $this->editCustomPropertiesOnSlideOver = $editCustomPropertiesOnSlideOver;
        $this->editCustomPropertiesTitle = $editCustomPropertiesTitle;

        return $this;
    }

    public function getCustomPropertiesSchema(): array
    {
        return $this->evaluate($this->customPropertiesSchema);
    }

    public function customPropertiesActionName(string $customPropertiesActionName): static
    {
        $this->customPropertiesActionName = $customPropertiesActionName;

        return $this;
    }

    public function editableCustomProperties(bool | Closure $canEditCustomProperties = true): static
    {
        $this->canEditCustomProperties = $canEditCustomProperties;

        return $this;
    }

    public function canEditCustomProperties(): bool
    {
        return $this->evaluate($this->canEditCustomProperties);
    }

    public function getCustomPropertiesActionName(): string
    {
        return $this->customPropertiesActionName;
    }

    public function editCustomPropertiesAction(): ?Action
    {
        if ($this->canEditCustomProperties()) {
            $action = Action::make($this->getCustomPropertiesActionName())
                ->fillForm(static function (array $arguments, JsonMediaGallery $component, Action $action): array {
                    $key = $arguments['key'];
                    $state = $component->getRawState();
                    if (! isset($state[$key])) {
                        $action->cancel();
                    }

                    return $state[$key]['customProperties'];
                })
                ->label(trans('gallery-json-media::gallery-json-media.title.edit-modal-form-for-customs-properties'))
                ->schema(fn (array $arguments) => $this->getMinimumFieldForCustomEditField($arguments))
                ->action(function (array $arguments, array $data, Schema $schema, JsonMediaGallery $component) {
                    $key = $arguments['key'];
                    $state = $component->getRawState();
                    if (! isset($state[$key])) {
                        return;
                    }
                    $state[$key]['customProperties'] = $data;
                    $component->rawState($state);
                })
                ->iconButton()
                ->icon('heroicon-o-bars-3-center-left')
                ->tooltip(trans('gallery-json-media::gallery-json-media.tooltip.edit-button-custom-property'))
                ->modalAlignment(Alignment::Center);

            if ($this->evaluate($this->editCustomPropertiesOnSlideOver)) {
                $action->slideOver();
            }

            return $action;
        }

        return null;
    }

    private function getMinimumFieldForCustomEditField(array $arguments): array
    {
        $key = $arguments['key'];
        $state = $this->getRawState();
        $mimeType = $state[$key]['mime_type'];
        $label = $this->isImageFile($mimeType) ?
            trans('gallery-json-media::gallery-json-media.form.alt.label.media')
            : trans('gallery-json-media::gallery-json-media.form.alt.label.document');

        return array_merge(
            [TextInput::make('alt')->label($label)->maxLength(255)->required()],
            $this->getCustomPropertiesSchema()
        );
    }

    private function isImageFile(string $mimeType): bool
    {
        return str($mimeType)->startsWith('image');
    }

    private function isSvgFile(string $mimeType): bool
    {
        return str($mimeType)->exactly('image/svg+xml');
    }
}
