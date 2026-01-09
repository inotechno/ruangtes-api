<?php

namespace App\Http\Middleware;

use App\Models\TestSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventCheating
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionToken = $request->header('X-Session-Token') ?? $request->input('session_token');

        if (! $sessionToken) {
            return response()->json([
                'success' => false,
                'message' => 'Session token is required',
            ], 400);
        }

        $session = TestSession::where('session_token', $sessionToken)->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session token',
            ], 404);
        }

        // Check if session is active
        if ($session->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Test session is not active',
            ], 403);
        }

        // Check if session is banned
        if ($session->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Test session has been banned due to cheating',
            ], 403);
        }

        // Attach session to request
        $request->merge(['test_session' => $session]);

        return $next($request);
    }
}
