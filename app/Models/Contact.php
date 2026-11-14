<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    //一括代入を許可
    protected $fillable = [
        'category_id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'tel',
        'address',
        'building',
        'detail',
    ];

    //問い合わせ内容が属しているカテゴリーを取得
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    //問い合わせ内容が持つタグを取得
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
