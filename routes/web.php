<?php

use Illuminate\Support\Facades\Route;
use App\Models\Category;
use App\Models\Contact;
use App\Http\Controllers\ContactController;

// お問い合わせ入力画面
Route::get('/', [ContactController::class, 'index'])
    ->name('contacts.index');

// 確認ページ
Route::post('/contacts/confirm', [ContactController::class, 'confirm'])
    ->name('contact.confirm');

// 管理画面
Route::middleware('auth')->group(function () {

    Route::get('/admin', function () {

        // お問い合わせ種類を取得
        $categories = Category::all();

        // お問い合わせ一覧を取得
        $contacts = Contact::paginate(10);

        // 管理画面にデータを渡す
        return view('admin.index', compact(
            'categories',
            'contacts'
        ));
    })->name('admin.index');
});
