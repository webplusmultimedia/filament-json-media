<?php
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

namespace WebplusMultimedia\GalleryJsonMedia\Form\Concerns;

use Closure;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Support\Enums\Alignment;
use WebplusMultimedia\GalleryJsonMedia\Form\GalleryJsonMedia;

trait HasCustomProperties
{
    protected string $customPropertiesActionName = 'edit-custom-properties';

    protected null | array | Closure $customPropertiesSchema = null;

    protected bool | Closure $editCustomPropertiesOnSlideOver = false;

    protected null | string | Closure $editCustomPropertiesTitle = null;

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

    public function getCustomPropertiesSchema(): ?array
    {
        return $this->evaluate($this->customPropertiesSchema);
    }

    public function customPropertiesActionName(string $customPropertiesActionName): static
    {
        $this->customPropertiesActionName = $customPropertiesActionName;

        return $this;
    }

    public function getCustomPropertiesActionName(): string
    {
        return $this->customPropertiesActionName;
    }

    public function hasCustomPropertiesAction(): bool
    {
        return $this->getCustomPropertiesSchema() !== null;
    }

    public function customPropertiesAction(): ?Action
    {
        if ($this->hasCustomPropertiesAction()) {
            $action = Action::make($this->getCustomPropertiesActionName())
                ->fillForm(static function (array $arguments, GalleryJsonMedia $component): array {
                    $key = $arguments['key'];
                    $state = $component->getState();
                    if (! isset($state[$key])) {
                        return [];
                    }

                    return $state[$key]['customProperties'];
                })
                ->label('Edition des propriétés')
                ->form(fn (array $arguments) => $this->getMinimumFieldForCustomEditField($arguments, $this->getState()))
                ->action(function (array $arguments, array $data, Form $form, GalleryJsonMedia $component) {
                    $key = $arguments['key'];
                    $state = $component->getState();
                    if (! isset($state[$key])) {
                        return;
                    }
                    $state[$key]['customProperties'] = $data;
                    $component->state($state);
                })
                ->iconButton()
                ->icon('heroicon-o-bars-3-center-left')
                ->modalAlignment(Alignment::Center);

            if ($this->evaluate($this->editCustomPropertiesOnSlideOver)) {
                $action->slideOver();
            }

            return $action;
        }

        return null;
    }

    private function getMinimumFieldForCustomEditField(array $arguments, array $state): array
    {
        return array_merge([TextInput::make('alt')->required()->helperText('Alternative text image')], $this->getCustomPropertiesSchema());
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
