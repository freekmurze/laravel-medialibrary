<?php

namespace Spatie\Medialibrary\Features\MediaCollections\Exceptions;

use Spatie\Medialibrary\Features\MediaCollections\Exceptions\FileCannotBeAdded;

class MimeTypeNotAllowed extends FileCannotBeAdded
{
    public static function create($file, array $allowedMimeTypes): self
    {
        $mimeType = mime_content_type($file);

        $allowedMimeTypes = implode(', ', $allowedMimeTypes);

        return new static("File has a mimetype of {$mimeType}, while only {$allowedMimeTypes} are allowed");
    }
}
