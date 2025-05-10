# Changelog

## [Unreleased]

### Changed

* Rename `integration` to `provider` and adapt service classes accordingly.

## [0.10.0]

### Added

* Add support for media processing of files in private storage.
* Add support for rendering online media preview images (for YouTube and Vimeo).
* Add proxy source loader for `gumlet` provider.
* Add `MediaProcessedEvent` event.

### Fixed

* Skip processing if processed file is up-to-date.
* Fix configuration template category names.
* Fix cloudflare trim parameter order.
* Fix CSP configuration.
* Fix status report label.

### Removed

* Remove non-standard focus area handling from all providers.
* Remove backend toolbar action and CLI command for processed media invalidation.
* Remove custom file checksum calculation in favor of native checksum calculation.

## [0.9.0]

### Added

* Add support for `device pixel ratio` parameter for `imgproxy` provider.

## [0.8.0]

### Added

* Support `TYPO3` v13.

## [0.7.1]

### Fixed

* Fix calculation of the provider checksum.

## [0.7.0]

### Added

* Introduce option to enable/disable PDF processing for the `imgproxy` provider.

### Fixed

* Determine and set dimensions of PDF files prior to processing.
* Skip processing for files not having valid dimensions set.
* Declare report classes as private services.

## [0.6.0]

### Added

* Add support for `gravity` parameter for `gumlet.com` provider.
* Add support for URL signing for `bunny.net` provider.

### Fixed

* Fix configuration label for the `imgproxy` provider.
* Fix argument definition on the `ImgixProvider::__construct` method.
* Fix return-type on the `GumletUri::getKey` method.
* Fix incorrect gravity parameter for the `imgproxy` provider.
* Fix incorrect gravity parameter for the `optimole` provider.

## [0.5.0]

### Added

* Add provider for [**gumlet.com** `service`](https://www.gumlet.com/).
* Add support for `gravity` parameter for `cloudflare.com` provider.

### Fixed

* Fix URL building when `config.absRefPrefix` is set.
* Fix composer configuration regarding supported PHP versions.

## [0.4.0]

### Added

* Add focus area support for [**imgproxy** `library`](https://github.com/imgproxy/imgproxy).

## [0.3.3]

### Fixed

* Remove empty parameters from URIs.
* Trim slashes from host URIs.

## [0.3.2]

### Added

* Add URL signing functionality for the imagekit.io provider.

### Fixed

* Fix URL building for the imagekit.io provider.

## [0.3.1]

### Added

* Add setting to store processed files for compatibility with various extensions.

## [0.3.0]

### Added

* Add provider for [**imgix.com** `service`](https://imgix.com/).
* Add provider for [**cloudinary.com** `service`](https://cloudinary.com/).
* Add provider for [**cloudimage.io** `service`](https://cloudimage.io/).

## [0.2.0]

### Added

* Add provider for [**bunny.net** `service`](https://bunny.net/).
* Add provider for [**sirv.com** `service`](https://sirv.com/).

## [0.1.0]

### Added

* Add support for TYPO3 `12`.
* Add support `resizing` and `cropping` operations.
* Add provider for [**imgproxy** `library`](https://github.com/imgproxy/imgproxy).
* Add provider for [**imagor** `library`](https://github.com/cshum/imagor).
* Add provider for [**thumbor** `library`](https://github.com/thumbor/thumbor).
* Add provider for [**optimole.com** `service`](https://optimole.com/).
* Add provider for [**cloudflare.com** `service`](https://developers.cloudflare.com/images/).
* Add provider for [**imagekit.io** `service`](https://imagekit.io/).
* Add backend toolbar action and CLI command for processed media invalidation.

[unreleased]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.10.0...HEAD
[0.10.0]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.9.0...v0.10.0
[0.9.0]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.8.0...v0.9.0
[0.8.0]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.7.1...v0.8.0
[0.7.1]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.7.0...v0.7.1
[0.7.0]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.6.0...v0.7.0
[0.6.0]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.3.3...v0.4.0
[0.3.3]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.3.2...v0.3.3
[0.3.2]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.3.1...v0.3.2
[0.3.1]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/somehow-digital/typo3-media-processing/releases/tag/v0.1.0
