<?php

namespace Tests\Unit;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\IndexContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Requests\Api\V1\IndexContactRequest as ApiIndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest as ApiStoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * FormRequestのバリデーションルールを使用してテストを実行する。
     */
    private function validateRequest(string $requestClass, array $data, $tag = null)
    {
        $request = new $requestClass();

        // タグ更新時は、ルートパラメータから更新対象のタグを取得できるようにする。
        if ($tag !== null) {
            $route = new class($tag) {
                public function __construct(private $tag) {}

                public function parameter($key, $default = null)
                {
                    return $key === 'tag' ? $this->tag : $default;
                }
            };

            $request->setRouteResolver(fn() => $route);
        }

        return Validator::make(
            $data,
            $request->rules(),
            $request->messages()
        );
    }

    //正しいフィルタ条件を受け付け、不正な性別や存在しないカテゴリIDを拒否すること。
    public function test_csv_export_validation(): void
    {
        $category = Category::create(['content' => '商品のお届けについて']);

        // 正しいフィルタ条件を受け付けること。
        $validator = $this->validateRequest(ExportContactRequest::class, [
            'keyword' => '山田',
            'gender' => 0,
            'category_id' => $category->id,
            'date' => '2026-11-17',
        ]);
        $this->assertTrue($validator->passes());

        // 不正な性別や存在しないカテゴリIDを拒否すること。
        $validator = $this->validateRequest(ExportContactRequest::class, [
            'gender' => 4,
            'category_id' => 999999,
        ]);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    //キーワード・性別・カテゴリ・日付フィルタが有効であり、不正な性別値を拒否すること。
    public function test_contact_search_validation(): void
    {
        $category = Category::create(['content' => '商品のお届けについて']);

        // キーワード・性別・カテゴリ・日付フィルタが有効であること。
        $validator = $this->validateRequest(IndexContactRequest::class, [
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-11-17',
        ]);
        $this->assertTrue($validator->passes());

        // 不正な性別値を拒否すること。
        $validator = $this->validateRequest(IndexContactRequest::class, [
            'gender' => 4,
        ]);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    //全ての必須項目とタグ入力を受け付けること。不正な電話番号形式は拒否すること。
    public function test_contact_store_validation(): void
    {
        $category = Category::create(['content' => '商品のお届けについて']);
        $tag = Tag::create(['name' => '重要']);

        // 全ての必須項目とタグ入力を受け付けること。
        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都千代田区',
            'building' => 'テストビル',
            'category_id' => $category->id,
            'detail' => '商品について確認したいです。',
            'tag_ids' => [$tag->id],
        ];
        $validator = $this->validateRequest(StoreContactRequest::class, $data);
        $this->assertTrue($validator->passes());

        // 不正な電話番号形式は拒否すること。
        $data['tel'] = '090-1234-5678';
        $validator = $this->validateRequest(StoreContactRequest::class, $data);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('tel', $validator->errors()->toArray());
    }

    //タグ名の必須入力、文字数制限、一意性（重複禁止）が維持されていること。
    public function test_tag_store_validation(): void
    {
        // タグ名の必須入力を確認すること。
        $validator = $this->validateRequest(StoreTagRequest::class, []);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());

        // タグ名の文字数制限を確認すること。
        $validator = $this->validateRequest(StoreTagRequest::class, [
            'name' => str_repeat('あ', 51),
        ]);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());

        // タグ名の一意性（重複禁止）が維持されていること。
        Tag::create(['name' => '重要']);
        $validator = $this->validateRequest(StoreTagRequest::class, [
            'name' => '重要',
        ]);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    //自身の名前維持は可能だが、他で既に使用されているタグ名への変更は拒否すること。
    public function test_tag_update_validation(): void
    {
        $tag = Tag::create(['name' => '重要']);
        Tag::create(['name' => '確認済み']);

        // 自身の名前維持は可能であること。
        $validator = $this->validateRequest(
            UpdateTagRequest::class,
            ['name' => '重要'],
            $tag
        );
        $this->assertTrue($validator->passes());

        // 他で既に使用されているタグ名への変更は拒否すること。
        $validator = $this->validateRequest(
            UpdateTagRequest::class,
            ['name' => '確認済み'],
            $tag
        );
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    //API用IndexContactRequestのキーワード・性別(in:1,2,3)・カテゴリ・日付・per_pageフィルタが有効であり、不正な値を拒否すること。
    public function test_api_contact_search_validation(): void
    {
        $category = Category::create(['content' => '商品のお届けについて']);

        // キーワード・性別(in:1,2,3)・カテゴリ・日付・per_pageフィルタが有効であること。
        $validator = $this->validateRequest(ApiIndexContactRequest::class, [
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-11-17',
            'per_page' => 20,
        ]);
        $this->assertTrue($validator->passes());

        // 不正な性別・存在しないカテゴリ・範囲外のper_pageを拒否すること。
        $validator = $this->validateRequest(ApiIndexContactRequest::class, [
            'gender' => 4,
            'category_id' => 999999,
            'per_page' => 101,
        ]);
        $this->assertFalse($validator->passes());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('gender', $errors);
        $this->assertArrayHasKey('category_id', $errors);
        $this->assertArrayHasKey('per_page', $errors);
    }

    //API用StoreContactRequestの全必須項目・タグ入力を受け付け、不正な値を拒否すること。
    public function test_api_contact_store_validation(): void
    {
        $category = Category::create(['content' => '商品のお届けについて']);
        $tag = Tag::create(['name' => '重要']);

        // 全必須項目・タグ入力を受け付けること。
        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都千代田区',
            'building' => 'テストビル',
            'category_id' => $category->id,
            'detail' => '商品について確認したいです。',
            'tag_ids' => [$tag->id],
        ];
        $validator = $this->validateRequest(ApiStoreContactRequest::class, $data);
        $this->assertTrue($validator->passes());

        // 不正な値（電話番号形式・タグID）を拒否すること。
        $data['tel'] = '090-1234-5678';
        $data['tag_ids'] = [999999];
        $validator = $this->validateRequest(ApiStoreContactRequest::class, $data);
        $this->assertFalse($validator->passes());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('tel', $errors);
        $this->assertArrayHasKey('tag_ids.0', $errors);
    }
}
