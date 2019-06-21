<?php

namespace App;

use App\Traits\HasEncryptedAttributes;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasUuid, HasEncryptedAttributes;

    public $incrementing = false;

    protected $encrypted = [
        'description'
    ];

    protected $fillable = [
        'domain',
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
     * Set the domain's name.
     */
    public function setDomainAttribute($value)
    {
        $this->attributes['domain'] = strtolower($value);
    }

    /**
     * Get the user for the custom domain.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the domains's aliases.
     */
    public function aliases()
    {
        return $this->hasMany(Alias::class);
    }

    /**
     * Deactivate the alias.
     */
    public function deactivate()
    {
        $this->update(['active' => false]);
    }

    /**
     * Activate the alias.
     */
    public function activate()
    {
        $this->update(['active' => true]);
    }
}
