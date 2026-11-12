<?php

namespace App\Exceptions;

use RuntimeException;

class SupabaseLoginException extends RuntimeException
{
    public function __construct(
        public readonly string $reason
    ) {
        parent::__construct('Supabase login failed: '.$reason);
    }
}