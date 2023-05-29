<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

class SirvUri implements UriInterface
{
	private ?string $source = null;

	private ?string $scale = null;

	private ?int $width = null;

	private ?int $height = null;

	private ?array $crop = null;

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

	public function setScale(string $scale): self
	{
		$this->scale = $scale;

		return $this;
	}

	public function getScale(): ?string
	{
		return $this->scale;
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
		$this->crop = [
			$width,
			$height,
			$horizontal,
			$vertical,
		];

		return $this;
	}

	public function getCrop(): ?array
	{
		return $this->crop;
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
			'scale.option' => $this->getScale(),
			'w' => $this->getWidth(),
			'h' => $this->getHeight(),
			'cw' => $this->getCrop()[0] ?? 0,
			'ch' => $this->getCrop()[1] ?? 0,
			'cx' => $this->getCrop()[2] ?? 0,
			'cy' => $this->getCrop()[3] ?? 0,
		]);

		$options = implode('&', array_map(static function ($name, $value) {
			return strtr('%name%=%value%', [
				'%name%' => $name,
				'%value%' => $value,
			]);
		}, array_keys($parameters), $parameters));

		return strtr('%source%?%options%', [
			'%source%' => $this->getSource(),
			'%options%' => $options,
		]);
	}
}
