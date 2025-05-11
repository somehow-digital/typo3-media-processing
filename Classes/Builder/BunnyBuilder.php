<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Builder;

class BunnyBuilder implements BuilderInterface
{
	public const SIGNATURE_ALGORITHM = 'sha256';

	public const SIGNATURE_EXPIRATION = 31536000;

	private ?string $source = null;

	private ?int $width = null;

	private ?int $height = null;

	private ?array $crop = null;

	public function __construct(
		private readonly ?string $endpoint,
		private readonly ?string $key,
	) {}

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

	public function build(): string
	{
		$path = $this->buildPath();
		$expiration = time() + static::SIGNATURE_EXPIRATION;

		$signature = $this->getKey()
			? $this->calculateSignature($path, $expiration)
			: null;

		return strtr($signature ? '%endpoint%/%path%&token=%signature%&expires=%expiration%' : '%endpoint%/%path%', [
			'%endpoint%' => trim($this->getEndpoint(), '/'),
			'%path%' => $path,
			'%signature%' => $signature,
			'%expiration%' => $expiration,
		]);
	}

	private function buildPath(): string
	{
		$parameters = array_filter([
			'crop' => $this->getCrop() ? implode(',', $this->getCrop()) : null,
			'height' => $this->getHeight(),
			'width' => $this->getWidth(),
		]);

		$options = implode('&', array_map(static function ($name, $value) {
			return strtr('%name%=%value%', [
				'%name%' => $name,
				'%value%' => rawurlencode((string) $value),
			]);
		}, array_keys($parameters), $parameters));

		$source = rawurlencode(trim($this->getSource(), '/'));

		return strtr('%source%?%options%', [
			'%source%' => $source,
			'%options%' => $options,
		]);
	}

	private function calculateSignature(string $path, int $expiration): string
	{
		$url = parse_url($path);

		parse_str($url['query'], $parameters);
		ksort($parameters);

		$data = implode('', [
			$this->getKey(),
			'/' . $url['path'],
			$expiration,
			urldecode(http_build_query($parameters)),
		]);

		$hash = hash(static::SIGNATURE_ALGORITHM, $data, true);

		return str_replace('=', '', strtr(base64_encode($hash), '+/', '-_'));
	}
}
