<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Contact;

class Tag extends Model
{
    use HasFactory;

    //一括代入を許可
    protected $fillable = [
        'name',
    ];

    // タグが付与されているお問い合わせを取得
    public function contacts()
    {
        return $this->belongsToMany(Contact::class);
    }
}
