<?php

namespace App\Services\Auth;

use App\Mail\VerifyEmailMail;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailVerificationService
{
    public function sendVerificationEmail(User $user): void
    {
        $token = Str::random(64);
        $expiresAt = now()->addHours(24);

        EmailVerification::updateOrCreate(
            ['user_id' => $user->id],
            [
                'token' => $token,
                'expires_at' => $expiresAt,
                'verified_at' => null,
            ]
        );

        // Send email via queue
        Mail::to($user->email)->queue(new VerifyEmailMail($user, $token));
    }

    public function verifyEmail(string $token): User
    {
        $verification = EmailVerification::where('token', $token)
            ->where('expires_at', '>', now())
            ->whereNull('verified_at')
            ->first();

        if (! $verification) {
            throw new \Exception('Invalid or expired verification token');
        }

        $user = $verification->user;

        if ($user->email_verified_at) {
            throw new \Exception('Email already verified');
        }

        $user->update(['email_verified_at' => now()]);
        $verification->update(['verified_at' => now()]);

        // Activate company if user is company admin
        if ($user->userable instanceof \App\Models\CompanyAdmin) {
            $user->userable->company->update(['is_active' => true]);
        }

        return $user;
    }

    public function resendVerificationEmail(User $user): void
    {
        if ($user->email_verified_at) {
            throw new \Exception('Email already verified');
        }

        // Delete old verification tokens
        EmailVerification::where('user_id', $user->id)
            ->whereNull('verified_at')
            ->delete();

        $this->sendVerificationEmail($user);
    }
}
