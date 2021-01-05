<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\UserAddressesController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes(['verify' => true]);
// 首页
Route::get('/', [PagesController::class, 'index'])->name('index');
// auth 中间件代表需要登录，verified中间件代表需要经过邮箱验证
Route::group(['middleware' => ['auth', 'verified']], function() {
    // 收货地址列表
    Route::get('user_addresses', [UserAddressesController::class, 'index'])->name('user_addresses.index');
    // 新建收货地址
    Route::get('user_addresses/create', [UserAddressesController::class, 'create'])->name('user_addresses.create');
    // 保存收货地址
    Route::post('user_addresses', [UserAddressesController::class, 'store'])->name('user_addresses.store');
});
