<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

class ConvertBoolean extends TransformsRequest
{
    protected function transform($key, $value)
    {
        if (($value === 'true' || $value === 'false')) {
            return $value === 'true' ? 1 : 0;
        }

        if ($value === 'null') {
            return '';
        }

        return $value;
    }
}
