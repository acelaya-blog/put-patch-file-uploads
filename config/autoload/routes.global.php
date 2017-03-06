<?php
use Fig\Http\Message\RequestMethodInterface as RequestMethod;

return [
    'dependencies' => [
        'invokables' => [
            Zend\Expressive\Router\RouterInterface::class => Zend\Expressive\Router\FastRouteRouter::class,
            App\Action\UploadAction::class => App\Action\UploadAction::class,
        ],
        'factories' => [
            App\Action\HomePageAction::class => App\Action\HomePageFactory::class,
        ],
    ],

    'routes' => [
        [
            'name' => 'home',
            'path' => '/',
            'middleware' => App\Action\HomePageAction::class,
            'allowed_methods' => [RequestMethod::METHOD_GET],
        ],
        [
            'name' => 'upload',
            'path' => '/upload',
            'middleware' => App\Action\UploadAction::class,
            'allowed_methods' => [RequestMethod::METHOD_POST, RequestMethod::METHOD_PUT, RequestMethod::METHOD_PATCH],
        ],
    ],
];
