---
title: Storing media specific manipulations
weight: 3
---

Imagine you need to apply a 90 degree rotation to a single image. So the rotation should be applied to one specific `Media` and not to all media linked to the given `$yourModel`.

When adding an image to the media library, you can use `withManipulations` to set any media specific manipulations.

Here's a quick example:

```php
$yourModel
   ->addMedia($pathToFile)
   ->withManipulations([
      'thumb' => ['orientation' => ['orientation' => ['orientation' => '90']]],
   ]);
```

The package will regenerate all files (conversions) using the saved manipulation as the first manipulation when creating each derived image.

You can also apply media specific manipulations to an existing `Media` instance.

```php
$mediaItems = $yourModel->getMedia('images');
$mediaItems[0]->manipulations = [
   'thumb' => ['orientation' => ['orientation' => '90']],
];

// This will cause the thumb conversions to be regenerated.
$mediaItems[0]->save();
```

First the rotation will be applied for this specific `$mediaItem`, then the other manipulations you specified in the `thumb` conversion.

Of course you can also set media specific manipulations for multiple conversions in one go:

```php
$yourModel
   ->addMedia($pathToFile)
   ->withManipulations([
      'thumb' => ['orientation' => ['orientation' => '90']],
      'otherConversion' => ['orientation' => ['orientation' => '90']],
   ]);
```

Lets take the example again of this one image `$mediaItem` that needs to be rotated and was linked to `$yourModel`. Imagine we have a lot of conversions for all the media: `thumb`, `small` for web, `cmyk` for print in full resolution.
Having to add all these manipulation keys with `orientation 90` would be a pain. 

You can avoid this pain by using a wildcard. Manipulations of the wildcard will be added to each conversion of the media.

Here's an example:

```php
$yourModel
   ->addMedia($pathToFile)
   ->withManipulations([
      '*' => ['orientation' => ['orientation' => '90']],
   ]);
```

You can also combine wildcard manipulations with one for a specific collection. The wildcard manipulations will always be performed before the collection specific ones.

```php
$yourModel
   ->addMedia($pathToFile)
   ->withManipulations([
      '*' => ['orientation' => ['orientation' => '90']],
      'thumb' => ['greyscale' => []],
   ]);
```
