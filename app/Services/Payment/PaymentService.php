<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Mail\PaymentVerifiedMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SubscriptionTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

interface PaymentGatewayInterface
{
    public function processPayment(float $amount, array $metadata): array;

    public function verifyPayment(string $paymentId): array;

    public function refundPayment(string $paymentId, ?float $amount = null): array;
}

class ManualPaymentGateway implements PaymentGatewayInterface
{
    public function processPayment(float $amount, array $metadata): array
    {
        // Manual payment doesn't process immediately
        // It requires admin verification
        return [
            'status' => 'pending',
            'payment_id' => null,
            'redirect_url' => null,
        ];
    }

    public function verifyPayment(string $paymentId): array
    {
        // For manual payment, verification is done by admin
        return [
            'status' => 'pending',
            'verified' => false,
        ];
    }

    public function refundPayment(string $paymentId, ?float $amount = null): array
    {
        // Manual refund process
        return [
            'status' => 'pending',
            'refund_id' => null,
        ];
    }
}

class PaymentService
{
    protected PaymentGatewayInterface $gateway;

    public function __construct(?PaymentGatewayInterface $gateway = null)
    {
        // Default to manual payment gateway
        // In future, can inject other gateways (Midtrans, Xendit, etc.)
        $this->gateway = $gateway ?? new ManualPaymentGateway;
    }

    public function createPaymentForTransaction(
        SubscriptionTransaction $transaction,
        ?string $proofFile = null,
        ?string $notes = null
    ): Payment {
        return DB::transaction(function () use ($transaction, $proofFile, $notes) {
            $payment = Payment::create([
                'payable_type' => SubscriptionTransaction::class,
                'payable_id' => $transaction->id,
                'payment_number' => $this->generatePaymentNumber(),
                'amount' => $transaction->amount,
                'method' => 'manual',
                'status' => PaymentStatus::Pending,
                'proof_file' => $proofFile,
                'notes' => $notes,
            ]);

            return $payment;
        });
    }

    public function createPaymentForInvoice(
        Invoice $invoice,
        ?string $proofFile = null,
        ?string $notes = null
    ): Payment {
        return DB::transaction(function () use ($invoice, $proofFile, $notes) {
            $payment = Payment::create([
                'payable_type' => Invoice::class,
                'payable_id' => $invoice->id,
                'payment_number' => $this->generatePaymentNumber(),
                'amount' => $invoice->total_amount,
                'method' => 'manual',
                'status' => PaymentStatus::Pending,
                'proof_file' => $proofFile,
                'notes' => $notes,
            ]);

            return $payment;
        });
    }

    public function createPaymentForPublicTransaction(
        \App\Models\PublicTransaction $transaction,
        ?string $proofFile = null,
        ?string $notes = null
    ): Payment {
        return DB::transaction(function () use ($transaction, $proofFile, $notes) {
            $payment = Payment::create([
                'payable_type' => \App\Models\PublicTransaction::class,
                'payable_id' => $transaction->id,
                'payment_number' => $this->generatePaymentNumber(),
                'amount' => $transaction->total_amount,
                'method' => 'manual',
                'status' => PaymentStatus::Pending,
                'proof_file' => $proofFile,
                'notes' => $notes,
            ]);

            return $payment;
        });
    }

    public function uploadProof(Payment $payment, $file): Payment
    {
        $path = $file->store('payments/proofs', 'public');

        $payment->update([
            'proof_file' => $path,
        ]);

        return $payment->fresh();
    }

    public function verifyPayment(Payment $payment, bool $approved = true, ?string $notes = null): Payment
    {
        return DB::transaction(function () use ($payment, $approved, $notes) {
            if ($approved) {
                $payment->update([
                    'status' => PaymentStatus::Paid,
                    'paid_at' => now(),
                    'notes' => $notes,
                ]);

                // Update payable status
                $this->updatePayableStatus($payment->payable, true);
            } else {
                $payment->update([
                    'status' => PaymentStatus::Failed,
                    'notes' => $notes,
                ]);
            }

            // Send email notification to company admins
            $this->sendPaymentVerificationEmail($payment, $approved);

            return $payment->fresh();
        });
    }

    protected function sendPaymentVerificationEmail(Payment $payment, bool $approved): void
    {
        try {
            // Handle PublicTransaction - send to public user
            if ($payment->payable_type === \App\Models\PublicTransaction::class) {
                $transaction = $payment->payable;
                $user = $transaction->user;

                if ($user && $user->email) {
                    Mail::to($user->email)->send(
                        new PaymentVerifiedMail($user, $payment, $approved)
                    );
                }
                return;
            }

            // Handle Company payments - send to company admins
            $company = $this->getCompanyFromPayment($payment);
            if (! $company) {
                return;
            }

            // Get all company admins
            $admins = $company->admins()->with('user')->get();

            foreach ($admins as $admin) {
                if ($admin->user && $admin->user->email) {
                    Mail::to($admin->user->email)->send(
                        new PaymentVerifiedMail($admin->user, $payment, $approved)
                    );
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail the payment verification
            \Log::error('Failed to send payment verification email: '.$e->getMessage());
        }
    }

    protected function getCompanyFromPayment(Payment $payment): ?Company
    {
        if ($payment->payable_type === SubscriptionTransaction::class) {
            return $payment->payable->companySubscription->company ?? null;
        } elseif ($payment->payable_type === Invoice::class) {
            return $payment->payable->company ?? null;
        } elseif ($payment->payable_type === \App\Models\PublicTransaction::class) {
            // Public transactions don't have company, return null
            return null;
        }

        return null;
    }

    public function cancelPayment(Payment $payment, ?string $notes = null): Payment
    {
        $payment->update([
            'status' => PaymentStatus::Cancelled,
            'notes' => $notes,
        ]);

        return $payment->fresh();
    }

    public function refundPayment(Payment $payment, ?float $amount = null, ?string $notes = null): Payment
    {
        $refundAmount = $amount ?? $payment->amount;

        $payment->update([
            'status' => PaymentStatus::Refunded,
            'notes' => $notes ? ($payment->notes ? $payment->notes."\n\nRefund: {$notes}" : "Refund: {$notes}") : $payment->notes,
        ]);

        // In future, can integrate with gateway refund API
        $this->gateway->refundPayment($payment->payment_number, $refundAmount);

        return $payment->fresh();
    }

    protected function updatePayableStatus(Model $payable, bool $paid): void
    {
        if ($payable instanceof \App\Models\PublicTransaction) {
            if ($paid) {
                $payable->update(['status' => 'completed']);
            } else {
                $payable->update(['status' => 'failed']);
            }
        } elseif ($payable instanceof SubscriptionTransaction) {
            $payable->update([
                'status' => $paid ? TransactionStatus::Completed : TransactionStatus::Failed,
            ]);

            // Activate subscription if transaction is completed
            if ($paid) {
                $payable->refresh();
                if ($payable->status === TransactionStatus::Completed) {
                    $subscription = $payable->companySubscription;
                    if ($subscription && $subscription->status->value === 'pending') {
                        app(\App\Services\Subscription\SubscriptionService::class)
                            ->activateSubscription($subscription);
                    }
                }
            }
        } elseif ($payable instanceof Invoice) {
            if ($paid) {
                app(\App\Services\Subscription\InvoiceService::class)
                    ->markInvoiceAsPaid($payable);
            }
        }
    }

    public function getPaymentsForCompany(Company $company): \Illuminate\Support\Collection
    {
        // Get payments from transactions
        $transactionPayments = Payment::where('payable_type', SubscriptionTransaction::class)
            ->whereHas('payable.companySubscription', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->get();

        // Get payments from invoices
        $invoicePayments = Payment::where('payable_type', Invoice::class)
            ->whereHas('payable', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->get();

        return $transactionPayments->merge($invoicePayments)
            ->sortByDesc('created_at')
            ->values();
    }

    public function getAllPayments(?string $status = null, ?string $method = null, ?int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Payment::with(['payable']);

        if ($status) {
            try {
                $query->where('status', PaymentStatus::from($status));
            } catch (\ValueError $e) {
                // Invalid status, ignore filter
            }
        }

        if ($method) {
            $query->where('method', $method);
        }

        return $query->latest()->paginate($perPage);
    }

    public function verifyPaymentBelongsToCompany(Payment $payment, Company $company): bool
    {
        $companyId = $this->getCompanyIdFromPayment($payment);

        return $companyId === $company->id;
    }

    protected function getCompanyIdFromPayment(Payment $payment): ?int
    {
        if ($payment->payable_type === SubscriptionTransaction::class) {
            return $payment->payable->companySubscription->company_id ?? null;
        } elseif ($payment->payable_type === Invoice::class) {
            return $payment->payable->company_id ?? null;
        }

        return null;
    }

    public function uploadProofWithNotes(Payment $payment, $file, ?string $notes = null): Payment
    {
        $payment = $this->uploadProof($payment, $file);

        if ($notes) {
            $payment->update(['notes' => $notes]);
        }

        return $payment->fresh();
    }

    protected function generatePaymentNumber(): string
    {
        do {
            $number = 'PAY-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -8));
        } while (Payment::where('payment_number', $number)->exists());

        return $number;
    }
}
