
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE public.sessions
             ALTER COLUMN user_id TYPE UUID
             USING NULL::UUID'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE public.sessions
             ALTER COLUMN user_id TYPE BIGINT
             USING NULL::BIGINT'
        );
    }
};
