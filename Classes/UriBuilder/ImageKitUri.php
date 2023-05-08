<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

class ImageKitUri implements UriInterface
{
	private ?string $source = null;

	private ?string $crop = null;

	private ?int $width = null;

	private ?int $height = null;

	private ?array $offset = null;

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

	public function setCrop(string $crop): self
	{
		$this->crop = $crop;

		return $this;
	}

	public function getCrop(): ?string
	{
		return $this->crop;
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

	public function setOffset(int $horizontal, int $vertical): self
	{
		$this->offset = [$horizontal, $vertical];

		return $this;
	}

	public function getOffset(): ?array
	{
		return $this->offset;
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
			'c' => $this->getCrop(),
			'w' => $this->getWidth(),
			'h' => $this->getHeight(),
			'xc' => $this->getOffset()[0] ?? null,
			'yc' => $this->getOffset()[1] ?? null,
			'cm' => $this->getOffset() ? 'extract' : null,
		]);

		$options = implode(',', array_map(static function ($name, $value) {
			return strtr('%name%-%value%', [
				'%name%' => $name,
				'%value%' => $value,
			]);
		}, array_keys($parameters), $parameters));

		return strtr('%options%/%source%', [
			'%options%' => $options,
			'%source%' => $this->getSource(),
		]);
	}
}
