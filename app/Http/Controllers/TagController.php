<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Models\Tag;

class TagController extends Controller
{
    // タグを追加
    public function store(StoreTagRequest $request)
    {
        $validated = $request->validated();

        Tag::create($validated);

        return redirect()->route('admin.index');
    }

    // タグ編集画面を表示
    public function edit(Tag $tag)
    {
        return view('admin.tags.edit', compact('tag'));
    }

    // タグ更新処理
    public function update(StoreTagRequest $request, Tag $tag)
    {
        $validated = $request->validated();

        $tag->update($validated);

        return redirect('/admin');
    }

    // タグ削除
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect('/admin');
    }
}
