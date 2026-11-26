<?php

namespace App\Services\Apps;

use App\Contracts\AppProvisioner;
use App\Models\AccountUser;
use App\Models\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class Perfect10Provisioner implements AppProvisioner
{
    public function provision(
        AccountUser $user,
        App $app
    ): void {
        $baseUrl = config(
            'elsewhere_oauth.perfect10_url'
        );

        if (
            ! is_string($baseUrl) ||
            ! filter_var($baseUrl, FILTER_VALIDATE_URL) ||
            ! in_array(
                parse_url($baseUrl, PHP_URL_SCHEME),
                ['http', 'https'],
                true
            ) ||
            parse_url($baseUrl, PHP_URL_USER) !== null ||
            parse_url($baseUrl, PHP_URL_PASS) !== null ||
            parse_url($baseUrl, PHP_URL_QUERY) !== null ||
            parse_url($baseUrl, PHP_URL_FRAGMENT) !== null
        ) {
            throw new RuntimeException(
                'Perfect10 provisioning URL is not configured.'
            );
        }

        $configuredKey = config(
            'app_enrolment.signing_key'
        );

        if (
            ! is_string($configuredKey) ||
            ! preg_match('/\A[a-fA-F0-9]{64}\z/', $configuredKey)
        ) {
            throw new RuntimeException(
                'Perfect10 provisioning signing key is not configured.'
            );
        }

        $key = hex2bin($configuredKey);

        if ($key === false) {
            throw new RuntimeException(
                'Invalid Perfect10 provisioning signing key.'
            );
        }

        if (
            ! is_string($user->auth_id) ||
            ! Str::isUuid($user->auth_id) ||
            ! is_string($user->username) ||
            $user->username === ''
        ) {
            throw new RuntimeException(
                'Invalid Elsewhere account identity.'
            );
        }

        /*
         * Build the JSON once.
         *
         * The exact bytes used for signing must also
         * be sent as the HTTP request body.
         */
        $body = json_encode([
            'auth_id' => $user->auth_id,
            'username' => $user->username,
        ], JSON_THROW_ON_ERROR);

        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(16));

        $message = $timestamp."\n"
            .$nonce."\n"
            .$body;

        $signature = hash_hmac(
            'sha256',
            $message,
            $key
        );

        $url = rtrim($baseUrl, '/')
            .'/api/integrations/elsewhere/profiles';

        /*
         * This request is independent of the user's
         * browser authentication session.
         */
        $response = Http::acceptJson()
            ->withHeaders([
                'X-Elsewhere-Timestamp' => $timestamp,
                'X-Elsewhere-Nonce' => $nonce,
                'X-Elsewhere-Signature' => $signature,
            ])
            ->withBody($body, 'application/json')
            ->timeout((int) config('app_enrolment.timeout', 10))
            ->post($url);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Perfect10 profile provisioning failed.'
            );
        }

        $profileId = $response->json('profile_id');

        if (
            ! is_string($profileId) ||
            ! Str::isUuid($profileId) ||
            $response->json('provisioned') !== true
        ) {
            throw new RuntimeException(
                'Perfect10 returned an invalid provisioning response.'
            );
        }
    }
}
