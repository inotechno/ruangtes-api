<?php

namespace App\Services\Auth;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    /**
     * Send password reset link to user email.
     */
    public function sendResetLink(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw new \Exception('User not found');
        }

        // Generate token
        $token = Str::random(64);

        // Store token in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Send email via queue
        Mail::to($user->email)->queue(new ResetPasswordMail($user, $token));
    }

    /**
     * Reset user password using token.
     */
    public function resetPassword(string $email, string $token, string $password): void
    {
        $resetToken = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $resetToken) {
            throw new \Exception('Invalid or expired reset token');
        }

        // Check if token is valid (created within last 60 minutes)
        $createdAt = $resetToken->created_at
            ? \Carbon\Carbon::parse($resetToken->created_at)
            : null;
        $expiresAt = now()->subMinutes(60);

        if (! $createdAt || $createdAt < $expiresAt) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            throw new \Exception('Reset token has expired');
        }

        // Verify token
        if (! Hash::check($token, $resetToken->token)) {
            throw new \Exception('Invalid reset token');
        }

        // Update user password
        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new \Exception('User not found');
        }

        $user->update([
            'password' => Hash::make($password),
        ]);

        // Delete used token
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }
}
