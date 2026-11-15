<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Contact;
use App\Models\Category;
use App\Models\Tag;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        // 日本語のFakerを使用
        $faker = fake('ja_JP');

        // 登録済みのカテゴリーとタグを取得
        $categories = Category::select('id', 'content')->get();
        $tags = Tag::select('id', 'name')->get();

        // カテゴリーとタグの存在確認
        if ($categories->isEmpty() || $tags->isEmpty()) {
            throw new \RuntimeException(
                'カテゴリーまたはタグが登録されていません。'
            );
        }

        // お問い合わせデータを20件登録
        for ($i = 0; $i < 20; $i++) {

            // お問い合わせデータを作成
            $contact = Contact::create([
                'first_name' => $faker->lastName(),
                'last_name' => $faker->firstName(),
                'gender' => $faker->numberBetween(1, 3),
                'email' => $faker->unique()->safeEmail(),
                'tel' => $faker->numerify('###########'),
                'address' => $faker->address(),
                'building' => $faker->optional()->secondaryAddress(),
                'category_id' => $categories->random()->id,
                'detail' => $faker->randomElement([
                    '商品の配送状況について確認したいです。',
                    '商品の交換をお願いしたいです。',
                    '購入した商品に不具合がありました。',
                    'ショップのサービスについて質問があります。',
                    'その他について相談したいです。',
                ]),
            ]);

            // タグをランダムに1～3件選択
            $randomTags = $tags->random(
                $faker->numberBetween(1, 3)
            );

            // タグを中間テーブルに紐付け
            $contact->tags()->attach(
                $randomTags->pluck('id')->all()
            );
        }
    }
}
