<?php

namespace App;

use App\Traits\HasEncryptedAttributes;
use Illuminate\Database\Eloquent\Model;

class DeletedUsername extends Model
{
    use HasEncryptedAttributes;

    public $incrementing = false;

    public $timestamps = false;

    protected $encrypted = [
        'username'
    ];

    protected $fillable = [
        'username'
    ];
}
