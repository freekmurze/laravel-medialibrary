<?php

namespace Spatie\MediaLibrary\Support\PathGenerator;

use Spatie\MediaLibrary\MediaCollections\Exceptions\InvalidPathGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\Factories\TemporaryUploadFactory;

class PathGeneratorFactory
{
    public static function create(Media $media): PathGenerator
    {
        $pathGeneratorClass = self::getPathGeneratorClass($media);

        static::guardAgainstInvalidPathGenerator($pathGeneratorClass);

        return app($pathGeneratorClass);
    }

    protected static function getPathGeneratorClass(Media $media)
    {
        $defaultPathGeneratorClass = config('media-library.path_generator');

        foreach(config('media-library.custom_path_generators', []) as $modelClass => $customPathGeneratorClass) {
            if (is_a($media->model_type, $modelClass, true)) {
                return $customPathGeneratorClass;
            }
        }

        return $defaultPathGeneratorClass;
    }

    protected static function guardAgainstInvalidPathGenerator(string $pathGeneratorClass): void
    {
        if (! class_exists($pathGeneratorClass)) {
            throw InvalidPathGenerator::doesntExist($pathGeneratorClass);
        }

        if (! is_subclass_of($pathGeneratorClass, PathGenerator::class)) {
            throw InvalidPathGenerator::doesNotImplementPathGenerator($pathGeneratorClass);
        }
    }
}
