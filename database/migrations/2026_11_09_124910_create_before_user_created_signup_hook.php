<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION public.hook_authorise_elsewhere_signup(
    event jsonb
)
RETURNS jsonb
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = ''
AS $$
DECLARE
    v_email text;
    v_token text;
    v_token_hash text;
    v_authorised boolean;
BEGIN
    v_email := lower(btrim(
        coalesce(event->'user'->>'email', '')
    ));

    v_token := coalesce(
        event->'user'->'user_metadata'
            ->>'signup_reservation_token',
        ''
    );

    IF v_email = ''
        OR v_token !~ '^[0-9a-f]{64}$'
    THEN
        RETURN jsonb_build_object(
            'error',
            jsonb_build_object(
                'http_code', 403,
                'message', 'Registration is not authorised.'
            )
        );
    END IF;

    v_token_hash := encode(
        sha256(convert_to(v_token, 'UTF8')),
        'hex'
    );

    SELECT EXISTS (
        SELECT 1
        FROM public.signup_code_reservations AS r
        JOIN public.signup_codes AS c
            ON c.signup_code_id = r.signup_code_id
        WHERE r.token_hash = v_token_hash
          AND r.email = v_email
          AND r.status = 'reserved'
          AND r.expires_at > now()
          AND c.active = true
          AND (
              c.expires_at IS NULL
              OR c.expires_at > now()
          )
          AND (
              c.max_uses IS NULL
              OR c.use_count < c.max_uses
          )
    )
    INTO v_authorised;

    IF NOT coalesce(v_authorised, false) THEN
        RETURN jsonb_build_object(
            'error',
            jsonb_build_object(
                'http_code', 403,
                'message', 'Registration is not authorised.'
            )
        );
    END IF;

    RETURN '{}'::jsonb;
END;
$$;

REVOKE ALL
ON FUNCTION public.hook_authorise_elsewhere_signup(jsonb)
FROM PUBLIC, anon, authenticated;

GRANT EXECUTE
ON FUNCTION public.hook_authorise_elsewhere_signup(jsonb)
TO supabase_auth_admin;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'DROP FUNCTION IF EXISTS public.hook_authorise_elsewhere_signup(jsonb)'
        );
    }
};
