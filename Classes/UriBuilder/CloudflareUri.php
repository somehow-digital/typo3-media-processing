<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

class CloudflareUri implements UriInterface
{
	private ?string $source = null;

	private ?string $fit = null;

	private ?int $width = null;

	private ?int $height = null;

	private ?array $trim = null;

	private ?array $gravity = null;

	public function __construct(
		private readonly ?string $endpoint,
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

	public function setHeight(int $height): self
	{
		$this->height = $height;

		return $this;
	}

	public function getHeight(): ?int
	{
		return $this->height;
	}

	public function setTrim(int $top = 0, int $right = 0, int $left = 0, $bottom = 0): self
	{
		$this->trim = [
			max(0, $top),
			max(0, $right),
			max(0, $bottom),
			max(0, $left),
		];

		return $this;
	}

	public function getTrim(): ?array
	{
		return $this->trim;
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

		return strtr('%endpoint%/%path%', [
			'%endpoint%' => trim($this->endpoint, '/'),
			'%path%' => $path,
		]);
	}

	private function buildPath(): string
	{
		$parameters = array_filter([
			'fit' => $this->getFit(),
			'width' => $this->getWidth(),
			'height' => $this->getHeight(),
			'trim' => $this->getTrim() ? implode(';', $this->getTrim()) : null,
			'gravity' => $this->getGravity() ? implode('x', $this->getGravity()) : null,
		]);

		$options = implode(',', array_map(static function ($name, $value) {
			return strtr('%name%=%value%', [
				'%name%' => $name,
				'%value%' => $value,
			]);
		}, array_keys($parameters), $parameters));

		$source = rawurlencode(trim($this->getSource(), '/'));

		return strtr('%options%/%source%', [
			'%source%' => $source,
			'%options%' => $options,
		]);
	}
}
