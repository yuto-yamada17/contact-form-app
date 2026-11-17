<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    //1つのカテゴリから、紐づく複数のお問い合わせ（hasMany）が正しく取得できること。
    public function test_category_has_many_contacts(): void
    {
        $category = Category::create(['content' => '商品のお届けについて']);

        $contact1 = Contact::create($this->contactData($category->id, 'taro@example.com'));
        $contact2 = Contact::create($this->contactData($category->id, 'hanako@example.com'));

        // カテゴリに紐づく複数のお問い合わせを取得できること。
        $contacts = $category->Contact;

        $this->assertCount(2, $contacts);
        $this->assertTrue($contacts->contains('id', $contact1->id));
        $this->assertTrue($contacts->contains('id', $contact2->id));
    }

    //1つのお問い合わせが特定のカテゴリに属し、複数のタグと同期（sync）できること。
    public function test_contact_belongs_to_category_and_syncs_multiple_tags(): void
    {
        $category = Category::create(['content' => '商品のお届けについて']);
        $contact = Contact::create($this->contactData($category->id, 'taro@example.com'));

        // お問い合わせが特定のカテゴリに属していること。
        $this->assertTrue($contact->category->is($category));

        $tag1 = Tag::create(['name' => '重要']);
        $tag2 = Tag::create(['name' => '確認済み']);

        // 1つのお問い合わせに複数のタグを同期（sync）できること。
        $contact->tags()->sync([$tag1->id, $tag2->id]);

        $this->assertCount(2, $contact->fresh()->tags);
        $this->assertEqualsCanonicalizing(
            [$tag1->id, $tag2->id],
            $contact->fresh()->tags->pluck('id')->all()
        );
    }

    //中間テーブルを介して、1つのタグが複数のお問い合わせに紐づいていること。
    public function test_tag_belongs_to_many_contacts(): void
    {
        $category = Category::create(['content' => '商品のお届けについて']);
        $tag = Tag::create(['name' => '重要']);

        $contact1 = Contact::create($this->contactData($category->id, 'taro@example.com'));
        $contact2 = Contact::create($this->contactData($category->id, 'hanako@example.com'));

        // 中間テーブルを介して、1つのタグを複数のお問い合わせに紐づけること。
        $tag->contacts()->sync([$contact1->id, $contact2->id]);

        $contacts = $tag->fresh()->contacts;

        $this->assertCount(2, $contacts);
        $this->assertTrue($contacts->contains('id', $contact1->id));
        $this->assertTrue($contacts->contains('id', $contact2->id));
    }

    //お問い合わせのテストデータを作成する。
    private function contactData(int $categoryId, string $email): array
    {
        return [
            'category_id' => $categoryId,
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => $email,
            'tel' => '09012345678',
            'address' => '東京都千代田区',
            'building' => null,
            'detail' => 'お問い合わせ内容です。',
        ];
    }
}
