# heleket.com Laravel

The free Laravel package to help you integrate payment with heleket.com

## Use Cases

- Create a payment link with heleket.com
- Parse result from heleket.com
- Example webhook

## Features

- Dynamic heleket.com credentials from config/heleket.php
- Easy to create payment link with a simple line code

## Requirements

- **PHP**: 8.1 or higher
- **Laravel** 9.0 or higher

## Quick Start

If you prefer to install this package into your own Laravel application, please follow the installation steps below

## Installation

#### Step 1. Install a Laravel project if you don't have one already

https://laravel.com/docs/installation

#### Step 2. Require the current package using composer:

```bash
composer require parsecvpn/heleket-laravel-integrate
```

#### Step 3. Publish the controller file and config file

```bash
php artisan vendor:publish --provider="Parsecvpn\Heleket\HeleketServiceProvider" --tag="heleket"
```

If publishing files fails, please create corresponding files at the path `config/heleket.php` and `app\Http\Controllers\HeleketControllers.php` from this package. And you can also further customize the HeleketControllers.php file to suit your project.

#### Step 4. Update the various config settings in the published config file:

After publishing the package assets a configuration file will be located at <code>config/heleket.php</code>. Please contact heleket.com to get those values to fill into the config file.

#### Step 5. Add middleware protection:

###### app/Http/Kernel.php

```php
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // Other kernel properties...
    
    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $routeMiddleware = [
        // Other middlewares...
         'heleket' => 'App\Http\Middleware\HeleketMiddleware',
    ];
}
```

#### Step 6. Add route:

###### routes/api.php

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HeleketController;

// Other routes properties...

Route::group(['middleware' => ['heleket']], function () {
    Route::post('/heleket/webhook', [HeleketController::class, 'webhook']);
});

}
```

Then your IPN (Webhook) URL will be something like https://yourdomain.ltd/api/heleket/webhook, and you should provide it to Heleket's account setting. You could provide it to `routes/web.php` if you want but remember that Heleket will check for referer matched with the pre-registration URL. So make sure that you provide them the right URL of website.

<!--- ## Usage --->

## Testing

``` php
<?php

namespace App\Console\Commands;

use Parsecvpn\Heleket\HeleketSdk;
use Illuminate\Console\Command;

class HeleketTestCommand extends Command
{
    protected $signature = 'heleket:test';

    protected $description = 'Test Heleket SDK';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $instance = new HeleketSdk();
        echo $instance->create_payment(
            'INV-test-01',
            100,
            'USDT',
            'BSC',
            'INV-test-01',
            'https://yourdomain.ltd/invoices/INV-test-01',
            'https://yourdomain.ltd/invoices/INV-test-01',
            'https://yourdomain.ltd/invoices/INV-test-01?success=true' // Remember that param success=true or any similar is just for toast notification, do not put any logical process here
        );
    }
}
```

## Feedback

Respect us in the [Laravel Việt Nam](https://www.facebook.com/groups/167363136987053)

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email contact@funnydev.vn or use the issue tracker.

## Credits

- [Funny Dev., Jsc](https://github.com/funnydevjsc)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
