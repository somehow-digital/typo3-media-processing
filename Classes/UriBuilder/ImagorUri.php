<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

class ImagorUri implements UriInterface
{
	public const SIGNATURE_UNSAFE = 'unsafe';

	private ?string $source = null;

	private ?string $type = null;

	private ?int $width = null;

	private ?int $height = null;

	private ?array $crop = null;

	public function __construct(
		private readonly ?string $endpoint,
		private readonly ?string $key,
		private readonly ?string $algorithm,
		private readonly ?int $length,
	) {
	}

	public function __invoke(): string
	{
		return $this->build();
	}

	public function __toString(): string
	{
		return $this->build();
	}

	public function setSource(string $source): self
	{
		$this->source = $source;

		return $this;
	}

	public function getSource(): ?string
	{
		return $this->source;
	}

	public function setType(string $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function getType(): ?string
	{
		return $this->type;
	}

	public function setWidth(int $width): self
	{
		$this->width = $width;

		return $this;
	}

	public function getWidth(): ?int
	{
		return $this->width;
	}

	public function setHeight(int $height): self
	{
		$this->height = $height;

		return $this;
	}

	public function getHeight(): ?int
	{
		return $this->height;
	}

	public function setCrop(int $width, int $height, int $horizontal, int $vertical): self
	{
		$this->crop = [$width, $height, $horizontal, $vertical];

		return $this;
	}

	public function getCrop(): ?array
	{
		return $this->crop;
	}

	private function build(): string
	{
		$path = $this->buildPath();
		$signature = $this->calculateSignature($path);

		return strtr('%endpoint%/%signature%/%path%', [
			'%endpoint%' => trim($this->endpoint, '/'),
			'%signature%' => $signature,
			'%path%' => $path,
		]);
	}

	private function buildPath(): string
	{
		$parameters = [];

		if ($this->getCrop()) {
			$parameters[] = sprintf(
				'%sx%s:%sx%s',
				$this->getCrop()[2],
				$this->getCrop()[3],
				$this->getCrop()[0] + $this->getCrop()[2],
				$this->getCrop()[1] + $this->getCrop()[3],
			);
		}

		if ($this->getWidth() || $this->getHeight()) {
			$parameters[] = $this->getType();

			$parameters[] = sprintf(
				'%sx%s',
				$this->getWidth() ?? 0,
				$this->getHeight() ?? 0,
			);
		}

		$options = implode('/', array_filter($parameters));

		$source = rawurlencode(trim($this->getSource(), '/'));

		return strtr('%options%/%source%', [
			'%source%' => $source,
			'%options%' => $options,
		]);
	}

	private function calculateSignature(string $path): string
	{
		if (! $this->key) {
			return static::SIGNATURE_UNSAFE;
		}

		$hash = hash_hmac($this->algorithm, $path, $this->key, true);
		$digest = base64_encode($hash);
		$signature = mb_substr($digest, 0, $this->length ?: null);

		return strtr($signature, '+/', '-_');
	}
}
