<?php

namespace Spatie\MediaLibrary\Conversions;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Conditionable;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\ResponsiveImages\WidthCalculator\WidthCalculator;
use Spatie\MediaLibrary\Support\FileNamer\FileNamer;

/** @mixin \Spatie\Image\Drivers\ImageDriver */
class Conversion
{
    use Conditionable;

    protected FileNamer $fileNamer;

    protected float $extractVideoFrameAtSecond = 0;

    protected Manipulations $manipulations;

    protected array $performOnCollections = [];

    protected bool $performOnQueue;

    protected bool $keepOriginalImageFormat = false;

    protected bool $generateResponsiveImages = false;

    protected ?WidthCalculator $widthCalculator = null;

    protected ?string $loadingAttributeValue;

    protected int $pdfPageNumber = 1;

    public function __construct(
        protected string $name,
    ) {
        $optimizerChain = OptimizerChainFactory::create(config('media-library.image_optimizers'));

        $this->manipulations = new Manipulations;
        $this->manipulations->optimize($optimizerChain)->format('jpg');

        $this->fileNamer = app(config('media-library.file_namer'));

        $this->loadingAttributeValue = config('media-library.default_loading_attribute_value');

        $this->performOnQueue = config('media-library.queue_conversions_by_default', true);
    }

    public static function create(string $name): self
    {
        return new static($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPerformOnCollections(): array
    {
        if (! count($this->performOnCollections)) {
            return ['default'];
        }

        return $this->performOnCollections;
    }

    public function extractVideoFrameAtSecond(float $timeCode): self
    {
        $this->extractVideoFrameAtSecond = $timeCode;

        return $this;
    }

    public function getExtractVideoFrameAtSecond(): float
    {
        return $this->extractVideoFrameAtSecond;
    }

    public function keepOriginalImageFormat(): self
    {
        $this->keepOriginalImageFormat = true;

        return $this;
    }

    public function shouldKeepOriginalImageFormat(): bool
    {
        return $this->keepOriginalImageFormat;
    }

    public function getManipulations(): Manipulations
    {
        return $this->manipulations;
    }

    public function removeManipulation(string $manipulationName): self
    {
        $this->manipulations->removeManipulation($manipulationName);

        return $this;
    }

    public function withoutManipulations(): self
    {
        $this->manipulations = new Manipulations;

        return $this;
    }

    public function __call($name, $arguments): self
    {
        $this->manipulations->$name(...$arguments);

        return $this;
    }

    public function setManipulations($manipulations): self
    {
        if ($manipulations instanceof Manipulations) {
            $this->manipulations = $this->manipulations->mergeManipulations($manipulations);
        }

        if (is_callable($manipulations)) {
            $manipulations($this->manipulations);
        }

        return $this;
    }

    public function addAsFirstManipulations(Manipulations $manipulations): self
    {
        $newManipulations = $manipulations->toArray();

        $currentManipulations = $this->manipulations->toArray();

        $allManipulations = array_merge($newManipulations, Arr::except($currentManipulations, array_keys($newManipulations)));

        $this->manipulations = new Manipulations($allManipulations);

        return $this;
    }

    public function performOnCollections(...$collectionNames): self
    {
        $this->performOnCollections = $collectionNames;

        return $this;
    }

    public function shouldBePerformedOn(string $collectionName): bool
    {
        // if no collections were specified, perform conversion on all collections
        if (! count($this->performOnCollections)) {
            return true;
        }

        if (in_array('*', $this->performOnCollections)) {
            return true;
        }

        return in_array($collectionName, $this->performOnCollections);
    }

    public function queued(): self
    {
        $this->performOnQueue = true;

        return $this;
    }

    public function nonQueued(): self
    {
        $this->performOnQueue = false;

        return $this;
    }

    public function nonOptimized(): self
    {
        $this->removeManipulation('optimize');

        return $this;
    }

    public function withResponsiveImages(bool $withResponsiveImages = true): self
    {
        $this->generateResponsiveImages = $withResponsiveImages;

        return $this;
    }

    public function withWidthCalculator(WidthCalculator $widthCalculator): self
    {
        $this->widthCalculator = $widthCalculator;

        return $this;
    }

    public function getWidthCalculator(): ?WidthCalculator
    {
        return $this->widthCalculator;
    }

    public function shouldGenerateResponsiveImages(): bool
    {
        return $this->generateResponsiveImages;
    }

    public function shouldBeQueued(): bool
    {
        return $this->performOnQueue;
    }

    public function getResultExtension(string $originalFileExtension = ''): string
    {
        if ($this->shouldKeepOriginalImageFormat()) {
            if (in_array(strtolower($originalFileExtension), ['jpg', 'jpeg', 'pjpg', 'png', 'gif', 'webp', 'avif'])) {
                return $originalFileExtension;
            }
        }

        if ($manipulationArgument = Arr::get($this->manipulations->getManipulationArgument('format'), 0)) {
            return $manipulationArgument;
        }

        return $originalFileExtension;
    }

    public function getConversionFile(Media $media): string
    {
        $fileName = $this->fileNamer->conversionFileName($media->file_name, $this);

        $fileExtension = $this->fileNamer->extensionFromBaseImage($media->file_name);
        $extension = $this->getResultExtension($fileExtension) ?: $fileExtension;

        return "{$fileName}.{$extension}";
    }

    public function useLoadingAttributeValue(string $value): self
    {
        $this->loadingAttributeValue = $value;

        return $this;
    }

    public function getLoadingAttributeValue(): ?string
    {
        return $this->loadingAttributeValue;
    }

    public function pdfPageNumber(int $pageNumber): self
    {
        $this->pdfPageNumber = $pageNumber;

        return $this;
    }

    public function getPdfPageNumber(): int
    {
        return $this->pdfPageNumber;
    }
}
