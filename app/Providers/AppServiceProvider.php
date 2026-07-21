<?php

namespace App\Providers;

use App\Interfaces\ISellerPriceable;
use App\Services\AI\AIService;
use App\Services\AI\Provider\ClaudeProvider;
use App\Services\AI\Provider\OpenAIProvider;
use App\Services\AI\Provider\YandexProvider;
use App\Services\SellerPriceHttpService;
use App\Services\SellerPriceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Jenssegers\Date\Date;
use URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public $bindings = [
        ISellerPriceable::class => SellerPriceHttpService::class, //SellerPriceService::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // AI-провайдеры: ключи из config/services.php, HTTP берётся фасадом Http:: внутри
        $this->app->singleton(ClaudeProvider::class, fn () => new ClaudeProvider(
            config('services.ai.claude.key', ''),
            config('services.ai.claude.proxy'),
        ));

        $this->app->singleton(OpenAIProvider::class, fn () => new OpenAIProvider(
            config('services.ai.openai.key', ''),
            config('services.ai.openai.proxy'),
        ));

        $this->app->singleton(YandexProvider::class, fn () => new YandexProvider(
            config('services.ai.yandex.key', ''),
            config('services.ai.yandex.folder', ''),
        ));

        // Тег → iterable провайдеров для AIService
        $this->app->tag([
            ClaudeProvider::class,
            OpenAIProvider::class,
            YandexProvider::class,
        ], 'ai.providers');

        $this->app->singleton(AIService::class, fn ($app) => new AIService($app->tagged('ai.providers')));
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // For MariaDB
        Schema::defaultStringLength(191);
        // hhtps on prod
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }
        // locale
        setlocale(LC_ALL, 'ru_RU.utf8');
        Carbon::setLocale(config('app.locale'));
        Date::setLocale('ru');
    }
}
