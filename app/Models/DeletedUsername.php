<?php

namespace App\Models;

use App\Traits\HasEncryptedAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeletedUsername extends Model
{
    use HasEncryptedAttributes;
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $encrypted = [
        'username',
    ];

    protected $fillable = [
        'username',
    ];
}
