<?php

namespace Spatie\Medialibrary\Tests\Support;

use Illuminate\Support\Collection;
use Spatie\Medialibrary\Conversions\Conversion;
use Spatie\Medialibrary\ImageGenerators\FileTypes\BaseGenerator;

class TestImageGenerator extends BaseGenerator
{
    public Collection $supportedMimetypes;

    public Collection $supportedExtensions;

    public bool $shouldMatchBothExtensionsAndMimetypes = false;

    public function __construct()
    {
        $this->supportedExtensions = new Collection();

        $this->supportedMimetypes = new Collection();
    }

    public function supportedExtensions(): Collection
    {
        return $this->supportedExtensions;
    }

    public function supportedMimetypes(): Collection
    {
        return $this->supportedMimetypes;
    }

    public function shouldMatchBothExtensionsAndMimeTypes(): bool
    {
        return $this->shouldMatchBothExtensionsAndMimetypes;
    }

    public function convert(string $path, Conversion $conversion = null): string
    {
        return $path;
    }

    public function requirementsAreInstalled(): bool
    {
        return true;
    }
}
