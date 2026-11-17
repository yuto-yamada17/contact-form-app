<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactManagementTest extends TestCase
{
    use RefreshDatabase;

    // テスト用のカテゴリーを作成
    private function createCategory(array $attributes = []): Category
    {
        return Category::create(array_merge([
            'content' => 'テストカテゴリー',
        ], $attributes));
    }

    // テスト用のタグを作成
    private function createTag(array $attributes = []): Tag
    {
        return Tag::create(array_merge([
            'name' => 'テストタグ' . uniqid(),
        ], $attributes));
    }

    // テスト用のお問い合わせを作成
    private function createContact(array $attributes = []): Contact
    {
        return Contact::create($this->contactData($attributes));
    }

    // テスト用のお問い合わせデータを作成
    private function contactData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都千代田区',
            'building' => 'テストビル',
            'category_id' => Category::query()->value('id') ?? $this->createCategory()->id,
            'detail' => 'お問い合わせ内容です。',
        ], $overrides);
    }

    // 管理者ユーザーでログイン
    private function loginAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    // トップページが表示される
    public function test_top_page_displays_categories_and_tags(): void
    {
        $this->createCategory(['content' => '商品のお届けについて']);
        $this->createTag(['name' => 'テストタグ']);

        $this->get('/')
            ->assertOk()
            ->assertViewHas('categories')
            ->assertViewHas('tags')
            ->assertSee('商品のお届けについて')
            ->assertSee('テストタグ');
    }

    // サンクスページが表示される
    public function test_thanks_page_displays(): void
    {
        $this->get('/thanks')->assertOk();
    }

    // 未ログインの場合は管理画面にアクセスできない
    public function test_guest_is_redirected_from_admin_page(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    // ログイン済みの場合は管理画面が表示される
    public function test_authenticated_user_can_access_admin_page(): void
    {
        $this->loginAdmin();

        $this->get('/admin')->assertOk();
    }

    // 入力内容が正しい場合は確認画面が表示される
    public function test_contact_confirmation_displays_input_values(): void
    {
        $this->post('/contacts/confirm', $this->contactData())
            ->assertOk()
            ->assertViewIs('contact.confirm')
            ->assertSee('太郎')
            ->assertSee('山田')
            ->assertSee('test@example.com');
    }

    // 必須項目が不足している場合は確認画面へ進めない
    public function test_contact_confirmation_rejects_invalid_input(): void
    {
        $this->from('/')
            ->post('/contacts/confirm', [])
            ->assertRedirect('/')
            ->assertSessionHasErrors();
    }

    // お問い合わせを登録してサンクスページへ遷移する
    public function test_contact_can_be_created(): void
    {
        $category = $this->createCategory();
        $tag = $this->createTag();

        $data = $this->contactData([
            'category_id' => $category->id,
            'tag_ids' => [$tag->id],
        ]);

        $this->post('/contacts', $data)->assertRedirect('/thanks');

        $this->assertDatabaseHas('contacts', [
            'email' => 'test@example.com',
            'category_id' => $category->id,
        ]);

        $contact = Contact::where('email', 'test@example.com')->firstOrFail();

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    // 不正な入力の場合はお問い合わせを登録しない
    public function test_contact_creation_rejects_invalid_input(): void
    {
        $this->from('/')
            ->post('/contacts', [])
            ->assertRedirect('/')
            ->assertSessionHasErrors();
    }

    // 管理画面でお問い合わせを検索できる
    public function test_admin_can_search_contacts(): void
    {
        $this->loginAdmin();

        $this->createContact([
            'first_name' => '検索対象',
            'email' => 'target@example.com',
        ]);

        $this->createContact([
            'first_name' => '別の利用者',
            'email' => 'other@example.com',
        ]);

        $this->get('/admin?keyword=検索対象')
            ->assertOk()
            ->assertSee('target@example.com')
            ->assertDontSee('other@example.com');
    }

    // 管理画面のお問い合わせ一覧は7件ずつ表示する
    public function test_admin_contacts_are_paginated_by_seven(): void
    {
        $this->loginAdmin();

        for ($i = 0; $i < 8; $i++) {
            $this->createContact([
                'email' => 'page' . $i . '@example.com',
            ]);
        }

        $this->get('/admin')
            ->assertOk()
            ->assertViewHas('contacts', function ($contacts) {
                return $contacts->count() === 7;
            });
    }

    // お問い合わせ詳細を表示する
    public function test_admin_can_view_contact_detail(): void
    {
        $this->loginAdmin();

        $contact = $this->createContact();

        $this->get('/admin/contacts/' . $contact->id)
            ->assertOk()
            ->assertViewIs('admin.show')
            ->assertSee($contact->email);
    }

    // お問い合わせを削除する
    public function test_admin_can_delete_contact(): void
    {
        $this->loginAdmin();

        $contact = $this->createContact();

        $this->delete('/admin/contacts/' . $contact->id)
            ->assertRedirect('/admin');

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    // タグ編集画面を表示する
    public function test_admin_can_view_tag_edit_page(): void
    {
        $this->loginAdmin();

        $tag = $this->createTag();

        $this->get('/admin/tags/' . $tag->id . '/edit')->assertOk();
    }

    // タグを登録する
    public function test_admin_can_create_tag(): void
    {
        $this->loginAdmin();

        $this->post('/admin/tags', [
            'name' => '新しいタグ',
        ])->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'name' => '新しいタグ',
        ]);
    }

    // タグ名を更新する
    public function test_admin_can_update_tag(): void
    {
        $this->loginAdmin();

        $tag = $this->createTag(['name' => '変更前']);

        $this->put('/admin/tags/' . $tag->id, [
            'name' => '変更後',
        ])->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'name' => '変更後',
        ]);
    }

    // タグを削除する
    public function test_admin_can_delete_tag(): void
    {
        $this->loginAdmin();

        $tag = $this->createTag();

        $this->delete('/admin/tags/' . $tag->id)
            ->assertRedirect('/admin');

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    // CSV出力で検索条件を指定できる
    public function test_admin_can_export_filtered_contacts_as_csv(): void
    {
        $this->loginAdmin();

        $category = $this->createCategory();

        $this->createContact([
            'first_name' => 'CSV対象',
            'email' => 'csv-target@example.com',
            'category_id' => $category->id,
        ]);

        $response = $this->get(
            '/contacts/export?keyword=CSV対象&gender=1&category_id=' . $category->id
        );

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('csv-target@example.com', $csv);
    }

    // CSV出力では新しいお問い合わせから順に出力する

    public function test_admin_can_export_contacts_without_filters(): void
    {
        $this->loginAdmin();

        $olderContact = $this->createContact([
            'email' => 'older@example.com',
        ]);

        $olderContact->forceFill([
            'created_at' => now()->subDays(2),
        ])->saveQuietly();

        $newerContact = $this->createContact([
            'email' => 'newer@example.com',
        ]);

        $newerContact->forceFill([
            'created_at' => now()->subDay(),
        ])->saveQuietly();

        $response = $this->get('/contacts/export');

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('newer@example.com', $csv);
        $this->assertStringContainsString('older@example.com', $csv);

        $this->assertTrue(
            strpos($csv, 'newer@example.com') < strpos($csv, 'older@example.com')
        );
    }

    // APIでお問い合わせ一覧を取得し検索・ページネーションできる
    public function test_api_can_list_and_search_contacts(): void
    {
        $this->createContact([
            'first_name' => 'API対象',
            'email' => 'api-target@example.com',
        ]);

        $this->createContact([
            'first_name' => '別の利用者',
            'email' => 'api-other@example.com',
        ]);

        $this->getJson('/api/v1/contacts?keyword=API対象&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.email', 'api-target@example.com')
            ->assertJsonPath('meta.per_page', 1);
    }

    // APIのお問い合わせ一覧で不正な検索条件を指定すると422を返す
    public function test_api_contact_list_rejects_invalid_filters(): void
    {
        $this->getJson('/api/v1/contacts?gender=9')->assertUnprocessable();
    }

    // APIでお問い合わせ詳細を取得する
    public function test_api_can_show_contact(): void
    {
        $contact = $this->createContact();

        $this->getJson('/api/v1/contacts/' . $contact->id)
            ->assertOk()
            ->assertJsonPath('data.id', $contact->id);
    }

    // 存在しないお問い合わせIDを指定すると404を返す
    public function test_api_show_returns_404_for_missing_contact(): void
    {
        $this->getJson('/api/v1/contacts/999999')->assertNotFound();
    }

    // APIでお問い合わせを登録して201を返す
    public function test_api_can_create_contact(): void
    {
        $category = $this->createCategory();
        $tag = $this->createTag();

        $data = $this->contactData([
            'category_id' => $category->id,
            'tag_ids' => [$tag->id],
        ]);

        $this->postJson('/api/v1/contacts', $data)
            ->assertCreated()
            ->assertJsonPath('data.email', 'test@example.com');

        $contact = Contact::where('email', 'test@example.com')->firstOrFail();

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    // APIで不正なお問い合わせ内容を登録すると422を返す
    public function test_api_contact_creation_rejects_invalid_input(): void
    {
        $this->postJson('/api/v1/contacts', [])
            ->assertUnprocessable();
    }

    // APIでお問い合わせ内容を更新して200を返す
    public function test_api_can_update_contact(): void
    {
        $contact = $this->createContact();
        $category = $this->createCategory();
        $tag = $this->createTag();

        $data = $this->contactData([
            'first_name' => '更新後',
            'email' => 'updated@example.com',
            'category_id' => $category->id,
            'tag_ids' => [$tag->id],
        ]);

        $this->putJson('/api/v1/contacts/' . $contact->id, $data)
            ->assertOk()
            ->assertJsonPath('data.email', 'updated@example.com');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'email' => 'updated@example.com',
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    // 存在しないお問い合わせを更新すると404を返す
    public function test_api_update_returns_404_for_missing_contact(): void
    {
        $this->putJson('/api/v1/contacts/999999', $this->contactData())
            ->assertNotFound();
    }

    // APIで不正な内容を更新すると422を返す
    public function test_api_contact_update_rejects_invalid_input(): void
    {
        $contact = $this->createContact();

        $this->putJson('/api/v1/contacts/' . $contact->id, [])
            ->assertUnprocessable();
    }

    // APIでお問い合わせを削除して204を返す
    public function test_api_can_delete_contact(): void
    {
        $contact = $this->createContact();

        $this->deleteJson('/api/v1/contacts/' . $contact->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    // 存在しないお問い合わせを削除すると404を返す
    public function test_api_delete_returns_404_for_missing_contact(): void
    {
        $this->deleteJson('/api/v1/contacts/999999')->assertNotFound();
    }
}
