<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Unit\Builder;

use PHPUnit\Framework\Attributes\Test;
use SomehowDigital\ImgProxy\Option\Width;
use SomehowDigital\ImgProxy\Url;
use SomehowDigital\Typo3\MediaProcessing\Builder\ImgProxyBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImgProxyBuilderTest extends UnitTestCase
{
	private const ENDPOINT = 'https://imgproxy.example.com';

	#[Test]
	public function buildPrependsEndpoint(): void
	{
		$url = Url::create()->source('banner.jpg')->options(new Width(1200));

		$this->assertSame(
			self::ENDPOINT . '/_/w:1200/plain/banner.jpg',
			(new ImgProxyBuilder(self::ENDPOINT, $url))->build(),
		);
	}

	#[Test]
	public function buildSupportsRelativeEndpoint(): void
	{
		$url = Url::create()->source('banner.jpg');

		$this->assertSame(
			'/_assets/processed/_/plain/banner.jpg',
			(new ImgProxyBuilder('/_assets/processed/', $url))->build(),
		);
	}

	#[Test]
	public function listenersCanExtendTheUrl(): void
	{
		$builder = new ImgProxyBuilder(self::ENDPOINT, Url::create()->source('banner.jpg'));

		$extended = new ImgProxyBuilder($builder->getEndpoint(), $builder->getUrl()->options(new Width(10)));

		$this->assertSame(self::ENDPOINT . '/_/w:10/plain/banner.jpg', $extended->build());
	}
}
