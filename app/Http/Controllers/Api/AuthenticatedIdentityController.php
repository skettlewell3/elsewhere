<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedIdentityController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user('api');
        $client = Auth::guard('api')->client();

        $expectedClientId = config(
            'ecosystem.perfect10_client_id'
        );

        if (
            ! is_string($expectedClientId) ||
            $expectedClientId === '' ||
            $client === null ||
            (string) $client->getKey() !== $expectedClientId
        ) {
            return response()->json([
                'message' => 'Application access denied.',
            ], 403);
        }

        $app = App::query()
            ->where('slug', 'perfect10')
            ->where('status', 'active')
            ->first();

        if ($app === null) {
            return response()->json([
                'message' => 'Application unavailable.',
            ], 403);
        }

        $hasMembership = AppMembership::query()
            ->where('auth_id', $user->getAuthIdentifier())
            ->where('app_id', $app->app_id)
            ->where('status', 'active')
            ->exists();

        if (! $hasMembership) {
            return response()->json([
                'message' => 'Application membership required.',
            ], 403);
        }

        return response()->json([
            'auth_id' => $user->getAuthIdentifier(),
        ]);
    }
}
