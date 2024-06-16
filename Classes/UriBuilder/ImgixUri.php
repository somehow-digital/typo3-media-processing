<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

class ImgixUri implements UriInterface
{
	public const SIGNATURE_ALGORITHM = 'md5';

	private ?string $source = null;

	private ?string $fit = null;

	private ?int $width = null;

	private ?int $maxWidth = null;

	private ?int $height = null;

	private ?int $maxHeight = null;

	private ?array $rect = null;

	public function __construct(
		private readonly ?string $endpoint,
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

	public function setFit(string $fit): self
	{
		$this->fit = $fit;

		return $this;
	}

	public function getFit(): ?string
	{
		return $this->fit;
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

	public function setMaxWidth(int $width): self
	{
		$this->maxWidth = $width;

		return $this;
	}

	public function getMaxWidth(): ?int
	{
		return $this->maxWidth;
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

	public function setMaxHeight(int $height): self
	{
		$this->maxHeight = $height;

		return $this;
	}

	public function getMaxHeight(): ?int
	{
		return $this->maxHeight;
	}

	public function setRect(int $horizontal, int $vertical, int $width, int $height): self
	{
		$this->rect = [
			$horizontal,
			$vertical,
			$width,
			$height,
		];

		return $this;
	}

	public function getRect(): ?array
	{
		return $this->rect;
	}

	private function build(): string
	{
		$path = $this->buildPath();

		$signature = $this->getKey()
			? $this->calculateSignature($path)
			: null;

		return strtr($signature ? '%endpoint%/%path%&s=%signature%' : '%endpoint%/%path%', [
			'%endpoint%' => trim($this->endpoint, '/'),
			'%path%' => $path,
			'%signature%' => $signature,
		]);
	}

	private function buildPath(): string
	{
		$parameters = array_filter([
			'fit' => $this->getFit(),
			'w' => $this->getWidth(),
			'max-w' => $this->getMaxWidth(),
			'h' => $this->getHeight(),
			'max-h' => $this->getMaxHeight(),
			'rect' => $this->getRect() ? implode(',', $this->getRect()) : null,
		]);

		ksort($parameters);

		$options = implode('&', array_map(static function ($name, $value) {
			return strtr('%name%=%value%', [
				'%name%' => (string) $name,
				'%value%' => urlencode((string) $value),
			]);
		}, array_keys($parameters), $parameters));

		return strtr('%source%?%options%', [
			'%source%' => trim($this->getSource(), '/'),
			'%options%' => $options,
		]);
	}

	private function calculateSignature(string $path): string
	{
		$data = $this->getKey() . '/' . $path;

		return hash(static::SIGNATURE_ALGORITHM, $data);
	}
}
