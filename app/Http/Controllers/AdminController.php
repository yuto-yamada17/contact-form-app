<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Contact;
use App\Models\Category;
use App\Models\Tag;

class AdminController extends Controller
{
    // 管理画面を表示・検索
    public function index(IndexContactRequest $request)
    {
        // 管理画面で使用するデータを取得
        $categories = Category::all();
        $tags = Tag::all();

        $query = Contact::with(['category', 'tags']);

        // 検索処理
        $validated = $request->validated();

        // 名前・メールアドレスで部分一致検索
        if (!empty($validated['keyword'])) {
            $keyword = $validated['keyword'];

            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        // 性別で検索
        if (isset($validated['gender']) && $validated['gender'] != 0) {
            $query->where('gender', $validated['gender']);
        }

        // お問い合わせ種類で検索
        if (!empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        // 日付で検索
        if (!empty($validated['date'])) {
            $query->whereDate('created_at', $validated['date']);
        }

        // 7件ずつ表示
        $contacts = $query->paginate(7)->withQueryString();

        return view('admin.index', compact(
            'categories',
            'tags',
            'contacts'
        ));
    }

    // お問い合わせ詳細を表示
    public function show(Contact $contact)
    {
        $contact->load(['category', 'tags']);

        return view('admin.show', compact('contact'));
    }

    // お問い合わせを削除
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect('/admin');
    }
}
