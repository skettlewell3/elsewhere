<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Registration tables are server-only.
         *
         * Laravel connects as postgres.
         * The Supabase Auth hook uses supabase_auth_admin.
         */
        DB::unprepared(<<<'SQL'
            REVOKE ALL ON TABLE
                public.signup_codes,
                public.signup_code_reservations,
                public.signup_code_redemptions,
                public.registration_attempts
            FROM PUBLIC, anon, authenticated, service_role;

            GRANT USAGE ON SCHEMA public
                TO supabase_auth_admin;

            GRANT SELECT ON TABLE
                public.signup_codes,
                public.signup_code_reservations,
                public.registration_attempts
            TO supabase_auth_admin;

            -- Required to acquire a row lock on signup_codes.
            -- Do not grant unrestricted table UPDATE privileges.
            GRANT UPDATE (signup_code_id)
                ON public.signup_codes
                TO supabase_auth_admin;

            GRANT UPDATE (auth_id, updated_at)
                ON public.registration_attempts
                TO supabase_auth_admin;

            CREATE POLICY signup_hook_read_codes
                ON public.signup_codes
                FOR SELECT
                TO supabase_auth_admin
                USING (true);
            
            CREATE POLICY signup_hook_lock_codes
                ON public.signup_codes
                FOR UPDATE
                TO supabase_auth_admin
                USING (true)
                WITH CHECK (true);

            CREATE POLICY signup_hook_read_reservations
                ON public.signup_code_reservations
                FOR SELECT
                TO supabase_auth_admin
                USING (true);

            CREATE POLICY signup_hook_read_attempts
                ON public.registration_attempts
                FOR SELECT
                TO supabase_auth_admin
                USING (true);

            CREATE POLICY signup_hook_claim_attempt
                ON public.registration_attempts
                FOR UPDATE
                TO supabase_auth_admin
                USING (true)
                WITH CHECK (true);
        SQL);

        /*
         * Supabase Before User Created Auth Hook.
         *
         * The Auth server supplies the proposed user
         * and its signup metadata as JSONB.
         *
         * Reject requests that do not have a matching
         * server-created reservation and attempt.
         */
        DB::unprepared(<<<'SQL'
            CREATE FUNCTION public.elsewhere_before_user_created(
                event jsonb
            )
            RETURNS jsonb
            LANGUAGE plpgsql
            SECURITY INVOKER
            SET search_path = ''
            AS $$
            DECLARE
                v_auth_id uuid;
                v_email text;
                v_token text;
                v_token_hash text;
                v_provider text;
                v_first_name text;
                v_last_name text;
                v_signup_code_id bigint;
                v_attempt_id bigint;
                v_updated integer;
            BEGIN
                v_provider :=
                    event->'user'->'app_metadata'->>'provider';

                v_email := pg_catalog.lower(
                    pg_catalog.btrim(
                        event->'user'->>'email'
                    )
                );

                v_token :=
                    event->'user'->'user_metadata'
                        ->>'signup_reservation_token';

                v_first_name :=
                    event->'user'->'user_metadata'
                        ->>'first_name';

                v_last_name :=
                    event->'user'->'user_metadata'
                        ->>'last_name';

                /*
                 * The registration workflow currently
                 * supports invitation-based email signup.
                 */
                IF v_provider IS DISTINCT FROM 'email'
                    OR v_email IS NULL
                    OR v_email = ''
                    OR v_token IS NULL
                    OR v_token !~ '^[0-9a-f]{64}$'
                    OR v_first_name IS NULL
                    OR pg_catalog.btrim(v_first_name) = ''
                    OR pg_catalog.char_length(v_first_name) > 100
                    OR v_last_name IS NULL
                    OR pg_catalog.btrim(v_last_name) = ''
                    OR pg_catalog.char_length(v_last_name) > 100
                THEN
                    RETURN pg_catalog.jsonb_build_object(
                        'error',
                        pg_catalog.jsonb_build_object(
                            'http_code', 403,
                            'message', 'Registration is not authorised.'
                        )
                    );
                END IF;

                IF event->'user'->>'id' IS NULL THEN
                    RETURN pg_catalog.jsonb_build_object(
                        'error',
                        pg_catalog.jsonb_build_object(
                            'http_code', 403,
                            'message', 'Registration is not authorised.'
                        )
                    );
                END IF;

                v_auth_id := (
                    event->'user'->>'id'
                )::uuid;

                v_token_hash := pg_catalog.encode(
                    pg_catalog.sha256(
                        pg_catalog.convert_to(
                            v_token,
                            'UTF8'
                        )
                    ),
                    'hex'
                );

                /*
                 * Lock the parent signup code first.
                 *
                 * This matches the lock ordering used
                 * by SignupCodeService.
                 */
                SELECT sc.signup_code_id
                INTO v_signup_code_id
                FROM public.signup_codes AS sc
                JOIN public.signup_code_reservations AS r
                    ON r.signup_code_id =
                        sc.signup_code_id
                WHERE r.token_hash = v_token_hash
                FOR UPDATE OF sc;

                IF NOT FOUND THEN
                    RETURN pg_catalog.jsonb_build_object(
                        'error',
                        pg_catalog.jsonb_build_object(
                            'http_code', 403,
                            'message', 'Registration is not authorised.'
                        )
                    );
                END IF;

                /*
                 * Re-read the reservation and attempt
                 * after acquiring the parent lock.
                 */
                SELECT a.registration_attempt_id
                INTO v_attempt_id
                FROM public.signup_code_reservations AS r
                JOIN public.registration_attempts AS a
                    ON a.reservation_id = r.reservation_id
                JOIN public.signup_codes AS sc
                    ON sc.signup_code_id = r.signup_code_id
                WHERE sc.signup_code_id = v_signup_code_id
                    AND sc.active = true
                    AND (
                        sc.expires_at IS NULL
                        OR sc.expires_at > now()
                    )
                    AND (
                        sc.max_uses IS NULL
                        OR sc.use_count < sc.max_uses
                    )
                    AND r.token_hash = v_token_hash
                    AND r.email = v_email
                    AND r.status = 'reserved'
                    AND r.expires_at > now()
                    AND a.email = v_email
                    AND a.status = 'creating_auth'
                    AND a.auth_id IS NULL;

                IF NOT FOUND THEN
                    RETURN pg_catalog.jsonb_build_object(
                        'error',
                        pg_catalog.jsonb_build_object(
                            'http_code', 403,
                            'message', 'Registration is not authorised.'
                        )
                    );
                END IF;

                /*
                 * Claim exactly this Auth UUID.
                 *
                 * The unique constraint on auth_id
                 * prevents competing identity claims.
                 */
                UPDATE public.registration_attempts
                SET
                    auth_id = v_auth_id,
                    updated_at = now()
                WHERE registration_attempt_id = v_attempt_id
                    AND status = 'creating_auth'
                    AND auth_id IS NULL;

                GET DIAGNOSTICS v_updated = ROW_COUNT;

                IF v_updated <> 1 THEN
                    RETURN pg_catalog.jsonb_build_object(
                        'error',
                        pg_catalog.jsonb_build_object(
                            'http_code', 403,
                            'message', 'Registration is not authorised.'
                        )
                    );
                END IF;

                RETURN '{}'::jsonb;
            END;
            $$;

            REVOKE ALL ON FUNCTION
                public.elsewhere_before_user_created(jsonb)
            FROM PUBLIC, anon, authenticated, service_role;

            GRANT EXECUTE ON FUNCTION
                public.elsewhere_before_user_created(jsonb)
            TO supabase_auth_admin;
        SQL);
    }

    public function down(): void
    {
        /*
         * Disable the hook in Supabase Auth settings
         * before rolling back this migration.
         */
        DB::unprepared(<<<'SQL'
            DROP FUNCTION IF EXISTS
                public.elsewhere_before_user_created(jsonb);
            
            DROP POLICY IF EXISTS signup_hook_lock_codes
                ON public.signup_codes;

            DROP POLICY IF EXISTS signup_hook_claim_attempt
                ON public.registration_attempts;

            DROP POLICY IF EXISTS signup_hook_read_attempts
                ON public.registration_attempts;

            DROP POLICY IF EXISTS signup_hook_read_reservations
                ON public.signup_code_reservations;

            DROP POLICY IF EXISTS signup_hook_read_codes
                ON public.signup_codes;

            REVOKE SELECT ON TABLE
                public.signup_codes,
                public.signup_code_reservations,
                public.registration_attempts
            FROM supabase_auth_admin;

            REVOKE UPDATE (signup_code_id)
                ON public.signup_codes
                FROM supabase_auth_admin;

            REVOKE UPDATE (auth_id, updated_at)
                ON public.registration_attempts
                FROM supabase_auth_admin;
        SQL);

        /*
         * The previous broad client permissions are
         * intentionally not restored on rollback.
         */
    }
};
