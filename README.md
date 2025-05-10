# Media Processing for TYPO3

`Media Processing` for TYPO3 integrates various image processing
libraries and SaaS cloud services into TYPO3 by leveraging their APIs to
process images. This  basically replaces the need for local image processing
libraries like `ImageMagick` for image processing operations.

**Providers**

| name                                                      | resize | crop | focus | sign |
|:----------------------------------------------------------|:------:|:----:|:-----:|:----:|
| [**imgproxy**](https://imgproxy.net/)                     |   🟢   |  🟢  |  🟢   |  🟢  |
| [**imagor**](https://github.com/cshum/imagor)             |   🟢   |  🟢  |  🟡   |  🟢  |
| [**thumbor**](https://github.com/thumbor/thumbor)         |   🟢   |  🟢  |  🟡   |  🟢  |
| [**optimole.com** ](https://optimole.com/)                |   🟢   |  🟢  |  🟢   |  🔴  |
| [**bunny.net** ](https://bunny.net/)                      |   🟢   |  🟢  |  🔴   |  🟢  |
| [**cloudflare.com** ](https://developers.cloudflare.com/) |   🟢   |  🟢  |  🟢   |  🔴  |
| [**imagekit.io** ](https://imagekit.io/)                  |   🟢   |  🟢  |  🟡   |  🟢  |
| [**sirv.com** ](https://sirv.com/)                        |   🟢   |  🟢  |  🟡   |  🔴  |
| [**imgix.com** ](https://imgix.com/)                      |   🟢   |  🟢  |  🟡   |  🟢  |
| [**cloudinary.com** ](https://cloudinary.com/)            |   🟢   |  🟢  |  🟡   |  🟢  |
| [**cloudimage.io** ](https://cloudimage.io/)              |   🟢   |  🟢  |  🟡   |  🟢  |
| [**gumlet.com** ](https://www.gumlet.com/)                |   🟢   |  🟢  |  🟢   |  🟢  |

* `resize`: Provider supports resize operations.
* `crop`: Provider supports crop operations.
* `focus`: Provider supports gravity or focus points. (experimental)
* `sign`: Provider supports URL signing.

🟢 supported and integrated  
🟡 supported but not integrated  
🔴 unsupported  

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

* TYPO3 `12`-`13`
* PHP `8.1`-`8.4`
* PHP [`openssl`](https://www.php.net/manual/en/book.openssl.php) extension

## Setup ⚙️

By choosing and configuring one of the available image processing
providers, TYPO3 will use the configured provider to process
images instead of using the local image processing library.

### Configuration

Extension configuration is located in the TYPO3 backend under
`Admin Tools → Settings → Extension Configuration`.

**common** `common`

| option     | type    | description                                     | default |
|------------|---------|-------------------------------------------------|---------|
| provider   | options | Service provider to use for image processing.   | `null`  |
| storage    | bool    | Enable local storage of processed files.        | `false` |
| backend    | bool    | Enable image processing in the backend.         | `true`  |
| frontend   | bool    | Enable image processing in the frontend.        | `true`  |

**imgproxy** `provider.imgproxy`

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

**imagor** `provider.imagor`

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

**thumbor** `provider.thumbor`

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

**optimole.com** `provider.optimole`

| option     | type   | description                                 | default |
|------------|--------|---------------------------------------------|---------|
| api_key    | string | The API key of the optimole service.        | `null`  |
| source_uri | string | The origin host URL where files are stored. | `null`  |

See also the official [`optimole` documentation](https://docs.optimole.com/)
for more information.

**bunny.net** `provider.bunny`

| option        | type   | description                                 | default |
|---------------|--------|---------------------------------------------|---------|
| api_endpoint  | string | The Pull Zone URL of the bunny.net service. | `null`  |
| source_uri    | string | The origin host URL where files are stored. | `null`  |
| signature     | bool   | Enable signature of the bunny.net service.  | `false` |
| signature_key | string | The signature key of the bunny.net service. | `null`  |

See also the official [`bunny.net` documentation](https://docs.bunny.net/docs/)
for more information.

**cloudflare.com** `provider.cloudflare`

| option       | type   | description                                 | default |
|--------------|--------|---------------------------------------------|---------|
| api_endpoint | string | The API endpoint of the cloudflare service. | `null`  |
| source_uri   | string | The origin host URL where files are stored. | `null`  |

See also the official [`cloudflare` documentation](https://developers.cloudflare.com/images/image-resizing/)
for more information.

**imagekit.io** `provider.imagekit`

| option        | type   | description                                 | default |
|---------------|--------|---------------------------------------------|---------|
| api_endpoint  | string | The API endpoint of the imagekit service.   | `null`  |
| source_uri    | string | The origin host URL where files are stored. | `null`  |
| signature     | bool   | Enable signature of the imagekit service.   | `false` |
| signature_key | string | The signature key of the imagekit service.  | `null`  |

See also the official [`imagekit.io` documentation](https://docs.imagekit.io/)
for more information.

**sirv.com** `provider.sirv`

| option       | type   | description                                 | default |
|--------------|--------|---------------------------------------------|---------|
| api_endpoint | string | The API endpoint of the sirv service.       | `null`  |
| source_uri   | string | The origin host URL where files are stored. | `null`  |

See also the official [`sirv.com` documentation](https://sirv.com/help/articles/dynamic-imaging/)
for more information.

**imgix.com** `provider.imgix`

| option        | type    | description                                 | default |
|---------------|---------|---------------------------------------------|---------|
| api_endpoint  | string  | The API endpoint of the imgix service.      | `null`  |
| source_loader | options | The source loader of the imgix service.     | folder  |
| source_uri    | string  | The origin host URL where files are stored. | `null`  |
| signature     | bool    | Enable signature of the imgix service.      | `false` |
| signature_key | string  | The signature key of the imgix service.     | `null`  |

See also the official [`imgix.com` documentation](https://docs.imgix.com/)
for more information.

**cloudinary.com** `provider.cloudinary`

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

**cloudimage.io** `provider.cloudimage`

| option              | type    | description                                        | default |
|---------------------|---------|----------------------------------------------------|---------|
| api_endpoint        | string  | The API endpoint of the cloudimage service.        | `null`  |
| source_uri          | string  | The origin host URL where files are stored.        | `null`  |
| signature           | bool    | Enable signature of the cloudinary service.        | `false` |
| signature_key       | string  | The signature key of the cloudinary service.       | `null`  |

See also the official [`cloudimage.io` documentation](https://docs.cloudimage.io/)
for more information.

**gumlet.com** `provider.gumlet`

| option        | type   | description                                  | default |
|---------------|--------|----------------------------------------------|---------|
| api_endpoint  | string | The Gumlet URL of the gumlet.com service.    | `null`  |
| signature     | bool   | Enable signature of the gumlet.com service.  | `false` |
| signature_key | string | The signature key of the gumlet.com service. | `null`  |

See also the official [`gumlet.com` documentation](https://docs.gumlet.com/)
for more information.

## Usage 🪄

## API

### Events

**MediaProcessedEvent**

The `MediaProcessedEvent` is dispatched after a media file has been processed
and can be used to adapt the final URI before it is stored in the database.

```php
use SomehowDigital\Typo3\MediaProcessing\Event\MediaProcessedEvent;

class MediaProcessedEventListener
{
  public function __invoke(MediaProcessedEvent $event): void
  {
    $event->getService();
    $event->getTask();
    $event->getResult();
  }
}
```

## Support 🛟

* Discord
* [Discussions](https://github.com/somehow-digital/typo3-media-processing/discussions)
* [Issues](https://github.com/somehow-digital/typo3-media-processing/issues)

## Roadmap 🚧

Version **1.0.0** 🏷️ `developing`

* ✅ Support for TYPO3 `12`.
* ✅ Support for TYPO3 `13`.
* ✅ Support `resize` operations.
* ✅ Support `crop` operations.
* ✅ Provider for [**imgproxy** `library`](https://github.com/imgproxy/imgproxy).
* ✅ Provider for [**imagor** `library`](https://github.com/cshum/imagor).
* ✅ Provider for [**thumbor** `library`](https://github.com/thumbor/thumbor).
* ✅ Provider for [**optimole.com** `service`](https://optimole.com/).
* ✅ Provider for [**bunny.net** `service`](https://bunny.net/).
* ✅ Provider for [**cloudflare.com** `service`](https://developers.cloudflare.com/images/).
* ✅ Provider for [**imagekit.io** `service`](https://imagekit.io/).
* ✅ Provider for [**sirv.com** `service`](https://sirv.com/).
* ✅ Provider for [**imgix.com** `service`](https://imgix.com/).
* ✅ Provider for [**cloudinary.com** `service`](https://cloudinary.com/).
* ✅ Provider for [**cloudimage.io** `service`](https://www.cloudimage.io/).
* ✅ Provider for [**gumlet.com** `service`](https://www.gumlet.com/).
* Release.

Version **2.0.0** 🏷️ `planning`

* Support for gravity configuration via `focusArea` operations.
* Support for manual and smart gravity configuration.
* Provider for [**glide** `library`](https://glide.thephpleague.com/).
* Provider for [**imaginary** `library`](https://github.com/h2non/imaginary).
* Provider for [**imageflow** `library`](https://www.imageflow.io/).
* Provider for [**weserv** `library`](https://images.weserv.nl/).
* Provider for [**fastly.com** `service`](https://fastly.com/).
* Provider for [**shortpixel.com** `service`](https://shortpixel.com/).
* Provider for [**imagify.io** `service`](https://imagify.io/).
* Providers per site.
* Send HEAD requests to speed up image generation.

Version **3.0.0** 🏷️ `researching`

* Integration of more image processing libraries/services.
* Integration of video processing libraries/services.

---

[`somehow.digital`](https://somehow.digital/)
