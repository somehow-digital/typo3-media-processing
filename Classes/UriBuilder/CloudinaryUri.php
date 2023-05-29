<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

class CloudinaryUri implements UriInterface
{
	private ?string $source = null;

	private ?string $mode = null;

	private ?int $width = null;

	private ?int $height = null;

	private ?array $crop = null;

	public function __construct(
		private readonly string $endpoint,
		private readonly UriSourceInterface $delivery,
		private readonly ?string $key,
		private readonly ?string $algorithm,
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

	public function getDelivery(): ?UriSourceInterface
	{
		return $this->delivery;
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

	public function setCrop(int $horizontal, int $vertical, int $width, int $height): self
	{
		$this->crop = [
			$horizontal,
			$vertical,
			$width,
			$height,
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

		$signature = $this->key
			? $this->calculateSignature($path)
			: null;

		return strtr($signature ? '%endpoint%/%type%/%delivery%/%signature%/%path%' : '%endpoint%/%type%/%delivery%/%path%', [
			'%endpoint%' => trim($this->getEndpoint(), '/'),
			'%type%' => 'image',
			'%delivery%' => $this->getDelivery()?->getIdentifier(),
			'%signature%' => $signature,
			'%path%' => trim($path, '/'),
		]);
	}

	private function buildPath(): string
	{
		$parameters = array_filter([
			$this->getCrop() ? array_filter([
				'c' => 'crop',
				'x' => $this->getCrop()[0],
				'y' => $this->getCrop()[1],
				'w' => $this->getCrop()[2],
				'h' => $this->getCrop()[3],
			]): null,
			$this->getMode() ? array_filter([
				'c' => $this->getMode(),
				'w' => $this->getWidth(),
				'h' => $this->getHeight(),
			]) : null,
		]);

		$options = implode('/', array_map(static function ($parameter) {
			return implode(',', array_map(static function ($name, $value) {
				return strtr('%name%_%value%', [
					'%name%' => $name,
					'%value%' => $value,
				]);
			}, array_keys($parameter), $parameter));
		}, $parameters));

		return strtr('%options%/%source%', [
			'%source%' => trim($this->getSource(), '/'),
			'%options%' => $options,
		]);
	}

	private function calculateSignature(string $path): string
	{
		$hash = hash($this->algorithm, $path . $this->key, true);
		$digest = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
		$signature = mb_substr($digest, 0, 8);

		return strtr('s--%signature%--', [
			'%signature%' => $signature,
		]);
	}
}
