<?php

namespace App\Services\Profile;

use App\Models\PublicUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    /**
     * Get profile for public user.
     */
    public function getProfile(User $user): PublicUser
    {
        $publicUser = $user->publicUser();

        if (! $publicUser) {
            throw new \Exception('Public user profile not found.');
        }

        return $publicUser->load('user');
    }

    /**
     * Check if profile is complete.
     */
    public function isProfileComplete(User $user): bool
    {
        $publicUser = $user->publicUser();

        if (! $publicUser) {
            return false;
        }

        // Profile is considered complete if all required fields are filled
        return ! empty($publicUser->phone)
            && ! empty($publicUser->date_of_birth)
            && ! empty($publicUser->address);
    }

    /**
     * Complete profile for public user.
     */
    public function completeProfile(User $user, array $data): PublicUser
    {
        $publicUser = $user->publicUser();

        if (! $publicUser) {
            throw new \Exception('Public user profile not found.');
        }

        $publicUser->update([
            'phone' => $data['phone'],
            'date_of_birth' => $data['date_of_birth'],
            'address' => $data['address'],
            'biodata' => $data['biodata'] ?? null,
        ]);

        return $publicUser->load('user');
    }

    /**
     * Update profile for public user.
     */
    public function updateProfile(User $user, array $data): PublicUser
    {
        $publicUser = $user->publicUser();

        if (! $publicUser) {
            throw new \Exception('Public user profile not found.');
        }

        // Update user basic info if provided
        if (isset($data['name'])) {
            $user->update(['name' => $data['name']]);
        }

        // Update public user profile
        $updateData = [];
        if (isset($data['phone'])) {
            $updateData['phone'] = $data['phone'];
        }
        if (isset($data['date_of_birth'])) {
            $updateData['date_of_birth'] = $data['date_of_birth'];
        }
        if (isset($data['address'])) {
            $updateData['address'] = $data['address'];
        }
        if (isset($data['biodata'])) {
            $updateData['biodata'] = $data['biodata'];
        }

        $publicUser->update($updateData);

        return $publicUser->fresh('user');
    }

    /**
     * Update user password.
     */
    public function updatePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new \Exception('Current password is incorrect.');
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return true;
    }

    /**
     * Get profile completion status.
     */
    public function getProfileCompletionStatus(User $user): array
    {
        $publicUser = $user->publicUser();

        if (! $publicUser) {
            return [
                'is_complete' => false,
                'completion_percentage' => 0,
                'missing_fields' => ['phone', 'date_of_birth', 'address'],
            ];
        }

        $fields = [
            'name' => ! empty($user->name),
            'email' => ! empty($user->email) && $user->email_verified_at !== null,
            'phone' => ! empty($publicUser->phone),
            'date_of_birth' => ! empty($publicUser->date_of_birth),
            'address' => ! empty($publicUser->address),
        ];

        $completed = count(array_filter($fields));
        $total = count($fields);
        $completionPercentage = ($completed / $total) * 100;

        $missingFields = [];
        foreach ($fields as $field => $isFilled) {
            if (! $isFilled) {
                $missingFields[] = $field;
            }
        }

        return [
            'is_complete' => $completionPercentage === 100,
            'completion_percentage' => round($completionPercentage, 2),
            'missing_fields' => $missingFields,
            'fields' => $fields,
        ];
    }
}
