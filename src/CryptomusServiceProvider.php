<?php

namespace FunnyDev\Cryptomus;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

class CryptomusServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/cryptomus.php' => config_path('cryptomus.php'),
            __DIR__ . '/../app/Http/Controllers/CryptomusController.php' => app_path('Http/Controllers/CryptomusController.php'),
            __DIR__ . '/../app/Http/Middleware/CryptomusMiddleware.php' => app_path('Http/Middleware/CryptomusMiddleware.php'),
        ], 'cryptomus');

        try {
            if (!file_exists(config_path('cryptomus.php'))) {
                $this->commands([
                    \Illuminate\Foundation\Console\VendorPublishCommand::class,
                ]);

                Artisan::call('vendor:publish', ['--provider' => 'FunnyDev\\Cryptomus\\CryptomusServiceProvider', '--tag' => ['cryptomus']]);
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
            __DIR__ . '/../config/cryptomus.php', 'cryptomus'
        );
        $this->app->singleton(\FunnyDev\Cryptomus\CryptomusSdk::class, function ($app) {
            $merchant_uuid = $app['config']['cryptomus.merchant_uuid'];
            $payment_key = $app['config']['cryptomus.payment_key'];
            $payout_key = $app['config']['cryptomus.payout_key'];
            return new \FunnyDev\Cryptomus\CryptomusSdk($merchant_uuid, $payment_key, $payout_key);
        });
    }
}
