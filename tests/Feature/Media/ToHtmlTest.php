<?php

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Tests\TestCase;
use Spatie\MediaLibrary\Tests\TestSupport\TestModels\TestModelWithCustomLoadingAttribute;
use Spatie\Snapshots\MatchesSnapshots;

uses(TestCase::class);
uses(MatchesSnapshots::class);

beforeEach(function () {
    $this->testModelWithConversion
        ->addMedia($this->getTestJpg())
        ->preservingOriginal()
        ->toMediaCollection();
});

it('can render itself as an image', function () {
    $this->assertEquals(
        '<img src="/media/1/test.jpg" alt="test">',
        firstMedia()->img(),
    );
});

it('can render a conversion of itself as an image', function () {
    $this->assertEquals(
        '<img src="/media/1/conversions/test-thumb.jpg" alt="test">',
        firstMedia()->img('thumb')
    );
});

it('can render extra attributes', function () {
    $this->assertEquals(
        '<img class="my-class" id="my-id" src="/media/1/conversions/test-thumb.jpg" alt="test">',
        firstMedia()->img('thumb', ['class' => 'my-class', 'id' => 'my-id']),
    );
});

test('a media instance is htmlable', function () {
    $media = firstMedia();

    $renderedView = $this->renderView('media', compact('media'));

    $this->assertEquals(
        '<img src="/media/1/test.jpg" alt="test"> <img src="/media/1/conversions/test-thumb.jpg" alt="test">',
        $renderedView,
    );
});

test('converting a non image to an image tag will not blow up', function () {
    $media = $this->testModelWithConversion
        ->addMedia($this->getTestPdf())
        ->toMediaCollection();

    $this->assertEquals('', $media->img());
});

it('can render itself with responsive images and a placeholder', function () {
    $media = $this->testModelWithConversion
        ->addMedia($this->getTestJpg())
        ->withResponsiveImages()
        ->toMediaCollection();

    $image = $media->refresh()->img();

    $this->assertEquals(3, substr_count($image, '/media/2/responsive-images/'));
    $this->assertTrue(Str::contains($image, 'data:image/svg+xml;base64,'));
});

it('can render itself with responsive images of a conversion and a placeholder', function () {
    $media = $this->testModelWithResponsiveImages
        ->addMedia($this->getTestJpg())
        ->toMediaCollection();

    $image = $media->refresh()->img('thumb');

    $this->assertStringContainsString('/media/2/responsive-images/', $image);
    $this->assertStringContainsString('data:image/svg+xml;base64,', $image);
});

it('will not rendering extra javascript or including base64 svg when tiny placeholders are turned off', function () {
    config()->set('media-library.responsive_images.use_tiny_placeholders', false);

    $media = $this->testModelWithConversion
        ->addMedia($this->getTestJpg())
        ->withResponsiveImages()
        ->toMediaCollection();

    $imgTag = $media->refresh()->img();

    $this->assertEquals('<img srcset="http://localhost/media/2/responsive-images/test___media_library_original_340_280.jpg 340w, http://localhost/media/2/responsive-images/test___media_library_original_284_233.jpg 284w, http://localhost/media/2/responsive-images/test___media_library_original_237_195.jpg 237w" src="/media/2/test.jpg" width="340" height="280">', $imgTag);
});

test('the loading attribute can be specified on the conversion', function () {
    $media = TestModelWithCustomLoadingAttribute::create(['name' => 'test'])
        ->addMedia($this->getTestJpg())
        ->toMediaCollection();

    $originalImgTag = $media->refresh()->img();
    $this->assertEquals('<img src="/media/2/test.jpg" alt="test">', $originalImgTag);

    $lazyConversionImageTag = $media->refresh()->img('lazy-conversion');
    $this->assertEquals('<img loading="lazy" src="/media/2/conversions/test-lazy-conversion.jpg" alt="test">', $lazyConversionImageTag);

    $eagerConversionImageTag = $media->refresh()->img('eager-conversion');
    $this->assertEquals('<img loading="eager" src="/media/2/conversions/test-eager-conversion.jpg" alt="test">', $eagerConversionImageTag);
});

it('has a shorthand function to use lazy loading', function () {
    $this->assertEquals(
        '<img loading="lazy" src="/media/1/test.jpg" alt="test">',
        firstMedia()->img()->lazy()
    );
});

it('can set extra attributes', function () {
    $this->assertEquals(
        '<img extra="value" src="/media/1/test.jpg" alt="test">',
        (string) firstMedia()->img()->attributes(['extra' => 'value'])
    );
});

// Helpers
function firstMedia(): Media
{
    return Media::first();
}
