<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\Typo3\MediaProcessing\Builder\CloudinaryBuilder;
use SomehowDigital\Typo3\MediaProcessing\Builder\CloudinaryFetchSource;
use SomehowDigital\Typo3\MediaProcessing\Builder\SourceInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CloudinaryBuilderTest extends UnitTestCase
{
	private const BASE_URL = 'https://api.cloudinary.com/v1_1/demo';

	#[Test]
	public function gettersAndSettersPropertiesCanBeSetAndRetrieved(): void
	{
		$deliveryStub = $this->createStub(SourceInterface::class);
		$builder = new CloudinaryBuilder(self::BASE_URL, $deliveryStub, 'secret-key', 'sha256');

		$this->assertSame(self::BASE_URL, $builder->getEndpoint());
		$this->assertSame($deliveryStub, $builder->getDelivery());

		$this->assertSame($builder, $builder->setSource('images/photo.jpg'));
		$this->assertSame('images/photo.jpg', $builder->getSource());

		$this->assertSame($builder, $builder->setMode('fill'));
		$this->assertSame('fill', $builder->getMode());

		$this->assertSame($builder, $builder->setWidth(800));
		$this->assertSame(800, $builder->getWidth());

		$this->assertSame($builder, $builder->setHeight(600));
		$this->assertSame(600, $builder->getHeight());

		$this->assertSame($builder, $builder->setCrop(10, 20, 300, 400));
		$this->assertSame([10, 20, 300, 400], $builder->getCrop());
	}

	#[Test]
	public function buildReturnsUnsignedUrlWhenKeyIsNull(): void
	{
		$deliveryMock = $this->createMock(CloudinaryFetchSource::class);
		$deliveryMock
			->expects($this->once())
			->method('getIdentifier')
			->willReturn('upload');

		$builder = new CloudinaryBuilder('https://api.cloudinary.com/v1_1/demo/', $deliveryMock, null, null);

		$builder->setSource('/products/shoes.png')
			->setMode('limit')
			->setWidth(400)
			->setHeight(300);

		// Expected format: %endpoint%/image/%delivery%/%options%/%source%
		$expectedUrl = 'https://api.cloudinary.com/v1_1/demo/image/upload/c_limit,w_400,h_300/products%2Fshoes.png';

		$this->assertSame($expectedUrl, $builder->build());
	}

	#[Test]
	public function buildReturnsUrlWithCombinedCropAndModeOptions(): void
	{
		$deliveryMock = $this->createMock(CloudinaryFetchSource::class);
		$deliveryMock
			->expects($this->once())
			->method('getIdentifier')
			->willReturn('authenticated');

		$builder = new CloudinaryBuilder(self::BASE_URL, $deliveryMock, null, null);

		$builder->setSource('gallery/landscape.jpg')
			->setCrop(50, 50, 800, 600)
			->setMode('thumb')
			->setWidth(200)
			->setHeight(150);

		// Expects crop configurations first, then mode parameters separated by slashes
		$expectedUrl = 'https://api.cloudinary.com/v1_1/demo/image/authenticated/c_crop,x_50,y_50,w_800,h_600/c_thumb,w_200,h_150/gallery%2Flandscape.jpg';

		$this->assertSame($expectedUrl, $builder->build());
	}

	#[Test]
	public function buildReturnsSignedUrlWhenKeyAndAlgorithmAreProvided(): void
	{
		$deliveryMock = $this->createMock(CloudinaryFetchSource::class);
		$deliveryMock
			->expects($this->once())
			->method('getIdentifier')
			->willReturn('upload');

		$key = 'my-cloudinary-key';
		$algorithm = 'sha256';
		$builder = new CloudinaryBuilder(self::BASE_URL, $deliveryMock, $key, $algorithm);

		$builder->setSource('avatar.jpg')
			->setMode('scale')
			->setWidth(100);

		$url = $builder->build();

		// Programmatically recreate internal signature verification step
		$expectedPath = 'c_scale,w_100/avatar.jpg';
		$hash = hash($algorithm, $expectedPath . $key, true);
		$digest = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
		$expectedSignature = 's--' . mb_substr($digest, 0, 8) . '--';

		$expectedUrl = 'https://api.cloudinary.com/v1_1/demo/image/upload/' . $expectedSignature . '/' . $expectedPath;

		$this->assertSame($expectedUrl, $url);
	}
}
