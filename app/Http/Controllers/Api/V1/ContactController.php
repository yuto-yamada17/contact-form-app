<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\Resources\Api\V1\ContactResource;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    // お問い合わせ一覧を取得
    public function index(IndexContactRequest $request)
    {
        $validated = $request->validated();

        $query = Contact::with(['category', 'tags']);

        // キーワード検索
        if (!empty($validated['keyword'])) {
            $keyword = $validated['keyword'];

            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        // 性別で絞り込み
        if (!empty($validated['gender'])) {
            $query->where('gender', $validated['gender']);
        }

        // お問い合わせ種類で絞り込み
        if (!empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        // 日付で絞り込み
        if (!empty($validated['date'])) {
            $query->whereDate('created_at', $validated['date']);
        }

        // 一覧を取得
        $contacts = $query->latest()->paginate(
            $validated['per_page'] ?? 20
        );

        return response()->json([
            'data' => ContactResource::collection($contacts),
            'meta' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ]);
    }

    // お問い合わせ詳細を取得
    public function show(Contact $contact)
    {
        return new ContactResource(
            $contact->load(['category', 'tags'])
        );
    }

    // お問い合わせを新規作成
    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();

        $contact = DB::transaction(function () use ($validated) {
            $tagIds = $validated['tag_ids'] ?? [];

            unset($validated['tag_ids']);

            $contact = Contact::create($validated);

            $contact->tags()->attach($tagIds);

            return $contact->load(['category', 'tags']);
        });

        return (new ContactResource($contact))
            ->response()
            ->setStatusCode(201);
    }

    // お問い合わせを更新
    public function update(
        UpdateContactRequest $request,
        Contact $contact
    ) {
        $validated = $request->validated();

        if (isset($validated['tag_ids'])) {
            $contact->tags()->sync($validated['tag_ids']);
        }

        unset($validated['tag_ids']);

        $contact->update($validated);

        return new ContactResource(
            $contact->load(['category', 'tags'])
        );
    }

    // お問い合わせを削除
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->noContent();
    }
}
