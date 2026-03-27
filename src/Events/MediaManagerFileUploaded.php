<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MediaManagerFileUploaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $path,
        public string $disk,
    ) {}
}
