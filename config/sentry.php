<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sentry DSN
    |--------------------------------------------------------------------------
    |
    | The DSN tells the SDK where to send the events to. If this value is not
    | provided, the SDK will try to read it from the SENTRY_LARAVEL_DSN
    | environment variable. If that variable also does not exist, the SDK
    | will not send any events.
    |
    */

    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    /*
    |--------------------------------------------------------------------------
    | Sentry Environment
    |--------------------------------------------------------------------------
    |
    | The environment your application is running in. This value is used to
    | categorize events in Sentry.
    |
    */

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Sentry Release
    |--------------------------------------------------------------------------
    |
    | The release version of your application. This value is used to track
    | which version of your application an error occurred in.
    |
    */

    'release' => env('SENTRY_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Sentry Traces Sample Rate
    |--------------------------------------------------------------------------
    |
    | The sample rate for performance monitoring. This value should be between
    | 0.0 and 1.0. A value of 1.0 means 100% of transactions are sent to Sentry.
    | A value of 0.1 means 10% of transactions are sent.
    |
    */

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.0),

    /*
    |--------------------------------------------------------------------------
    | Sentry Profiles Sample Rate
    |--------------------------------------------------------------------------
    |
    | The sample rate for profiling. This value should be between 0.0 and 1.0.
    | A value of 1.0 means 100% of transactions are profiled.
    | A value of 0.1 means 10% of transactions are profiled.
    |
    */

    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    /*
    |--------------------------------------------------------------------------
    | Sentry Before Send Callback
    |--------------------------------------------------------------------------
    |
    | A callback that is invoked before an event is sent to Sentry. This
    | callback can be used to modify or filter events before they are sent.
    | Set to null to disable. Use a closure or callable if needed.
    |
    */

    // 'before_send' => null, // Removido - não pode ser null, apenas omitir se não usar

    /*
    |--------------------------------------------------------------------------
    | Sentry Ignore Exceptions
    |--------------------------------------------------------------------------
    |
    | A list of exception classes that should not be sent to Sentry.
    |
    */

    'ignore_exceptions' => [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Illuminate\Auth\AuthenticationException::class,
    ],


    /*
    |--------------------------------------------------------------------------
    | Sentry Send Default PII
    |--------------------------------------------------------------------------
    |
    | Whether to send default PII (Personally Identifiable Information) like
    | user ID, username, email, etc. Set to false to disable.
    |
    */

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    /*
    |--------------------------------------------------------------------------
    | Sentry Max Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | The maximum number of breadcrumbs to store. Default is 50.
    |
    */

    'max_breadcrumbs' => (int) env('SENTRY_MAX_BREADCRUMBS', 50),

];

