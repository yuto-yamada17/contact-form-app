<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;

class ContactController extends Controller
{
    public function index()
    {

        // カテゴリー一覧を取得
        $categories = Category::select('id', 'content')->get();

        // タグ一覧を取得
        $tags = Tag::select('id', 'name')->get();

        // お問い合わせ入力画面を表示
        return view('contact.index', compact(
            'categories',
            'tags'
        ));
    }

    //確認画面の表示
    public function confirm(StoreContactRequest $request)
    {
        $validated = $request->validated();

        $category = Category::find($validated['category_id']);

        $tags = Tag::whereIn('id', $validated['tag_ids'] ?? [])->get();

        return view('contact.confirm', compact(
            'validated',
            'category',
            'tags'
        ));
    }
}
