<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

class GumletUri implements UriInterface
{
	public const SIGNATURE_ALGORITHM = 'md5';

	private ?string $source = null;

	private ?int $width = null;

	private ?int $height = null;

	private ?array $crop = null;

	private ?array $gravity = null;

	public function __construct(
		private readonly string $endpoint,
		private readonly ?string $key,
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

	public function getEndpoint(): string
	{
		return $this->endpoint;
	}

	public function getKey(): ?string
	{
		return $this->key;
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

	public function setCrop(int $width = 0, int $height = 0, int $horizontal = 0, $vertical = 0): self
	{
		$this->crop = [
			max(0, $horizontal),
			max(0, $vertical),
			max(0, $width),
			max(0, $height),
		];

		return $this;
	}

	public function getCrop(): ?array
	{
		return $this->crop;
	}

	public function getGravity(): ?array
	{
		return $this->gravity;
	}

	public function setGravity(float $horizontalOffset, float $verticalOffset): self
	{
		$this->gravity = [$horizontalOffset, $verticalOffset];

		return $this;
	}

	private function build(): string
	{
		$path = $this->buildPath();

		$signature = $this->getKey()
			? $this->calculateSignature($path)
			: null;

		return strtr($signature ? '%endpoint%/%path%&s=%signature%' : '%endpoint%/%path%', [
			'%endpoint%' => trim($this->getEndpoint(), '/'),
			'%path%' => trim($path, '/'),
			'%signature%' => $signature,
		]);
	}

	private function buildPath(): string
	{
		$parameters = array_filter([
			'extract' => $this->getCrop() && !$this->getGravity() ? implode(',', $this->getCrop()) : null,
			'mode' => $this->getGravity() ? 'crop' : null,
			'crop' =>  $this->getGravity() ? 'focalpoint' : null,
			'fp-x' => $this->getGravity() ? $this->getGravity()[0] : null,
			'fp-y' => $this->getGravity() ? $this->getGravity()[1] : null,
			'w' => $this->getWidth(),
			'h' => $this->getHeight(),
		]);

		$options = implode('&', array_map(static function ($name, $value) {
			return strtr('%name%=%value%', [
				'%name%' => $name,
				'%value%' => $value,
			]);
		}, array_keys($parameters), $parameters));

		$source = trim($this->getSource(), '/');

		return strtr('%source%?%options%', [
			'%source%' => $source,
			'%options%' => $options,
		]);
	}

	private function calculateSignature(string $path): string
	{
		$data = strtr('%key%/%path%', [
			'%key%' => $this->getKey(),
			'%path%' => $path,
		]);

		return base64_encode(hash(static::SIGNATURE_ALGORITHM, $data, true));
	}
}
