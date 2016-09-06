<?php

namespace Spatie\MediaLibrary\Test\Conversion;

use Spatie\MediaLibrary\BeforeConversion\BeforeConversionDriver;
use Spatie\MediaLibrary\BeforeConversion\BeforeConversionDriverHandler;
use Spatie\MediaLibrary\BeforeConversion\Drivers\ImageDriver;
use Spatie\MediaLibrary\BeforeConversion\Drivers\PdfDriver;
use Spatie\MediaLibrary\BeforeConversion\Drivers\SvgDriver;
use Spatie\MediaLibrary\BeforeConversion\Drivers\VideoDriver;
use Spatie\MediaLibrary\Conversion\Conversion;
use Spatie\MediaLibrary\Media;
use Spatie\MediaLibrary\Test\TestCase;

class BeforeConversionTest extends TestCase
{
    protected $conversionName = 'test';

    /**
     * @var \Spatie\MediaLibrary\Conversion\Conversion
     */
    protected $conversion;

    public function setUp()
    {
        $this->conversion = new Conversion($this->conversionName);

        parent::setUp();
    }

    /** @test */
    public function it_has_the_required_drivers()
    {
        $mediaModelDrivers = (new Media())->getBeforeConversionDrivers();

        $this->assertContains(ImageDriver::class, $mediaModelDrivers);
        $this->assertContains(PdfDriver::class, $mediaModelDrivers);
        $this->assertContains(SvgDriver::class, $mediaModelDrivers);
        $this->assertContains(VideoDriver::class, $mediaModelDrivers);
    }

    /** @test */
    public function it_instantiate_the_required_drivers()
    {
        $mediaModelDrivers = (new Media())->getBeforeConversionDrivers();
        $instanciatedDrivers = app(BeforeConversionDriverHandler::class)->getDrivers();

        $this->assertEquals($mediaModelDrivers->count(), $instanciatedDrivers->count());

        foreach ($instanciatedDrivers as $key => $driver) {
            $this->assertTrue($mediaModelDrivers->contains(get_class($driver)));
            $this->assertEquals($driver->getMediaType(), $key);
        }
    }

    /** @test */
    public function it_implements_the_before_conversion_driver_interface()
    {
        $instanciatedDrivers = app(BeforeConversionDriverHandler::class)->getDrivers();

        foreach ($instanciatedDrivers as $driver) {
            $this->assertContains(BeforeConversionDriver::class, class_implements($driver));
        }
    }

    /**
     * @test
     * @dataProvider extensionProvider
     */
    public function it_can_detect_media_type_from_extension_with_drivers($extension, $type)
    {
        $media = new Media();
        $media->file_name = 'test.'.$extension;
        $this->assertEquals($type, $media->type_from_extension);
    }

    public static function extensionProvider()
    {
        $extensions =
            [
                ['jpg', (new ImageDriver())->getMediaType()],
                ['jpeg', (new ImageDriver())->getMediaType()],
                ['png', (new ImageDriver())->getMediaType()],
                ['gif', (new ImageDriver())->getMediaType()],
                ['webm', (new VideoDriver())->getMediaType()],
                ['mov', (new VideoDriver())->getMediaType()],
                ['mp4', (new VideoDriver())->getMediaType()],
                ['pdf', (new PdfDriver())->getMediaType()],
                ['svg', (new SvgDriver())->getMediaType()],
                ['bla', Media::TYPE_OTHER],
            ];

        $capitalizedExtensions = array_map(function ($extension) {
            $extension[0] = strtoupper($extension[0]);

            return $extension;
        }, $extensions);

        return array_merge($extensions, $capitalizedExtensions);
    }

    /**
     * @test
     * @dataProvider mimeProvider
     *
     * @param string $file
     * @param string $type
     */
    public function it_can_determine_the_type_from_the_mime($file, $type)
    {
        $media = $this->testModel->addMedia($this->getTestFilesDirectory($file))->toMediaLibrary();
        $this->assertEquals($type, $media->type_from_mime);
    }

    public static function mimeProvider()
    {
        return [
            ['image', (new ImageDriver())->getMediaType()],
            ['test.jpg', (new ImageDriver())->getMediaType()],
            ['test.webm', (new VideoDriver())->getMediaType()],
            ['test.mp4', (new VideoDriver())->getMediaType()],
            ['test.pdf', (new PdfDriver())->getMediaType()],
            ['test.svg', (new SvgDriver())->getMediaType()],
            ['test', Media::TYPE_OTHER],
            ['test.txt', Media::TYPE_OTHER],
        ];
    }

    /** @test */
    public function image_driver_can_convert_image()
    {
        $imageFile = (new ImageDriver())->convertToImage($this->getTestJpg(), $this->conversion);

        $this->assertEquals('image/jpeg', mime_content_type($imageFile));
        $this->assertEquals($this->getTestJpg(), $imageFile);
    }

    /** @test */
    public function it_has_a_working_video_driver()
    {
        if (! class_exists('\\FFMpeg\\FFMpeg')) {
            return;
        }

        $imageFile = (new VideoDriver())->convertToImage($this->getTestWebm(), $this->conversion);

        $this->assertEquals(str_replace('.webm', '.jpg', $this->getTestWebm()), $imageFile);
        $this->assertEquals('image/jpeg', mime_content_type($imageFile));
    }

    /** @test */
    public function it_has_a_working_pdf_driver()
    {
        $imageFile = (new PdfDriver())->convertToImage($this->getTestPdf(), $this->conversion);

        $this->assertEquals(str_replace('.pdf', '.jpg', $this->getTestPdf()), $imageFile);
        $this->assertEquals('image/jpeg', mime_content_type($imageFile));
    }

    /** @test */
    public function it_has_a_working_svg_driver()
    {
        $imageFile = (new SvgDriver())->convertToImage($this->getTestSvg(), $this->conversion);

        $this->assertEquals(str_replace('.svg', '.png', $this->getTestSvg()), $imageFile);
        $this->assertEquals('image/png', mime_content_type($imageFile));
    }
}
