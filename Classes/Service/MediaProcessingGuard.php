<?php
declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Service;

use SomehowDigital\Typo3\MediaProcessing\Provider\ProviderInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\FileInterface;

class MediaProcessingGuard
{
	private ?array $configuration;

	public function __construct(
		private readonly ProviderInterface $provider,
		?ExtensionConfiguration $configuration,
	) {
		$this->configuration = $configuration?->get('media_processing');
	}

	public function isProcessingAllowed(FileInterface $file): bool
	{
		$request = $GLOBALS['TYPO3_REQUEST'] ?? null;
		if ($request) {
			$context = ApplicationType::fromRequest($request);
			if ($context->isBackend() && !($this->configuration['common']['backend'] ?? false)) {
				return false;
			}
			if ($context->isFrontend() && !($this->configuration['common']['frontend'] ?? false)) {
				return false;
			}
		}

		$storage = $file->getStorage();
		if (!$storage?->isOnline()) {
			return false;
		}

		if (!$storage->isPublic() && !($this->configuration['common']['private'] ?? false)) {
			return false;
		}

		if (!$file->exists()) {
			return false;
		}

		if (!$this->provider->hasConfiguration()) {
			return false;
		}

		if (!empty($this->configuration['common']['ignoreExtensionAssets']) && str_starts_with($file?->getPublicUrl() ?? '', '/_assets/')) {
			return false;
		}

		return true;
	}
}
