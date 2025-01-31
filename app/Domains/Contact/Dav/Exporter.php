<?php

namespace App\Domains\Contact\Dav;

abstract class Exporter
{
    /**
     * @param  mixed  $value
     * @return string|null
     */
    protected function escape(mixed $value): ?string
    {
        $value = (string) $value;

        return ! empty($value) ? trim($value) : null;
    }
}
