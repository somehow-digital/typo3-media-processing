<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Builder;

class ImgLabBuilder implements BuilderInterface
{
	public const SIGNATURE_ALGORITHM = 'sha256';

	private ?string $source = null;

	private ?string $mode = null;

	private ?int $width = null;

	private ?int $height = null;

	private ?array $region = null;

	public function __construct(
		private readonly string $endpoint,
		private readonly ?string $key,
		private readonly ?string $salt,
	) {}

	public function getEndpoint(): string
	{
		return $this->endpoint;
	}

	public function getKey(): ?string
	{
		return $this->key;
	}

	public function getSalt(): ?string
	{
		return $this->salt;
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

	public function setRegion(int $horizontal, int $vertical, int $width, int $height): self
	{
		$this->region = [
			$horizontal,
			$vertical,
			$width,
			$height,
		];

		return $this;
	}

	public function getRegion(): ?array
	{
		return $this->region;
	}

	public function build(): string
	{
		$path = $this->buildPath();

		$signature = $this->getKey()
			? $this->calculateSignature($path)
			: null;

		return strtr($signature ? '%endpoint%/%path%&signature=%signature%' : '%endpoint%/%path%', array_filter([
			'%endpoint%' => trim($this->endpoint, '/'),
			'%path%' => $path,
			'%signature%' => $signature,
		]));
	}

	private function buildPath(): string
	{
		$parameters = array_filter([
			'mode' => $this->getMode(),
			'width' => $this->getWidth(),
			'height' => $this->getHeight(),
			'region' => $this->getRegion() ? implode(',', $this->getRegion()) : null,
		]);

		$options = implode('&', array_map(static function ($name, $value) {
			return strtr('%name%=%value%', [
				'%name%' => $name,
				'%value%' => $value,
			]);
		}, array_keys($parameters), $parameters));

		$source = rawurlencode(trim($this->getSource(), '/'));

		return strtr('%source%?%options%', [
			'%source%' => $source,
			'%options%' => $options,
		]);
	}

	private function calculateSignature(string $path): string
	{
		$data = strtr('%salt%/%path%', [
			'%salt%' => base64_decode($this->getSalt(), true),
			'%path%' => rawurldecode($path),
		]);

		$hash = hash_hmac(static::SIGNATURE_ALGORITHM, $data, base64_decode($this->getKey(), true), true);
		$digest = base64_encode($hash);

		return rtrim(strtr($digest, '+/', '-_'), '=');
	}
}
