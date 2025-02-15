<?php

use Spatie\MediaLibrary\Conversions\ConversionCollection;

beforeEach(function () {
    $media = $this->testModelWithConversion
        ->addMedia($this->getTestJpg())
        ->preservingOriginal()
        ->toMediaCollection();

    $media->manipulations = ['thumb' => ['greyscale' => [], 'height' => [10]]];
    $media->save();

    $secondMedia = $this->testModelWithConversion
        ->addMedia($this->getTestJpg())
        ->preservingOriginal()
        ->toMediaCollection();

    $secondMedia->manipulations = ['thumb' => ['greyscale' => [], 'height' => [20]]];
    $secondMedia->save();

    $avatarMedia = $this->testModelWithConversion
        ->addMedia($this->getTestJpg())
        ->preservingOriginal()
        ->toMediaCollection('avatar');

    $avatarMedia->manipulations = ['thumb' => ['greyscale' => [], 'height' => [10]]];
    $avatarMedia->save();

    $this->media = $media->fresh();
    $this->secondMedia = $media->fresh();
    $this->avatarMedia = $avatarMedia->fresh();
});

it('will prepend the manipulation saved on the model with the wildmark manipulations which will take precedence', function () {
    $this->media->manipulations = [
        'thumb' => ['greyscale' => [], 'height' => [10]],
        '*' => ['brightness' => ['-80']],
    ];

    $conversionCollection = ConversionCollection::createForMedia($this->media);

    $conversion = $conversionCollection->getConversions()[0];

    expect($conversion->getName())->toEqual('thumb');

    $manipulations = $conversion
        ->getManipulations()
        ->toArray();

    $this->assertArrayHasKey('optimize', $manipulations);

    unset($manipulations['optimize']);

    $this->assertEquals([
        'brightness',
        'greyscale',
        'height',
        'format',
        'width',
    ], array_keys($manipulations));

    $this->assertEquals([
        'greyscale' => [],
        'height' => [10],
        'brightness' => ['-80'],
        'format' => ['jpg'],
        'width' => [50],
    ], $manipulations);
});

it('will prepend the manipulation saved on the model', function () {
    $conversionCollection = ConversionCollection::createForMedia($this->media);

    $conversion = $conversionCollection->getConversions()->first();

    expect($conversion->getName())->toEqual('thumb');

    $manipulations = $conversion
        ->getManipulations()
        ->toArray();

    $this->assertArrayHasKey('optimize', $manipulations);

    unset($manipulations['optimize']);

    $this->assertEquals([
        'greyscale',
        'height',
        'format',
        'width',
    ], array_keys($manipulations));

    $this->assertEquals([
        'greyscale' => [],
        'height' => [10],
        'format' => ['jpg'],
        'width' => [50],
    ], $manipulations);
});

it('will prepend the manipulation saved on the model with non default collection', function () {
    $conversionCollection = ConversionCollection::createForMedia($this->avatarMedia);

    $conversion = $conversionCollection->getConversions()[0];

    expect($conversion->getName())->toEqual('thumb');

    $manipulations = $conversion
        ->getManipulations()
        ->toArray();

    $this->assertArrayHasKey('optimize', $manipulations);

    unset($manipulations['optimize']);

    $this->assertEquals([
        'greyscale' => [],
        'height' => [10],
        'format' => ['jpg'],
        'width' => [50],
    ], $manipulations);
});

it('will apply the manipulation on the equally named conversion of every model', function () {
    $mediaItems = [$this->media, $this->secondMedia];
    $manipulations = [];

    foreach ($mediaItems as $mediaItem) {
        $conversionCollection = ConversionCollection::createForMedia($mediaItem);

        $conversion = $conversionCollection->getConversions()[0];

        $manipulationSequence = $conversion
            ->getManipulations()
            ->getManipulationSequence()
            ->toArray();

        $manipulations[] = $manipulationSequence;
    }

    expect($manipulations[1])->toEqual($manipulations[0]);
});
