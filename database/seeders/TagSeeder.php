<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        //登録するタグ一覧
        $data = [
            ['name' => '質問'],
            ['name' => '要望'],
            ['name' => '不具合報告'],
            ['name' => '意見'],
            ['name' => 'その他'],
        ];

        //バルクインサートで一括登録
        Tag::insert($data);
    }
}
