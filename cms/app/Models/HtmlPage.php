<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HtmlPage extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    /** Path relative to the `pages` disk (storage/app/html-pages, added in Task 14). */
    public function storagePath(): string
    {
        return $this->token.'.html';
    }
}
