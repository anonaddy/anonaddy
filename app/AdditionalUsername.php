<?php

namespace App;

use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class AdditionalUsername extends Model
{
    use HasUuid, HasEncryptedAttributes;

    public $incrementing = false;

    protected $encrypted = [
        'description'
    ];

    protected $fillable = [
        'username',
        'description',
        'active'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'active' => 'boolean'
    ];

    /**
     * Set the username.
     */
    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = strtolower($value);
    }

    /**
     * Get the user for the additional username.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Deactivate the username.
     */
    public function deactivate()
    {
        $this->update(['active' => false]);
    }

    /**
     * Activate the username.
     */
    public function activate()
    {
        $this->update(['active' => true]);
    }
}
