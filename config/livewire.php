<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Class Namespaces
    |--------------------------------------------------------------------------
    |
    | This value determines the default namespace for Livewire components
    | in your application. Feel free to customize this value.
    |
    */

    'class_namespace' => 'App\\Livewire',

    /*
    |--------------------------------------------------------------------------
    | View Path
    |--------------------------------------------------------------------------
    |
    | This value determines the default view path for Livewire components.
    |
    */

    'view_path' => resource_path('views/livewire'),

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | This value determines the default layout view used by Livewire.
    |
    */

    'layout' => 'components.layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Lazy Loading Placeholder
    |--------------------------------------------------------------------------
    |
    | Livewire allows you to lazy load components. Here you can specify the
    | default placeholder view that will be used while loading.
    |
    */

    'lazy_placeholder' => null,

    /*
    |--------------------------------------------------------------------------
    | Temporary File Uploads
    |--------------------------------------------------------------------------
    |
    | Livewire handles file uploads by storing them in a temporary directory
    | before they are stored permanently. Here you can customize that.
    |
    */

    'temporary_file_uploads' => [
        'disk' => null,        // Defaults to 'local'
        'rules' => null,       // Example: ['file', 'mimes:png,jpg', 'max:102400']
        'directory' => null,   // Defaults to 'livewire-tmp'
        'middleware' => null,  // Defaults to 'throttle:60,1'
    ],

    /*
    |--------------------------------------------------------------------------
    | Manifest File Path
    |--------------------------------------------------------------------------
    |
    | Livewire uses a manifest file to speed up component loading. Here
    | you can specify the path where that file should be stored.
    |
    */

    'manifest_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Back Button Support
    |--------------------------------------------------------------------------
    |
    | Livewire can support the browser's back button by tracking the
    | state of the page in the browser's history.
    |
    */

    'back_button_cache' => false,

    /*
    |--------------------------------------------------------------------------
    | Render On Redirect
    |--------------------------------------------------------------------------
    |
    | Livewire can render the component on the server before redirecting
    | the user to a new page.
    |
    */

    'render_on_redirect' => false,

    /*
    |--------------------------------------------------------------------------
    | Livewire Update Route
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Livewire will send its internal update 
    | requests. You can customize this for security or to avoid conflicts.
    |
    */

    'update_route' => env('LIVEWIRE_UPDATE_PATH', 'livewire/update'),

    /*
    |--------------------------------------------------------------------------
    | Livewire Script Config
    |--------------------------------------------------------------------------
    |
    | This value determines the default script configuration for Livewire.
    |
    */

    'inject_assets' => true,

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#2299dd',
    ],

    'inject_morph_markers' => true,

    'pagination_theme' => 'tailwind',

];
