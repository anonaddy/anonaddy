<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AliasRecipient extends Pivot
{
    use HasUuid;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $table = 'alias_recipients';

    protected $casts = [
        'id' => 'string',
        'alias_id' => 'string',
        'recipient_id' => 'string',
    ];

    public function setAliasAttribute($alias)
    {
        $this->attributes['alias_id'] = $alias->getKey();
        $this->setRelation('alias', $alias);
    }

    public function setRecipientAttribute($recipient)
    {
        $this->attributes['recipient_id'] = $recipient->getKey();
        $this->setRelation('recipient', $recipient);
    }

    /**
     * Get the alias for this pivot row.
     */
    public function alias()
    {
        return $this->belongsTo(Alias::class);
    }

    /**
     * Get the recipient for this pivot row.
     */
    public function recipient()
    {
        return $this->belongsTo(Recipient::class);
    }
}
