<?php

namespace Spatie\MediaLibrary\Tests\ResponsiveImages;

use Spatie\MediaLibrary\Tests\TestSupport\TestFileNamer;

class ResponsiveImageFileNamerTest extends ResponsiveImageTest
{
    public function setUp(): void
    {
        parent::setUp();
        config()->set("media-library.file_namer", TestFileNamer::class);
        $this->file_name = "prefix_test_suffix";
    }
}
