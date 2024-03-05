<?php

declare(strict_types=1);

namespace GalleryJsonMedia\Commands;

use Illuminate\Console\Command;

class FilamentJsonMediaCommand extends Command
{
    public $signature = 'filament-json-media';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
