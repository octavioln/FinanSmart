<?php

use App\Http\Livewire\Accounts;
use App\Http\Livewire\Categories;
use App\Http\Livewire\Dashboard;
use App\Http\Livewire\Subscriptions;
use App\Http\Livewire\Transactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    if (Auth::user()) {
        return redirect('/dashboard');
    }

    return view('auth.login');
})->name('/login');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
    Route::get('accounts', Accounts::class)->name('accounts');
    Route::get('categories', Categories::class)->name('categories');
    Route::get('transactions', Transactions::class)->name('transactions');
    Route::get('subscriptions', Subscriptions::class)->name('subscriptions');
});
