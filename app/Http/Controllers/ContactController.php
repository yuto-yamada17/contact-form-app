<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Contact;
use App\Http\Requests\ExportContactRequest;

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

        // タグIDを取得
        $tagIds = $validated['tag_ids'] ?? [];

        unset($validated['tag_ids']);

        $contact = Contact::create($validated);

        $contact->tags()->sync($tagIds);

        return redirect()->route('contacts.complete');
    }

    // サンクス画面の表示
    public function complete()
    {
        return view('contact.thanks');
    }


    // お問い合わせをCSVでエクスポート
    public function export(ExportContactRequest $request)
    {
        $validated = $request->validated();

        $contacts = Contact::with('category')
            ->when(
                $validated['keyword'] ?? null,
                fn($q, $keyword) =>
                $q->where(function ($q) use ($keyword) {
                    $q->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                })
            )
            ->when(
                isset($validated['gender']),
                fn($q) =>
                $q->where('gender', $validated['gender'])
            )
            ->when(
                $validated['category_id'] ?? null,
                fn($q) =>
                $q->where('category_id', $validated['category_id'])
            )
            ->when(
                $validated['date'] ?? null,
                fn($q) =>
                $q->whereDate('created_at', $validated['date'])
            )
            ->orderByDesc('created_at')
            ->get();

        return response()->streamDownload(function () use ($contacts) {
            $file = fopen('php://output', 'w');

            // BOMを付与
            fwrite($file, "\xEF\xBB\xBF");

            // ヘッダー行
            fputcsv($file, [
                'ID',
                '氏名',
                '性別',
                'メール',
                '電話',
                '住所',
                '建物',
                'カテゴリ',
                '内容',
                '作成日時',
            ]);

            // データ行
            foreach ($contacts as $contact) {
                fputcsv($file, [
                    $contact->id,
                    $contact->last_name . ' ' . $contact->first_name,
                    [0 => '', 1 => '男性', 2 => '女性', 3 => 'その他'][$contact->gender] ?? '',
                    $contact->email,
                    $contact->tel,
                    $contact->address,
                    $contact->building,
                    $contact->category?->content ?? '',
                    $contact->detail,
                    $contact->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        }, 'contacts.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
