<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

trait HasEncryptedAttributes
{
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (! is_null($value) && in_array($key, $this->encrypted)) {
            $value = Crypt::decrypt($value);
        }

        return $value;
    }

    public function setAttribute($key, $value)
    {
        if (! is_null($value) && in_array($key, $this->encrypted)) {
            $value = Crypt::encrypt($value);
        }

        return parent::setAttribute($key, $value);
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        foreach ($this->encrypted as $key) {
            if (isset($attributes[$key])) {
                $attributes[$key] = Crypt::decrypt($attributes[$key]);
            }
        }

        return $attributes;
    }
}
