<?php

namespace App\Http\Middleware;

use App\Models\Participant;
use App\Models\TestAssignment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateParticipantToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token') ?? $request->query('token') ?? $request->input('token');

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required',
            ], 400);
        }

        $assignment = TestAssignment::where('unique_token', $token)->first();

        if (! $assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
            ], 404);
        }

        $participant = $assignment->participant;

        // Check if participant is banned
        if ($participant->isBanned()) {
            return response()->json([
                'success' => false,
                'message' => 'You have been banned from taking tests',
            ], 403);
        }

        // Check if assignment period is valid
        if (now() < $assignment->start_date || now() > $assignment->end_date) {
            return response()->json([
                'success' => false,
                'message' => 'Test assignment period has expired or not yet started',
            ], 403);
        }

        // Check if already completed
        if ($assignment->is_completed) {
            return response()->json([
                'success' => false,
                'message' => 'Test assignment already completed',
            ], 403);
        }

        // Attach assignment and participant to request
        $request->merge([
            'assignment' => $assignment,
            'participant' => $participant,
        ]);

        return $next($request);
    }
}
