<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TagController;

// お問い合わせ入力画面
Route::get('/', [ContactController::class, 'index'])
    ->name('contacts.index');

// 確認ページ
Route::post('/contacts/confirm', [ContactController::class, 'confirm'])
    ->name('contacts.confirm');

// お問い合わせ送信処理
Route::post('/contacts', [ContactController::class, 'store'])
    ->name('contacts.store');

// サンクス画面
Route::get('/thanks', [ContactController::class, 'complete'])
    ->name('contacts.complete');

// 管理画面
Route::middleware('auth')->group(function () {

    Route::get('/admin', [AdminController::class, 'index'])
        ->name('admin.index');

    // お問い合わせCSVエクスポート
    Route::get('/contacts/export', [ContactController::class, 'export'])
        ->name('contacts.export');
});

// お問い合わせ詳細
Route::get('/admin/contacts/{contact}', [AdminController::class, 'show'])
    ->name('admin.show');

// タグ追加
Route::post('/admin/tags', [TagController::class, 'store'])
    ->name('admin.tags.store');

// タグ編集画面
Route::get('/admin/tags/{tag}/edit', [TagController::class, 'edit'])
    ->name('admin.tags.edit');

// タグ更新
Route::put('/admin/tags/{tag}', [TagController::class, 'update'])
    ->name('admin.tags.update');

// タグ削除
Route::delete('/admin/tags/{tag}', [TagController::class, 'destroy'])
    ->name('admin.tags.destroy');

//お問い合わせ詳細削除
Route::delete('/admin/contacts/{contact}', [AdminController::class, 'destroy'])
    ->name('admin.contacts.destroy');
