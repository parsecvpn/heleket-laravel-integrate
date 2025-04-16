<?php

namespace Parsecvpn\Heleket;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

class HeleketServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/heleket.php' => config_path('heleket.php'),
            __DIR__ . '/../app/Http/Controllers/HeleketController.php' => app_path('Http/Controllers/HeleketController.php'),
            __DIR__ . '/../app/Http/Middleware/HeleketMiddleware.php' => app_path('Http/Middleware/HeleketMiddleware.php'),
        ], 'heleket');

        try {
            if (!file_exists(config_path('heleket.php'))) {
                $this->commands([
                    \Illuminate\Foundation\Console\VendorPublishCommand::class,
                ]);

                Artisan::call('vendor:publish', ['--provider' => 'Parsecvpn\\Heleket\\HeleketServiceProvider', '--tag' => ['heleket']]);
            }
        } catch (\Exception) {}
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/heleket.php', 'heleket'
        );
        $this->app->singleton(\Parsecvpn\Heleket\HeleketSdk::class, function ($app) {
            $merchant_uuid = $app['config']['heleket.merchant_uuid'];
            $payment_key = $app['config']['heleket.payment_key'];
            $payout_key = $app['config']['heleket.payout_key'];
            return new \Parsecvpn\Heleket\HeleketSdk($merchant_uuid, $payment_key, $payout_key);
        });
    }
}
