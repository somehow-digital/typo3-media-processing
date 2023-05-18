# Media Processing for TYPO3

`Media Processing` for TYPO3 integrates various image processing
libraries and SaaS cloud services into TYPO3 by leveraging their APIs to
process images. This  basically replaces the need for local image processing
libraries like `ImageMagick` for image processing operations like resizing,
cropping, rotating, etc.

Currently, all integrations support `resize` and `crop` operations for images.

**Integrations**

* [**imgproxy** `library`](https://imgproxy.net/)
* [**imagor** `library`](https://github.com/cshum/imagor)
* [**thumbor** `library`](https://github.com/thumbor/thumbor)
* [**optimole.com** `service`](https://optimole.com/)
* [**bunny.net** `service`](https://bunny.net/)
* [**cloudflare.com** `service`](https://developers.cloudflare.com/images/)
* [**imagekit.io** `service`](https://imagekit.io/)
* [**sirv.com** `service`](https://sirv.com/)
* [**imgix.com** `service`](https://imgix.com/)

## Installation 📦

**Composer Mode**

```bash
composer require somehow-digital/typo3-media-processing
```

**Legacy Mode**

Download and install the `media_processing` extension from the
[TYPO3 TER](https://extensions.typo3.org/extension/media_processing/).

### Requirements

* TYPO3 `12`
* PHP with [`openssl`](https://www.php.net/manual/en/book.openssl.php) extension

## Setup ⚙️

By choosing and configuring one of the available image processing
integrations, TYPO3 will use the configured integration to process
images instead of using the local image processing library.

### Configuration

Extension configuration is located in the TYPO3 backend under
`Admin Tools → Settings → Extension Configuration`.

`common`

| option      | type    | description                                      | default |
|-------------|---------|--------------------------------------------------|---------|
| integration | options | Service integration to use for image processing. | `null`  |
| backend     | bool    | Enable image processing in the backend.          | `true`  |
| frontend    | bool    | Enable image processing in the frontend.         | `true`  |

`integration.imgproxy` **imgproxy** 

| option         | type    | description                                 | default |
|----------------|---------|---------------------------------------------|---------|
| api_endpoint   | string  | The API endpoint of the imgproxy service.   | `null`  |
| source_loader  | options | The source loader of the imgproxy service.  | uri     |
| source_uri     | string  | The origin host URL where files are stored. | `null`  |
| signature      | bool    | Enable signature of the imgproxy service.   | `false` |
| signature_key  | string  | The signature key of the imgproxy service.  | `null`  |
| signature_salt | string  | The signature salt of the imgproxy service. | `null`  |
| signature_size | int     | The signature size of the imgproxy service. | `null`  |
| encryption     | bool    | Enable encryption of the imgproxy service.  | `false` |
| encryption_key | string  | The encryption key of the imgproxy service. | `null`  |

See also the official [`imgproxy` documentation](https://docs.imgproxy.net/)
for more information.

`integration.imagor` **imagor**

| option              | type    | description                                    | default |
|---------------------|---------|------------------------------------------------|---------|
| api_endpoint        | string  | The API endpoint of the imagor service.        | `null`  |
| source_loader       | options | The source loader of the imagor service.       | uri     |
| source_uri          | string  | The origin host URL where files are stored.    | `null`  |
| signature           | bool    | Enable signature of the imagor service.        | `false` |
| signature_key       | string  | The signature key of the imagor service.       | `null`  |
| signature_algorithm | options | The signature algorithm of the imagor service. | sha1    |
| signature_length    | int     | The signature size of the imagor service.      | `null`  |

See also the official [`imagor` documentation](https://github.com/cshum/imagor)
for more information.

`integration.thumbor` **thumbor**

| option              | type    | description                                     | default |
|---------------------|---------|-------------------------------------------------|---------|
| api_endpoint        | string  | The API endpoint of the thumbor service.        | `null`  |
| source_loader       | options | The source loader of the thumbor service.       | uri     |
| source_uri          | string  | The origin host URL where files are stored.     | `null`  |
| signature           | bool    | Enable signature of the thumbor service.        | `false` |
| signature_key       | string  | The signature key of the thumbor service.       | `null`  |
| signature_algorithm | options | The signature algorithm of the thumbor service. | sha1    |
| signature_length    | int     | The signature size of the thumbor service.      | `null`  |

See also the official [`thumbor` documentation](https://thumbor.readthedocs.io/)
for more information.

`integration.optimole` **optimole.com**

| option     | type   | description                                 | default |
|------------|--------|---------------------------------------------|---------|
| api_key    | string | The API key of the optimole service.        | `null`  |
| source_uri | string | The origin host URL where files are stored. | `null`  |

See also the official [`optimole` documentation](https://docs.optimole.com/)
for more information.

`integration.bunny` **bunny.net**

| option       | type   | description                                 | default |
|--------------|--------|---------------------------------------------|---------|
| api_endpoint | string | The Pull Zone URL of the bunny.net service. | `null`  |
| source_uri   | string | The origin host URL where files are stored. | `null`  |

See also the official [`bunny.net` documentation](https://docs.bunny.net/docs/)
for more information.

`integration.cloudflare` **cloudflare.com**

| option       | type   | description                                 | default |
|--------------|--------|---------------------------------------------|---------|
| api_endpoint | string | The API endpoint of the cloudflare service. | `null`  |
| source_uri   | string | The origin host URL where files are stored. | `null`  |

See also the official [`cloudflare` documentation](https://developers.cloudflare.com/images/image-resizing/)
for more information.

`integration.imagekit` **imagekit.io** 

| option       | type   | description                                 | default |
|--------------|--------|---------------------------------------------|---------|
| api_endpoint | string | The API endpoint of the imagekit service.   | `null`  |
| source_uri   | string | The origin host URL where files are stored. | `null`  |

See also the official [`imagekit` documentation](https://docs.imagekit.io/)
for more information.

`integration.sirv` **sirv.com**

| option       | type   | description                                 | default |
|--------------|--------|---------------------------------------------|---------|
| api_endpoint | string | The API endpoint of the sirv service.       | `null`  |
| source_uri   | string | The origin host URL where files are stored. | `null`  |

See also the official [`sirv.com` documentation](https://sirv.com/help/articles/dynamic-imaging/)
for more information.

`integration.imgix` **imgix.com**

| option        | type    | description                                 | default |
|---------------|---------|---------------------------------------------|---------|
| api_endpoint  | string  | The API endpoint of the imgix service.      | `null`  |
| source_loader | options | The source loader of the imgix service.     | folder  |
| source_uri    | string  | The origin host URL where files are stored. | `null`  |
| signature     | bool    | Enable signature of the imgix service.      | `false` |
| signature_key | string  | The signature key of the imgix service.     | `null`  |

See also the official [`imgix.com` documentation](https://docs.imgix.com/)
for more information.

## 🪄 Usage

### Backend

The backend `Invalidate processed media` action can be used to invalidate
processed files for the active media processing integration.

### CLI

The TYPO3 CLI `cleanup:invalidatemedia` command can be used to invalidate
processed files for the active media processing integration.

`vendor/bin/typo3 cleanup:invalidatemedia`

## Support 🛟

* Discord
* [Discussions](https://github.com/somehow-digital/typo3-media-processing/discussions)
* [Issues](https://github.com/somehow-digital/typo3-media-processing/issues)

## Roadmap 🚧

Version **1.0.0** 🏷️ `developing`

* ✅ Support for TYPO3 `12`.
* ✅ Support `resizing` and `cropping` operations.
* ✅ Integration for [**imgproxy** `library`](https://github.com/imgproxy/imgproxy).
* ✅ Integration for [**imagor** `library`](https://github.com/cshum/imagor).
* ✅ Integration for [**thumbor** `library`](https://github.com/thumbor/thumbor).
* ✅ Integration for [**optimole.com** `service`](https://optimole.com/).
* ✅ Integration for [**bunny.net** `service`](https://bunny.net/).
* ✅ Integration for [**cloudflare.com** `service`](https://developers.cloudflare.com/images/).
* ✅ Integration for [**imagekit.io** `service`](https://imagekit.io/).
* ✅ Integration for [**sirv.com** `service`](https://sirv.com/).
* ✅ Integration for [**imgix.com** `service`](https://imgix.com/).
* ✅ Backend toolbar action and CLI command for processed media invalidation.
* Release.

Version **2.0.0** 🏷️ `planning`

* Support for manual and smart focus/gravity configuration.
* Integration for [**glide** `library`](https://glide.thephpleague.com/).
* Integration for [**imaginary** `library`](https://github.com/h2non/imaginary).
* Integration for [**imageflow** `library`](https://www.imageflow.io/).
* Integration for [**fastly.com** `service`](https://fastly.com/).
* Integration for [**cloudinary.com** `service`](https://cloudinary.com/).
* Integration for [**cloudimage.io** `service`](https://www.cloudimage.io/).
* Integrations per site.
* Send HEAD requests to speed up image generation.

Version **3.0.0** 🏷️ `researching`

* Integration for [**gumlet.com** `service`](https://www.gumlet.com/). 
* Integration for more image processing libraries/services.
* Integration for video processing libraries/services.

---

[`somehow.digital`](https://somehow.digital/)
