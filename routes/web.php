<?php

use App\Http\Controllers\PlannerController;
use App\Http\Middleware\EnsureRole;
use App\Models\Client;
use App\Models\Content;
use Illuminate\Support\Facades\Route;

Route::get('/login',[PlannerController::class,'loginForm'])->name('login');
Route::post('/login',[PlannerController::class,'login'])->middleware('throttle:5,1')->name('login.store');
Route::get('/p/{client}/{token}',[PlannerController::class,'share'])->name('share');

Route::middleware('auth')->group(function () {
    Route::post('/logout',[PlannerController::class,'logout'])->name('logout');
    Route::get('/panduan',[PlannerController::class,'guide'])->name('guide');
    Route::get('/',[PlannerController::class,'dashboard'])->name('dashboard');
    Route::get('/clients/create',[PlannerController::class,'clientForm'])->middleware(EnsureRole::class.':pm')->name('clients.create');
    Route::post('/clients',[PlannerController::class,'saveClient'])->middleware(EnsureRole::class.':pm')->name('clients.store');
    Route::get('/clients/{client}/edit',[PlannerController::class,'clientForm'])->middleware(EnsureRole::class.':pm')->name('clients.edit');
    Route::put('/clients/{client}',[PlannerController::class,'saveClient'])->middleware(EnsureRole::class.':pm')->name('clients.update');
    Route::delete('/clients/{client}',[PlannerController::class,'deleteClient'])->middleware(EnsureRole::class.':pm')->name('clients.destroy');
    Route::put('/clients/{client}/team',[PlannerController::class,'saveClientTeam'])->middleware(EnsureRole::class.':pm')->name('clients.team');
    Route::put('/clients/{client}/info',[PlannerController::class,'saveInfo'])->middleware(EnsureRole::class.':pm,team')->name('clients.info');
    Route::get('/contents/create',[PlannerController::class,'contentForm'])->middleware(EnsureRole::class.':pm,team')->name('contents.create');
    Route::post('/contents',[PlannerController::class,'saveContent'])->middleware(EnsureRole::class.':pm,team')->name('contents.store');
    Route::get('/contents/{content}',[PlannerController::class,'showContent'])->name('contents.show');
    Route::get('/contents/{content}/edit',[PlannerController::class,'contentForm'])->middleware(EnsureRole::class.':pm,team')->name('contents.edit');
    Route::put('/contents/{content}',[PlannerController::class,'saveContent'])->middleware(EnsureRole::class.':pm,team')->name('contents.update');
    Route::delete('/contents/{content}',[PlannerController::class,'deleteContent'])->middleware(EnsureRole::class.':pm,team')->name('contents.destroy');
    Route::post('/contents/{content}/review',[PlannerController::class,'review'])->name('contents.review');
    Route::get('/users',[PlannerController::class,'users'])->middleware(EnsureRole::class.':pm')->name('users.index');
    Route::post('/users',[PlannerController::class,'saveUser'])->middleware(EnsureRole::class.':pm')->name('users.store');
    Route::put('/users/{user}',[PlannerController::class,'saveUser'])->middleware(EnsureRole::class.':pm')->name('users.update');
    Route::delete('/users/{user}',[PlannerController::class,'deleteUser'])->middleware(EnsureRole::class.':pm')->name('users.destroy');
});
