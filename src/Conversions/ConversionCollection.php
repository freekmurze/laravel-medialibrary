<?php

namespace Spatie\MediaLibrary\Conversions;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Exceptions\InvalidConversion;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @template TKey of array-key
 * @template TValue of Conversion
 *
 * @extends Collection<TKey, TValue>
 */
class ConversionCollection extends Collection
{
    protected Media $media;

    public static function createForMedia(Media $media): self
    {
        return (new static)->setMedia($media);
    }

    /**
     * @return $this
     */
    public function setMedia(Media $media): self
    {
        $this->media = $media;

        $this->items = [];

        $this->addConversionsFromRelatedModel($media);

        $this->addManipulationsFromDb($media);

        return $this;
    }

    public function getByName(string $name): Conversion
    {
        $conversion = $this->first(fn (Conversion $conversion) => $conversion->getName() === $name);

        if (! $conversion) {
            throw InvalidConversion::unknownName($name);
        }

        return $conversion;
    }

    protected function addConversionsFromRelatedModel(Media $media): void
    {
        $modelName = Arr::get(Relation::morphMap(), $media->model_type, $media->model_type);

        if (! class_exists($modelName)) {
            return;
        }

        /** @var \Spatie\MediaLibrary\HasMedia $model */
        $model = new $modelName;

        /*
         * In some cases the user might want to get the actual model
         * instance so conversion parameters can depend on model
         * properties. This will causes extra queries.
         */
        if ($model->registerMediaConversionsUsingModelInstance && $media->model) {
            $model = $media->model;

            $model->mediaConversions = [];
        }

        $model->registerAllMediaConversions($media);

        $this->items = $model->mediaConversions;
    }

    protected function addManipulationsFromDb(Media $media): void
    {
        collect(Arr::except($media->manipulations, '*'))->each(function ($manipulation, $conversionName) {
            $manipulations = new Manipulations($manipulation);

            $this->addManipulationToConversion($manipulations, $conversionName);
        });

        if (array_key_exists('*', $media->manipulations)) {
            $globalManipulations = new Manipulations($media->manipulations['*']);

            $this->addManipulationToConversion($globalManipulations, '*');
        }
    }

    public function getConversions(string $collectionName = ''): self
    {
        if ($collectionName === '') {
            return $this;
        }

        return $this->filter(fn (Conversion $conversion) => $conversion->shouldBePerformedOn($collectionName));
    }

    protected function addManipulationToConversion(Manipulations $manipulations, string $conversionName): void
    {
        /** @var Conversion|null $conversion */
        $conversion = $this->first(function (Conversion $conversion) use ($conversionName) {
            if (! $conversion->shouldBePerformedOn($this->media->collection_name)) {
                return false;
            }

            if ($conversion->getName() !== $conversionName) {
                return false;
            }

            return true;
        });

        if ($conversion) {
            $conversion->addAsFirstManipulations($manipulations);
        }

        if ($conversionName === '*') {
            $this->each(
                fn (Conversion $conversion) => $conversion->addAsFirstManipulations(clone $manipulations)
            );
        }
    }

    public function getConversionsFiles(string $collectionName = ''): self
    {
        return $this
            ->getConversions($collectionName)
            ->map(fn (Conversion $conversion) => $conversion->getConversionFile($this->media));
    }
}
