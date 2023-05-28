<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

class ImageKitUri implements UriInterface
{
	private ?string $source = null;

	private ?string $mode = null;

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

	public function setMode(string $mode): self
	{
		$this->mode = $mode;

		return $this;
	}

	public function getMode(): ?string
	{
		return $this->mode;
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
			max(0, $width),
			max(0, $height),
			max(0, $horizontal),
			max(0, $vertical),
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
		$parameters = [
			[
				'cm' => 'extract',
				'w' => $this->getCrop()[0] ?? 0,
				'h' => $this->getCrop()[1] ?? 0,
				'x' => $this->getCrop()[2] ?? 0,
				'y' => $this->getCrop()[3] ?? 0,
			],
			[
				'c' => $this->getMode(),
				'w' => $this->getWidth(),
				'h' => $this->getHeight(),
			],
		];

		$options = implode(':', array_map(static function ($parameter) {
			return implode(',', array_map(static function ($name, $value) {
				return strtr('%name%-%value%', [
					'%name%' => $name,
					'%value%' => $value,
				]);
			}, array_keys($parameter), $parameter));
		}, array_filter($parameters)));

		return strtr('tr:%options%/%source%', [
			'%options%' => $options,
			'%source%' => $this->getSource(),
		]);
	}
}
