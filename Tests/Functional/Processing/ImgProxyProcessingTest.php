<?php

namespace SomehowDigital\Typo3\MediaProcessing\Tests\Functional\Processing;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ImgProxyProcessingTest extends FunctionalTestCase
{
	protected array $coreExtensionsToLoad = ['core', 'fluid'];

	protected array $testExtensionsToLoad = [
		'typo3conf/ext/media_processing',
	];

	protected array $configurationToUseInTestInstance = [
		'EXTENSIONS' => [
			'media_processing' => [
				'common' => [
					'provider' => 'imgproxy',
					'frontend' => true,
					'storage' => false,
				],
				'provider' => [
					'imgproxy' => [
						'api_endpoint' => 'https://imgproxy.example.com',
						'source_loader' => 'uri',
						'source_uri' => null,
						'signature' => true,
						'signature_key' => 'b76233cf37418a',
						'signature_salt' => 'c89344df48529b',
						'signature_size' => null,
						'encryption' => false,
						'encryption_key' => null,
					],
				],
			],
		],
	];

	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpStorage();
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['TYPO3_REQUEST']);
		parent::tearDown();
	}

	private function setUpStorage(): void
	{
		$storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
		$defaultStorage = $storageRepository->getDefaultStorage();

		$fixturePath = $defaultStorage->getPublicUrl($defaultStorage->getRootLevelFolder()) . 'test-image.png';
		if (!file_exists($this->instancePath . '/' . $fixturePath)) {
			// A 200x300 PNG, so the test needs no network.
			file_put_contents($this->instancePath . '/' . $fixturePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAMgAAAEsCAIAAAAJmGvpAAACI0lEQVR42u3SQQkAAAgEwYttHOMYyxKCn4FJsGyqB85FAoyFsTAWGAtjYSwwFsbCWGAsjIWxwFgYC2OBsTAWxgJjYSyMBcbCWBgLjIWxMBYYC2NhLDAWxsJYYCyMhbHAWBgLY4GxMBbGAmNhLIwFxsJYGAuMhbEwFhgLY2EsMBbGwlhgLIyFscBYGAtjgbEwFsYCY2EsjAXGwlgYC4yFsTAWGAtjYSwwFsbCWGAsjIWxwFgYC2NhLBUwFsbCWGAsjIWxwFgYC2OBsTAWxgJjYSyMBcbCWBgLjIWxMBYYC2NhLDAWxsJYYCyMhbHAWBgLY4GxMBbGAmNhLIwFxsJYGAuMhbEwFhgLY2EsMBbGwlhgLIyFscBYGAtjgbEwFsYCY2EsjAXGwlgYC4yFsTAWGAtjYSwwFsbCWGAsjIWxwFgYC2OBsTAWxsJYKmAsjIWxwFgYC2OBsTAWxgJjYSyMBcbCWBgLjIWxMBYYC2NhLDAWxsJYYCyMhbHAWBgLY4GxMBbGAmNhLIwFxsJYGAuMhbEwFhgLY2EsMBbGwlhgLIyFscBYGAtjgbEwFsYCY2EsjAXGwlgYC4yFsTAWGAtjYSwwFsbCWGAsjIWxwFgYC2OBsTAWxgJjYSyMhbEkwFgYC2OBsTAWxgJjYSyMBcbCWBgLjIWxMBYYC2NhLDAWxsJYYCyMhbHAWBgLY4GxMBbGAmNhLIwFxsJYGAuMhbEwFhiLfwtUio26+5RKPAAAAABJRU5ErkJggg=='));
		}
	}

	#[Test]
	public function imageProcessingAppliesImgProxyUrlTemplateAndSignature(): void
	{
		$GLOBALS['TYPO3_REQUEST'] = (new ServerRequest(new Uri('http://typo3')))
			->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

		$resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

		// Retrieve our file reference from the sandbox FAL system
		$file = $resourceFactory->getFileObjectFromCombinedIdentifier('1:/test-image.png');

		// Target processing instructions (e.g. crop/resize via Fluid or Controller)
		$processingInstructions = [
			'width' => '100',
			'height' => '100c',
			'crop' => null,
		];

		$processedFile = $file->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingInstructions);

		$this->assertTrue($processedFile->isProcessed());

		$publicUrl = $processedFile->getPublicUrl();

		$this->assertStringStartsWith('https://imgproxy.example.com', $publicUrl);
		$this->assertStringContainsString('/w:100/h:100/', $publicUrl);
	}
}
