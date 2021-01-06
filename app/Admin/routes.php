<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {
    // 首页
    $router->get('/', 'HomeController@index')->name('home');
    // 用户列表
    $router->get('users', 'UserController@index');
    // 商品列表
    $router->get('products', 'ProductsController@index');
});
