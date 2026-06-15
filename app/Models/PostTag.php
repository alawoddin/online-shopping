<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Post;

class PostTag extends Model
{
    protected $fillable=['title','slug','status'];

    public function post()
    {
        return $this->hasMany(Post::class,'post_tag_id','id');
    }
}