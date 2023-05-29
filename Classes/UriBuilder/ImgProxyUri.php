<?php

declare(strict_types=1);

namespace SomehowDigital\Typo3\MediaProcessing\UriBuilder;

class ImgProxyUri implements UriInterface
{
	public const SIGNATURE_INSECURE = 'insecure';

	public const SIGNATURE_ALGORITHM = 'sha256';

	public const ENCRYPTION_ALGORITHM = 'aes-256-cbc';

	public const ENCRYPTION_PLAIN = 'plain';

	public const ENCRYPTION_ENCRYPTED = 'enc';

	public const ENCRYPTION_SEGMENT_LENGTH = 16;

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
		private readonly ?string $endpoint,
		private readonly ?string $key,
		private readonly ?string $salt,
		private readonly ?int $size,
		private readonly ?string $secret,
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

	public function setType(string $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function getType(): ?string
	{
		return $this->type;
	}

	public function setGravity(string $type, ?int $horizontalOffset, ?int $verticalOffset): self
	{
		$this->gravity = array_filter([$type, $horizontalOffset, $verticalOffset]);

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
		$this->crop = array_filter([$width, $height, ...$gravity]);

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

	private function build(): string
	{
		$path = $this->buildPath();

		$signature = $this->key && $this->salt
			? $this->calculateSignature($path)
			: static::SIGNATURE_INSECURE;

		return strtr('%endpoint%/%signature%/%path%', [
			'%endpoint%' => trim($this->endpoint, '/'),
			'%signature%' => $signature,
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

		$source = $this->secret && extension_loaded('openssl')
			? $this->encryptSource($this->getSource())
			: $this->getSource();

		$prefix = $this->secret && extension_loaded('openssl')
			? static::ENCRYPTION_ENCRYPTED
			: static::ENCRYPTION_PLAIN;

		return strtr('%options%/%prefix%/%source%', [
			'%source%' => trim($source, '/'),
			'%prefix%' => $prefix,
			'%options%' => $options,
		]);
	}

	private function calculateSignature(string $path): string
	{
		$data = pack('H*', $this->salt).'/'.trim($path, '/');
		$key = pack('H*', $this->key);

		$hash = hash_hmac(static::SIGNATURE_ALGORITHM, $data, $key, true);
		$digest = base64_encode(mb_substr($hash, 0, $this->size ?: null, '8bit'));

		return rtrim(strtr($digest, '+/', '-_'), '=');
	}

	private function encryptSource(string $source): string
	{
		$vector = openssl_random_pseudo_bytes(static::ENCRYPTION_SEGMENT_LENGTH);

		$cipher = openssl_encrypt(
			$source,
			static::ENCRYPTION_ALGORITHM,
			$this->secret,
			OPENSSL_RAW_DATA,
			$vector,
		);

		$digest = base64_encode($vector.$cipher);

		return rtrim(strtr($digest, '+/', '-_'), '=');
	}
}
