<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\PrediksiController;
use App\Http\Controllers\PenjualanController;



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
    return view('welcome');
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home')->middleware('auth');

Auth::routes();


// Rute untuk Kelola Barang
Route::get('/kelola-barang', [BarangController::class, 'index'])->name('kelola-barang');
Route::get('/kelola-barang/tambah', [BarangController::class, 'create'])->name('kelola-barang.create');
Route::post('/kelola-barang/simpan', [BarangController::class, 'store'])->name('kelola-barang.store');
Route::get('/kelola-barang/edit/{id}', [BarangController::class, 'edit'])->name('kelola-barang.edit');
Route::post('/kelola-barang/update/{id}', [BarangController::class, 'update'])->name('kelola-barang.update');
Route::delete('/kelola-barang/hapus/{id}', [BarangController::class, 'destroy'])->name('kelola-barang.destroy');

// Rute untuk Penjualan
Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan');
Route::get('/penjualan/tambah', [PenjualanController::class, 'create'])->name('penjualan.create');
Route::post('/penjualan/simpan', [PenjualanController::class, 'store'])->name('penjualan.store');
Route::get('/penjualan/edit/{id}', [PenjualanController::class, 'edit'])->name('penjualan.edit');
Route::post('/penjualan/update/{id}', [PenjualanController::class, 'update'])->name('penjualan.update');
Route::delete('/penjualan/hapus/{id}', [PenjualanController::class, 'destroy'])->name('penjualan.destroy');

Route::get('/prediksi', [PrediksiController::class, 'prediksi'])->name('prediksi');


Route::get('/kelola-barang/tambah', [BarangController::class, 'create'])->name('kelola-barang.create');
Route::post('/kelola-barang/simpan', [BarangController::class, 'store'])->name('kelola-barang.store');

Route::post('/barang/import', [BarangController::class, 'import'])->name('barang.import');
Route::post('/kelola-barang/truncate', [BarangController::class, 'truncate'])->name('kelola-barang.truncate');

Route::post('/penjualan/import', [PenjualanController::class, 'import'])->name('penjualan.import');
