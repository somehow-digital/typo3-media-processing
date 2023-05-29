<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

class CloudImageUri implements UriInterface
{
	public const SIGNATURE_ALGORITHM = 'sha1';

	private ?string $source = null;

	private ?string $function = null;

	private ?int $width = null;

	private ?int $height = null;

	private ?array $crop = null;

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

	public function setSource(string $source): self
	{
		$this->source = $source;

		return $this;
	}

	public function getSource(): ?string
	{
		return $this->source;
	}

	public function setFunction(string $function): self
	{
		$this->function = $function;

		return $this;
	}

	public function getFunction(): ?string
	{
		return $this->function;
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

		if ($this->key) {
			$path .= '&ci_sign=' . $this->calculateSignature($path);
		}

		return strtr('%endpoint%/%path%', [
			'%endpoint%' => trim($this->endpoint, '/'),
			'%path%' => $path,
		]);
	}

	private function buildPath(): string
	{
		$parameters = array_filter([
			'func' => $this->getFunction(),
			'w' => $this->getWidth(),
			'h' => $this->getHeight(),
			'tl_px' => $this->getCrop()
				? implode(',', [$this->getCrop()[0], $this->getCrop()[1]])
				: null,
			'br_px' => $this->getCrop()
				? implode(',', [$this->getCrop()[0] + $this->getCrop()[2], $this->getCrop()[1] + $this->getCrop()[3]])
				: null,
		]);

		$options = implode('&', array_map(static function ($name, $value) {
			return strtr('%name%=%value%', [
				'%name%' => $name,
				'%value%' => $value,
			]);
		}, array_keys($parameters), $parameters));

		return strtr('%source%?%options%', [
			'%source%' => trim($this->getSource(), '/'),
			'%options%' => $options,
		]);
	}

	private function calculateSignature(string $path): string
	{
		return hash(static::SIGNATURE_ALGORITHM, $this->key . $path);
	}
}
