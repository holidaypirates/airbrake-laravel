<?php namespace HolidayPirates\AirbrakeLaravel;

use Illuminate\Support;
use Exception;
use Airbrake;

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
        $this->package('holidaypirates/airbrake-laravel', 'airbrake-laravel', realpath(__DIR__));

        $app = $this->app;

        if ($this->isEnabled())
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
                    'filters' => $app['config']->get('airbrake-laravel::config.ignore_exceptions')
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

    /**
     * Check if exceptions should be sent to Airbrake
     *
     * @return bool
     */
    protected function isEnabled()
    {
        $enabled = $this->app['config']->get('airbrake-laravel::config.enabled', false);
        $ignoredEnvironments = $this->app['config']->get('airbrake-laravel::config.ignore_environments', []);

        return  $enabled and !in_array($this->app->environment(), $ignoredEnvironments);
    }
}
