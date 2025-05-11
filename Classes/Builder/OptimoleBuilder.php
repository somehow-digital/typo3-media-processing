<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\Builder;

class OptimoleBuilder implements BuilderInterface
{
	public const API_ENDPOINT_TEMPLATE = 'https://%key%.i.optimole.com/';

	public const GRAVITY_TOP = 'no';

	public const GRAVITY_LEFT = 'we';

	public const GRAVITY_RIGHT = 'ea';

	public const GRAVITY_BOTTOM = 'so';

	public const GRAVITY_TOP_RIGHT = 'noea';

	public const GRAVITY_TOP_LEFT = 'nowe';

	public const GRAVITY_BOTTOM_RIGHT = 'soea';

	public const GRAVITY_BOTTOM_LEFT = 'sowe';

	public const GRAVITY_CENTER = 'ce';

	public const GRAVITY_SMART = 'sm';

	private ?string $source = null;

	private ?string $type = null;

	private ?array $gravity = null;

	private ?int $width = null;

	private ?int $minWidth = null;

	private ?int $height = null;

	private ?int $minHeight = null;

	private ?array $crop = null;

	private ?string $hash = null;

	public function __construct(
		private readonly string $key,
	) {}

	public function setSource(string $source): self
	{
		$this->source = $source;

		return $this;
	}

	public function getSource(): ?string
	{
		return $this->source;
	}

	public function setType(string $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function getType(): ?string
	{
		return $this->type;
	}

	public function setGravity(string $type, ?int $horizontal, ?int $vertical): self
	{
		$this->gravity = array_filter([$type, $horizontal, $vertical]);

		return $this;
	}

	public function getGravity(): ?array
	{
		return $this->gravity;
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

	public function setMinWidth(int $width): self
	{
		$this->minWidth = $width;

		return $this;
	}

	public function getMinWidth(): ?int
	{
		return $this->minWidth;
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

	public function setMinHeight(int $height): self
	{
		$this->minHeight = $height;

		return $this;
	}

	public function getMinHeight(): ?int
	{
		return $this->minHeight;
	}

	public function setCrop(int $width, int $height, array $gravity): self
	{
		$this->crop = [$width, $height, ...$gravity];

		return $this;
	}

	public function getCrop(): ?array
	{
		return $this->crop;
	}

	public function setHash(string $hash): self
	{
		$this->hash = $hash;

		return $this;
	}

	public function getHash(): ?string
	{
		return $this->hash;
	}

	public function build(): string
	{
		$path = $this->buildPath();

		$endpoint = strtr(static::API_ENDPOINT_TEMPLATE, [
			'%key%' => $this->key,
		]);

		return strtr('%endpoint%/%path%', [
			'%endpoint%' => trim($endpoint, '/'),
			'%path%' => $path,
		]);
	}

	private function buildPath(): string
	{
		$parameters = array_filter([
			'rt' => $this->getType(),
			'g' => $this->getGravity() ? implode(':', $this->getGravity()) : null,
			'w' => $this->getWidth(),
			'mw' => $this->getMinWidth(),
			'h' => $this->getHeight(),
			'mh' => $this->getMinHeight(),
			'c' => $this->getCrop() ? implode(':', $this->getCrop()) : null,
			'cb' => $this->getHash(),
		]);

		$options = implode('/', array_map(static function ($name, $value) {
			return strtr('%name%:%value%', [
				'%name%' => $name,
				'%value%' => $value,
			]);
		}, array_keys($parameters), $parameters));

		$source = trim($this->getSource(), '/');

		return strtr('%options%/%source%', [
			'%source%' => $source,
			'%options%' => $options,
		]);
	}
}
