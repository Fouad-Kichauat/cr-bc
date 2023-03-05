<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DatabaseController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Inertia\Inertia;
use App\Models\User;

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

Route::get('/', function () {
    return Inertia::render('Dashboard', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->middleware(['auth', 'verified']);

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/decode/{id}', [DatabaseController::class, 'test'])->middleware(['auth', 'verified']);

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

Route::get('/auth/redirect', function () {
    return Socialite::driver('okta')->redirect();
});

Route::get('/auth/okta/callback', function () {
    $user = Socialite::driver('okta')->user();
    $localUser = User::updateOrCreate(['email' => $user->email], [
        'email'         => $user->email,
        'name'          => $user->name,
        'token'         => $user->token,
        'refresh_token' => $user->refreshToken,
    ]);

    try {
        Auth::login($localUser);
    }
    catch (\Throwable $e) {
        return Inertia::location('/login');
    }

    return Inertia::location('/');
});

require __DIR__.'/auth.php';
