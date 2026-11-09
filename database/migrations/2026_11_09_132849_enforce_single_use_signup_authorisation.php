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
    v_auth_id uuid;
    v_provider text;
    v_code_id bigint;
    v_attempt_id bigint;
    v_claimed_id bigint;
BEGIN
    v_email := lower(btrim(
        coalesce(event->'user'->>'email', '')
    ));

    v_token := coalesce(
        event->'user'->'user_metadata'
            ->>'signup_reservation_token',
        ''
    );

    v_provider := coalesce(
        event->'user'->'app_metadata'->>'provider',
        ''
    );

    IF v_email = ''
       OR v_provider <> 'email'
       OR v_token !~ '^[0-9a-f]{64}$'
       OR coalesce(
            event->'user'->>'id', ''
       ) !~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'
    THEN
        RETURN jsonb_build_object(
            'error',
            jsonb_build_object(
                'http_code', 403,
                'message', 'Registration is not authorised.'
            )
        );
    END IF;

    v_auth_id := (event->'user'->>'id')::uuid;

    v_token_hash := encode(
        sha256(convert_to(v_token, 'UTF8')),
        'hex'
    );

    /*
     * Identify the parent code.
     * The attempt must already exist and must have
     * entered creating_auth through Laravel.
     */

    SELECT
        c.signup_code_id,
        a.registration_attempt_id
    INTO
        v_code_id,
        v_attempt_id
    FROM public.signup_code_reservations AS r
    JOIN public.signup_codes AS c
        ON c.signup_code_id = r.signup_code_id
    JOIN public.registration_attempts AS a
        ON a.reservation_id = r.reservation_id
    WHERE r.token_hash = v_token_hash
      AND r.email = v_email
      AND a.email = v_email
      AND r.status = 'reserved'
      AND a.status = 'creating_auth'
      AND a.auth_id IS NULL
      AND a.auth_requested_at IS NOT NULL
      AND a.auth_requested_at <= r.expires_at
      AND a.auth_requested_at <= now()
      AND now() < a.auth_requested_at + interval '15 minutes'
      AND c.active = true
      AND (
          c.expires_at IS NULL
          OR c.expires_at > now()
      )
      AND (
          c.max_uses IS NULL
          OR c.use_count < c.max_uses
      )
    FOR UPDATE OF c;

    IF v_code_id IS NULL OR v_attempt_id IS NULL THEN
        RETURN jsonb_build_object(
            'error',
            jsonb_build_object(
                'http_code', 403,
                'message', 'Registration is not authorised.'
            )
        );
    END IF;

    /*
     * Atomically claim the proposed Auth UUID.
     *
     * Only the first claim can change auth_id
     * from NULL to the proposed UUID.
     *
     * The status stays creating_auth because
     * Supabase has not confirmed creation yet.
     */

    UPDATE public.registration_attempts AS a
    SET
        auth_id = v_auth_id,
        updated_at = now()
    WHERE a.registration_attempt_id = v_attempt_id
      AND a.status = 'creating_auth'
      AND a.auth_id IS NULL
      AND a.email = v_email
    RETURNING a.registration_attempt_id
    INTO v_claimed_id;

    IF v_claimed_id IS NULL THEN
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

GRANT USAGE ON SCHEMA public TO supabase_auth_admin;

GRANT EXECUTE
ON FUNCTION public.hook_authorise_elsewhere_signup(jsonb)
TO supabase_auth_admin;
SQL);
    }

    public function down(): void
    {
        // Do not restore a previously less restrictive
        // registration authorisation function.
        //
        // Rollback requires an explicit replacement
        // security migration.
    }
};
