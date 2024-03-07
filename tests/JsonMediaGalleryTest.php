<?php

declare(strict_types=1);

use Filament\Forms\ComponentContainer;
use GalleryJsonMedia\Form\JsonMediaGallery;
use GalleryJsonMedia\Tests\Fixtures\Livewire;

it('return empty array state when null value', function () {
    $jsonInput = JsonMediaGallery::make('images');

    $jsonInput->container(
        ComponentContainer::make(
            $livewire = Livewire::make()
                ->data(['images' => null])
        )->statePath('data')
            ->components([$jsonInput])
            ->fill(['images' => null])
    );
    expect($jsonInput->getState())->toEqual([]);
});
