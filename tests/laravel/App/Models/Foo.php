<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Railken\Lem\Contracts\EntityContract;

class Foo extends Model implements EntityContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'foo';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
    ];
}
