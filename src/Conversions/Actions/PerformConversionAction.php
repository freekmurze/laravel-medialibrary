<?php

namespace Spatie\MediaLibrary\Conversions\Actions;

use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Conversions\Events\ConversionHasBeenCompleted;
use Spatie\MediaLibrary\Conversions\Events\ConversionWillStart;
use Spatie\MediaLibrary\Conversions\ImageGenerators\ImageGeneratorFactory;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\ResponsiveImages\ResponsiveImageGenerator;

class PerformConversionAction
{
    public function execute(
        Conversion $conversion,
        Media $media,
        string $copiedOriginalFile
    ): void
    {
        $imageGenerator = ImageGeneratorFactory::forMedia($media);

        $copiedOriginalFile = $imageGenerator->convert($copiedOriginalFile, $conversion);

        if (! $copiedOriginalFile) {
            return;
        }

        event(new ConversionWillStart($media, $conversion, $copiedOriginalFile));

        $manipulationResult = (new PerformManipulationsAction())->execute($media, $conversion, $copiedOriginalFile);

        $newFileName = $conversion->getConversionFile($media);

        $renamedFile = $this->renameInLocalDirectory($manipulationResult, $newFileName);

        if ($conversion->shouldGenerateResponsiveImages()) {
            /** @var ResponsiveImageGenerator $responsiveImageGenerator */
            $responsiveImageGenerator = app(ResponsiveImageGenerator::class);

            $responsiveImageGenerator->generateResponsiveImagesForConversion(
                $media,
                $conversion,
                $renamedFile
            );
        }

        app(Filesystem::class)->copyToMediaLibrary($renamedFile, $media, 'conversions');

        $media->markAsConversionGenerated($conversion->getName());

        event(new ConversionHasBeenCompleted($media, $conversion));
    }

    protected function renameInLocalDirectory(
        string $fileNameWithDirectory,
        string $newFileNameWithoutDirectory
    ): string {
        $targetFile = pathinfo($fileNameWithDirectory, PATHINFO_DIRNAME).'/'.$newFileNameWithoutDirectory;

        rename($fileNameWithDirectory, $targetFile);

        return $targetFile;
    }
}
