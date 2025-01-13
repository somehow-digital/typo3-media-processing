# Changelog

## [Unreleased]

### Added

* Add support for `device pixel ratio` parameter for `imgproxy` integration.

## [0.8.0]

### Added

* Support `TYPO3` v13.

## [0.7.1]

### Fixed

* Fix calculation of the integration checksum.

## [0.7.0]

### Added

* Introduce option to enable/disable PDF processing for the `imgproxy` integration.

### Fixed

* Determine and set dimensions of PDF files prior to processing.
* Skip processing for files not having valid dimensions set.
* Declare report classes as private services.

## [0.6.0]

### Added

* Add support for `gravity` parameter for `gumlet.com` integration.
* Add support for URL signing for `bunny.net` integration.

### Fixed

* Fix configuration label for the `imgproxy` integration.
* Fix argument definition on the `ImgixImageService::__construct` method.
* Fix return-type on the `GumletUri::getKey` method.
* Fix incorrect gravity parameter for the `imgproxy` integration.
* Fix incorrect gravity parameter for the `optimole` integration.

## [0.5.0]

### Added

* Add integration for [**gumlet.com** `service`](https://www.gumlet.com/).
* Add support for `gravity` parameter for `cloudflare.com` integration.

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

* Add URL signing functionality for the imagekit.io integration.

### Fixed

* Fix URL building for the imagekit.io integration.

## [0.3.1]

### Added

* Add setting to store processed files for compatibility with various extensions.

## [0.3.0]

### Added

* Add integration for [**imgix.com** `service`](https://imgix.com/).
* Add integration for [**cloudinary.com** `service`](https://cloudinary.com/).
* Add integration for [**cloudimage.io** `service`](https://cloudimage.io/).

## [0.2.0]

### Added

* Add integration for [**bunny.net** `service`](https://bunny.net/).
* Add integration for [**sirv.com** `service`](https://sirv.com/).

## [0.1.0]

### Added

* Add support for TYPO3 `12`.
* Add support `resizing` and `cropping` operations.
* Add integration for [**imgproxy** `library`](https://github.com/imgproxy/imgproxy).
* Add integration for [**imagor** `library`](https://github.com/cshum/imagor).
* Add integration for [**thumbor** `library`](https://github.com/thumbor/thumbor).
* Add integration for [**optimole.com** `service`](https://optimole.com/).
* Add integration for [**cloudflare.com** `service`](https://developers.cloudflare.com/images/).
* Add integration for [**imagekit.io** `service`](https://imagekit.io/).
* Add backend toolbar action and CLI command for processed media invalidation.

[unreleased]: https://github.com/somehow-digital/typo3-media-processing/compare/v0.8.0...HEAD
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
