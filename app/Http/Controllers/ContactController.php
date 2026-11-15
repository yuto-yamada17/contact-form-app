<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Contact;

class ContactController extends Controller
{
    //お問い合わせフォーム入力画面の表示
    public function index()
    {
        $categories = Category::select('id', 'content')->get();

        $tags = Tag::select('id', 'name')->get();

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

    // お問い合わせ内容を保存
    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();

        unset($validated['tag_ids']);

        Contact::create($validated);

        return redirect()->route('contacts.complete');
    }

    // サンクス画面の表示
    public function complete()
    {
        return view('contact.thanks');
    }
}
