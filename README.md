# Media Processing for TYPO3

`Media Processing` for TYPO3 integrates various image processing
libraries and SaaS cloud services into TYPO3 by leveraging their APIs to
process images. This  basically replaces the need for local image processing
libraries like `ImageMagick` for image processing operations.

**Integrations**

| name                                                      | resize | crop | focus | sign |
|:----------------------------------------------------------|:------:|:----:|:-----:|:----:|
| [**imgproxy**](https://imgproxy.net/)                     |   🟢   |  🟢  |  🟢   |  🟢  |
| [**imagor**](https://github.com/cshum/imagor)             |   🟢   |  🟢  |  🟡   |  🟢  |
| [**thumbor**](https://github.com/thumbor/thumbor)         |   🟢   |  🟢  |  🟡   |  🟢  |
| [**optimole.com** ](https://optimole.com/)                |   🟢   |  🟢  |  🟡   |  🔴  |
| [**bunny.net** ](https://bunny.net/)                      |   🟢   |  🟢  |  🟡   |  🟡  |
| [**cloudflare.com** ](https://developers.cloudflare.com/) |   🟢   |  🟢  |  🟡   |  🟡  |
| [**imagekit.io** ](https://imagekit.io/)                  |   🟢   |  🟢  |  🟡   |  🟢  |
| [**sirv.com** ](https://sirv.com/)                        |   🟢   |  🟢  |  🟡   |  🔴  |
| [**imgix.com** ](https://imgix.com/)                      |   🟢   |  🟢  |  🟡   |  🔴  |
| [**cloudinary.com** ](https://cloudinary.com/)            |   🟢   |  🟢  |  🟡   |  🟢  |
| [**cloudimage.io** ](https://cloudimage.io/)              |   🟢   |  🟢  |  🟡   |  🟢  |
| [**gumlet.com** ](https://www.gumlet.com/)                |   🟢   |  🟢  |  🟡   |  🟢  |

🟢 supported  
🟡 unsupported  
🔴 unavailable  

## Installation 📦

**Composer Mode**

Install the [`somehow-digital/typo3-media-processing`](https://packagist.org/packages/somehow-digital/typo3-media-processing) 
package from the `Composer Package Repository`.

```bash
composer require somehow-digital/typo3-media-processing
```

**Legacy Mode**

Install the [`media_processing`](https://extensions.typo3.org/extension/media_processing/)
extension from the `TYPO3 Extension Repository`.

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

**common** `common`

| option      | type    | description                                      | default |
|-------------|---------|--------------------------------------------------|---------|
| integration | options | Service integration to use for image processing. | `null`  |
| storage     | bool    | Enable local storage of processed files.         | `false` |
| backend     | bool    | Enable image processing in the backend.          | `true`  |
| frontend    | bool    | Enable image processing in the frontend.         | `true`  |

**imgproxy** `integration.imgproxy`

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

**imagor** `integration.imagor`

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

**thumbor** `integration.thumbor`

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

**optimole.com** `integration.optimole`

| option     | type   | description                                 | default |
|------------|--------|---------------------------------------------|---------|
| api_key    | string | The API key of the optimole service.        | `null`  |
| source_uri | string | The origin host URL where files are stored. | `null`  |

See also the official [`optimole` documentation](https://docs.optimole.com/)
for more information.

**bunny.net** `integration.bunny`

| option       | type   | description                                 | default |
|--------------|--------|---------------------------------------------|---------|
| api_endpoint | string | The Pull Zone URL of the bunny.net service. | `null`  |
| source_uri   | string | The origin host URL where files are stored. | `null`  |

See also the official [`bunny.net` documentation](https://docs.bunny.net/docs/)
for more information.

**cloudflare.com** `integration.cloudflare`

| option       | type   | description                                 | default |
|--------------|--------|---------------------------------------------|---------|
| api_endpoint | string | The API endpoint of the cloudflare service. | `null`  |
| source_uri   | string | The origin host URL where files are stored. | `null`  |

See also the official [`cloudflare` documentation](https://developers.cloudflare.com/images/image-resizing/)
for more information.

**imagekit.io** `integration.imagekit`

| option        | type   | description                                 | default |
|---------------|--------|---------------------------------------------|---------|
| api_endpoint  | string | The API endpoint of the imagekit service.   | `null`  |
| source_uri    | string | The origin host URL where files are stored. | `null`  |
| signature     | bool   | Enable signature of the imagekit service.   | `false` |
| signature_key | string | The signature key of the imagekit service.  | `null`  |

See also the official [`imagekit.io` documentation](https://docs.imagekit.io/)
for more information.

**sirv.com** `integration.sirv`

| option       | type   | description                                 | default |
|--------------|--------|---------------------------------------------|---------|
| api_endpoint | string | The API endpoint of the sirv service.       | `null`  |
| source_uri   | string | The origin host URL where files are stored. | `null`  |

See also the official [`sirv.com` documentation](https://sirv.com/help/articles/dynamic-imaging/)
for more information.

**imgix.com** `integration.imgix`

| option        | type    | description                                 | default |
|---------------|---------|---------------------------------------------|---------|
| api_endpoint  | string  | The API endpoint of the imgix service.      | `null`  |
| source_loader | options | The source loader of the imgix service.     | folder  |
| source_uri    | string  | The origin host URL where files are stored. | `null`  |
| signature     | bool    | Enable signature of the imgix service.      | `false` |
| signature_key | string  | The signature key of the imgix service.     | `null`  |

See also the official [`imgix.com` documentation](https://docs.imgix.com/)
for more information.

**cloudinary.com** `integration.cloudinary`

| option              | type    | description                                        | default |
|---------------------|---------|----------------------------------------------------|---------|
| api_endpoint        | string  | The API endpoint of the cloudinary service.        | `null`  |
| delivery_mode       | options | The source loader of the cloudinary service.       | fetch   |
| source_uri          | string  | The origin host URL where files are stored.        | `null`  |
| signature           | bool    | Enable signature of the cloudinary service.        | `false` |
| signature_key       | string  | The signature key of the cloudinary service.       | `null`  |
| signature_algorithm | options | The signature algorithm of the cloudinary service. | sha1    |

See also the official [`cloudinary.com` documentation](https://cloudinary.com/documentation/)
for more information.

**cloudimage.io** `integration.cloudimage`

| option              | type    | description                                        | default |
|---------------------|---------|----------------------------------------------------|---------|
| api_endpoint        | string  | The API endpoint of the cloudimage service.        | `null`  |
| source_uri          | string  | The origin host URL where files are stored.        | `null`  |
| signature           | bool    | Enable signature of the cloudinary service.        | `false` |
| signature_key       | string  | The signature key of the cloudinary service.       | `null`  |

See also the official [`cloudimage.io` documentation](https://docs.cloudimage.io/)
for more information.

**gumlet.com** `integration.gumlet`

| option        | type   | description                                  | default |
|---------------|--------|----------------------------------------------|---------|
| api_endpoint  | string | The Gumlet URL of the gumlet.com service.    | `null`  |
| signature     | bool   | Enable signature of the gumlet.com service.  | `false` |
| signature_key | string | The signature key of the gumlet.com service. | `null`  |

See also the official [`gumlet.com` documentation](https://docs.gumlet.com/)
for more information.

## Usage 🪄

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
* ✅ Support `resize` operations.
* ✅ Support `crop` operations.
* ✅ Backend toolbar action and CLI command for processed media invalidation.
* ✅ Integration for [**imgproxy** `library`](https://github.com/imgproxy/imgproxy).
* ✅ Integration for [**imagor** `library`](https://github.com/cshum/imagor).
* ✅ Integration for [**thumbor** `library`](https://github.com/thumbor/thumbor).
* ✅ Integration for [**optimole.com** `service`](https://optimole.com/).
* ✅ Integration for [**bunny.net** `service`](https://bunny.net/).
* ✅ Integration for [**cloudflare.com** `service`](https://developers.cloudflare.com/images/).
* ✅ Integration for [**imagekit.io** `service`](https://imagekit.io/).
* ✅ Integration for [**sirv.com** `service`](https://sirv.com/).
* ✅ Integration for [**imgix.com** `service`](https://imgix.com/).
* ✅ Integration for [**cloudinary.com** `service`](https://cloudinary.com/).
* ✅ Integration for [**cloudimage.io** `service`](https://www.cloudimage.io/).
* ✅ Integration for [**gumlet.com** `service`](https://www.gumlet.com/).
* Support `focusArea` operations.
* Release.

Version **2.0.0** 🏷️ `planning`

* Support for manual and smart focus/gravity configuration.
* Integration for [**glide** `library`](https://glide.thephpleague.com/).
* Integration for [**imaginary** `library`](https://github.com/h2non/imaginary).
* Integration for [**imageflow** `library`](https://www.imageflow.io/).
* Integration for [**weserv** `library`](https://images.weserv.nl/).
* Integration for [**fastly.com** `service`](https://fastly.com/).
* Integration for [**shortpixel.com** `service`](https://shortpixel.com/).
* Integration for [**imagify.io** `service`](https://imagify.io/).
* Integrations per site.
* Send HEAD requests to speed up image generation.

Version **3.0.0** 🏷️ `researching`

* Integration for more image processing libraries/services.
* Integration for video processing libraries/services.

---

[`somehow.digital`](https://somehow.digital/)
