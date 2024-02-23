<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\BookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('login', [AuthController::class, 'login'])->name('login');

Route::get('authors', [AuthorController::class, 'index'])->name('authors.index');
Route::get('authors/view/{id}', [AuthorController::class, 'view'])->name('authors.view');

Route::get('genres', [GenreController::class, 'index'])->name('genres.index');
Route::get('genres/view/{id}', [GenreController::class, 'view'])->name('genres.view');

Route::get('books', [BookController::class, 'index'])->name('books.index');
Route::get('books/view/{id}', [BookController::class, 'view'])->name('books.view');

Route::group(['middleware' => 'auth:sanctum'], function(){

    Route::get('auth/user', [AuthController::class, 'getUser'])->name('auth.user');

    Route::get('books/get', [BookController::class, 'index'])->name('books.index');
    //authors
    Route::post('authors/store', [AuthorController::class, 'store'])->name('authors.store');
    Route::post('authors/update', [AuthorController::class, 'update'])->name('authors.update');
    Route::post('authors/delete', [AuthorController::class, 'delete'])->name('authors.delete');

    //genres
    Route::post('genres/store', [GenreController::class, 'store'])->name('genres.store');
    Route::post('genres/update', [GenreController::class, 'update'])->name('genres.update');
    Route::post('genres/delete', [GenreController::class, 'delete'])->name('genres.delete');

    //books
    Route::post('books/store', [BookController::class, 'store'])->name('books.store');
    Route::post('books/update', [BookController::class, 'update'])->name('books.update');
    Route::post('books/delete', [BookController::class, 'delete'])->name('books.delete');
});
