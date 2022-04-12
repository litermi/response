# Response

[![Software License][ico-license]](LICENSE.md)

## About

The `Response` is a package to response api-rest .

##### [Tutorial how create composer package](https://cirelramos.blogspot.com/2022/04/how-create-composer-package.html)

## Installation

Require the `cirelramos/response` package in your `composer.json` and update your dependencies:
```sh
composer require cirelramos/response
```


## Configuration

set provider

```php
'providers' => [
    // ...
    Cirelramos\Response\Providers\ServiceProvider::class,
],
```


The defaults are set in `config/response.php`. Publish the config to copy the file to your own config:
```sh
php artisan vendor:publish --provider="Cirelramos\Response\Providers\ServiceProvider"
```

> **Note:** this is necessary to you can change default config



## Usage

```php
use Cirelramos\Response\Traits\ResponseTrait;

class Controller extends BaseController
{
    use ResponseTrait;
    //.
    //.
    //.
}
```



```php

Class ProductController extends Controller
{
    //.
    //.
    //.
    
    public function store(ProductRequest $request){
             
        $successMessage = _('OPERATION_SUCCESSFUL_MESSAGE');
        $errorMessage   = _('AN_ERROR_HAS_OCCURRED_MESSAGE');

        try {
            DB::beginTransaction();

            $product = new Product();
            $product->fill($request->validated());
            $product->save();
            DB::commit();

            $data[ 'product' ] = new ProductResource($product);

            return $this->successResponseWithMessage($data, $successMessage, Response::HTTP_CREATED);

        }
        catch(Exception $exception) {
            DB::rollBack();

            return $this->errorCatchResponse($exception, $errorMessage, Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    //.
    //.
    //.
}

```

## License

Released under the MIT License, see [LICENSE](LICENSE).


[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square

