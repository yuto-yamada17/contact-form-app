<?php

use Illuminate\Support\Facades\Route;
use App\Models\Category;
use App\Models\Contact;

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

    // ログアウト後のトップページ
    Route::get('/', function () {
        return redirect('/login');
    });
});
