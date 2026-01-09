<?php

namespace App\Services\Participant;

use App\Models\Company;
use App\Models\Participant;
use App\Models\Test;
use App\Models\TestAssignment;
use App\Services\Participant\TestAssignmentEmailService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ParticipantService
{
    public function __construct(
        protected TestAssignmentEmailService $emailService
    ) {}

    /**
     * Generate unique token for participant.
     */
    protected function generateUniqueToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Participant::where('unique_token', $token)->exists());

        return $token;
    }

    /**
     * Generate unique token for test assignment.
     */
    protected function generateAssignmentToken(): string
    {
        do {
            $token = Str::random(32);
        } while (TestAssignment::where('unique_token', $token)->exists());

        return $token;
    }

    /**
     * Create participant with test assignments in one transaction.
     */
    public function createParticipantWithAssignments(
        Company $company,
        string $name,
        string $email,
        array $testIds,
        \DateTime $startDate,
        \DateTime $endDate
    ): Participant {
        return DB::transaction(function () use ($company, $name, $email, $testIds, $startDate, $endDate) {
            // Validate tests exist and are active
            $tests = Test::whereIn('id', $testIds)
                ->where('is_active', true)
                ->get();

            if ($tests->count() !== count($testIds)) {
                throw new \Exception('One or more tests not found or inactive.');
            }

            // Check email uniqueness within company
            if (Participant::where('company_id', $company->id)
                ->where('email', $email)
                ->exists()) {
                throw new \Exception('Email already exists for this company.');
            }

            // Create participant
            $participant = Participant::create([
                'company_id' => $company->id,
                'name' => $name,
                'email' => $email,
                'unique_token' => $this->generateUniqueToken(),
            ]);

            // Create test assignments
            $assignments = [];
            foreach ($tests as $test) {
                $assignments[] = TestAssignment::create([
                    'participant_id' => $participant->id,
                    'test_id' => $test->id,
                    'unique_token' => $this->generateAssignmentToken(),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);
            }

            // Send assignment emails (queued)
            $this->emailService->sendAssignmentEmails($assignments);

            return $participant->load(['testAssignments.test']);
        });
    }

    /**
     * Preview Excel import data (without saving).
     */
    public function previewImportData($file): array
    {
        $data = Excel::toArray([], $file);
        $rows = $data[0] ?? [];

        if (empty($rows)) {
            return [
                'valid' => [],
                'invalid' => [],
                'errors' => ['File is empty or invalid format.'],
            ];
        }

        // Skip header row (first row)
        $headerRow = array_shift($rows);
        $headerMap = $this->mapExcelHeaders($headerRow);

        $valid = [];
        $invalid = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because we removed header and array is 0-indexed

            $name = $this->getCellValue($row, $headerMap['name'] ?? null);
            $email = $this->getCellValue($row, $headerMap['email'] ?? null);

            $rowErrors = [];

            if (empty($name)) {
                $rowErrors[] = 'Name is required';
            }

            if (empty($email)) {
                $rowErrors[] = 'Email is required';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'Invalid email format';
            }

            if (empty($rowErrors)) {
                $valid[] = [
                    'row' => $rowNumber,
                    'name' => $name,
                    'email' => $email,
                ];
            } else {
                $invalid[] = [
                    'row' => $rowNumber,
                    'name' => $name,
                    'email' => $email,
                    'errors' => $rowErrors,
                ];
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
            'errors' => $errors,
        ];
    }

    /**
     * Import participants from Excel with test assignments.
     */
    public function importParticipantsWithAssignments(
        Company $company,
        array $participantsData,
        array $testIds,
        \DateTime $startDate,
        \DateTime $endDate
    ): array {
        return DB::transaction(function () use ($company, $participantsData, $testIds, $startDate, $endDate) {
            $created = [];
            $failed = [];

            // Validate tests
            $tests = Test::whereIn('id', $testIds)
                ->where('is_active', true)
                ->get();

            if ($tests->count() !== count($testIds)) {
                throw new \Exception('One or more tests not found or inactive.');
            }

            // Get existing emails for this company
            $emails = array_column($participantsData, 'email');
            $existingEmails = Participant::where('company_id', $company->id)
                ->whereIn('email', $emails)
                ->pluck('email')
                ->all();

            foreach ($participantsData as $data) {
                try {
                    // Skip if email already exists
                    if (in_array($data['email'], $existingEmails)) {
                        $failed[] = [
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'error' => 'Email already exists',
                        ];
                        continue;
                    }

                    // Create participant
                    $participant = Participant::create([
                        'company_id' => $company->id,
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'unique_token' => $this->generateUniqueToken(),
                    ]);

                    // Create test assignments
                    $assignments = [];
                    foreach ($tests as $test) {
                        $assignments[] = TestAssignment::create([
                            'participant_id' => $participant->id,
                            'test_id' => $test->id,
                            'unique_token' => $this->generateAssignmentToken(),
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                        ]);
                    }

                    // Send assignment emails (queued)
                    $this->emailService->sendAssignmentEmails($assignments);

                    $created[] = $participant;
                    $existingEmails[] = $data['email']; // Track to prevent duplicates in same batch
                } catch (\Exception $e) {
                    $failed[] = [
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'created' => $created,
                'failed' => $failed,
                'total' => count($participantsData),
                'success' => count($created),
                'failed_count' => count($failed),
            ];
        });
    }

    /**
     * Get participants for company with filters.
     */
    public function getParticipantsForCompany(
        Company $company,
        ?string $search = null,
        ?bool $banned = null,
        int $perPage = 15
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        $query = Participant::where('company_id', $company->id)
            ->with(['company:id,name,email', 'testAssignments.test', 'testAssignments' => function ($q) {
                $q->latest();
            }]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($banned !== null) {
            if ($banned) {
                $query->whereNotNull('banned_at');
            } else {
                $query->whereNull('banned_at');
            }
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get all participants with filters (for SuperAdmin).
     */
    public function getAllParticipants(
        ?int $companyId = null,
        ?string $search = null,
        ?bool $banned = null,
        int $perPage = 15
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        $query = Participant::with(['company:id,name,email', 'testAssignments.test', 'testAssignments' => function ($q) {
            $q->latest();
        }]);

        // Filter by company if provided
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($banned !== null) {
            if ($banned) {
                $query->whereNotNull('banned_at');
            } else {
                $query->whereNull('banned_at');
            }
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get participant detail with full history.
     */
    public function getParticipantDetail(Participant $participant): Participant
    {
        return $participant->load([
            'company',
            'testAssignments.test.category',
            'testAssignments.testSessions',
            'testSessions.test',
            'testSessions.result',
        ]);
    }

    /**
     * Ban participant.
     */
    public function banParticipant(Participant $participant, ?string $reason = null): Participant
    {
        $participant->update([
            'banned_at' => now(),
        ]);

        // Update biodata with ban reason if provided
        if ($reason) {
            $biodata = $participant->biodata ?? [];
            $biodata['ban_reason'] = $reason;
            $biodata['banned_at'] = now()->toIso8601String();
            $participant->update(['biodata' => $biodata]);
        }

        return $participant->fresh();
    }

    /**
     * Unban participant.
     */
    public function unbanParticipant(Participant $participant): Participant
    {
        $participant->update([
            'banned_at' => null,
        ]);

        // Remove ban reason from biodata
        $biodata = $participant->biodata ?? [];
        unset($biodata['ban_reason']);
        unset($biodata['banned_at']);
        $participant->update(['biodata' => $biodata]);

        return $participant->fresh();
    }

    /**
     * Update participant.
     */
    public function updateParticipant(
        Participant $participant,
        ?string $name = null,
        ?string $email = null
    ): Participant {
        $updateData = [];

        if ($name !== null) {
            $updateData['name'] = $name;
        }

        if ($email !== null) {
            // Check email uniqueness within company
            if (Participant::where('company_id', $participant->company_id)
                ->where('email', $email)
                ->where('id', '!=', $participant->id)
                ->exists()) {
                throw new \Exception('Email already exists for this company.');
            }
            $updateData['email'] = $email;
        }

        $participant->update($updateData);

        return $participant->fresh();
    }

    /**
     * Delete participant.
     */
    public function deleteParticipant(Participant $participant): bool
    {
        return $participant->delete();
    }

    /**
     * Map Excel headers to our expected format.
     */
    protected function mapExcelHeaders(array $headers): array
    {
        $map = [
            'name' => null,
            'email' => null,
        ];

        foreach ($headers as $index => $header) {
            $headerLower = strtolower(trim($header ?? ''));

            if (in_array($headerLower, ['name', 'nama', 'full name', 'fullname'])) {
                $map['name'] = $index;
            } elseif (in_array($headerLower, ['email', 'e-mail', 'email address'])) {
                $map['email'] = $index;
            }
        }

        return $map;
    }

    /**
     * Get cell value from row by index.
     */
    protected function getCellValue(array $row, ?int $index): ?string
    {
        if ($index === null || ! isset($row[$index])) {
            return null;
        }

        $value = trim($row[$index] ?? '');

        return $value === '' ? null : $value;
    }
}
