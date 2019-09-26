<?php

namespace App;

use App\Traits\HasEncryptedAttributes;
use Illuminate\Database\Eloquent\Model;

class DeletedUsername extends Model
{
    use HasEncryptedAttributes;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $encrypted = [
        'username'
    ];

    protected $fillable = [
        'username'
    ];
}
