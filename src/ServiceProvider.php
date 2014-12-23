<?php namespace HolidayPirates\AirbrakeLaravel;

use Illuminate\Support;
use Exception;

class ServiceProvider extends Support\ServiceProvider {
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('holidaypirates/airbrake-laravel');

        $app = $this->app;

        if ($app['config']->get('airbrake-laravel::config.enabled' === true))
        {
            // Register for exception handling
            $app->error(
                function (Exception $exception) use ($app)
                {
                    $app['airbrake']->notifyOnException($exception);
                }
            );

            // Register for fatal error handling
            $app->fatal(
                function ($exception) use ($app)
                {
                    $app['airbrake']->notifyOnException($exception);
                }
            );
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            'airbrake',
            function ($app)
            {
                $options = [
                    'async' => $app['config']->get('airbrake-laravel::config.async'),
                    'environmentName' => $app->environment(),
                    'projectRoot' => base_path(),
                    'url' => $app['request']->url(),
                    'filters' => $app['config']->get('airbrake-laravel::config.ignore')
                ];

                $config = new Airbrake\Configuration(
                    $app['config']->get('airbrake-laravel::config.api_key'), $options
                );

                return new Airbrake\Client($config);
            }
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['airbrake'];
    }
}
