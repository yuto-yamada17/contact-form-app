<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    //一括代入を許可
    protected $fillable = 'content';

    //カテゴリーが持つ問い合わせ内容を取得
    public function Contact()
    {
        return $this->hasMany(Contact::class);
    }
}
