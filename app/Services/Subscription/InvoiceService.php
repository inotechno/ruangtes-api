<?php

namespace App\Services\Subscription;

use App\Mail\InvoiceGeneratedMail;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class InvoiceService
{
    public function generateInvoiceForPostPaid(
        CompanySubscription $subscription,
        ?float $taxRate = 0.11
    ): Invoice {
        $price = $subscription->subscriptionPrice;
        $amount = $price->price;
        $taxAmount = $amount * $taxRate;
        $totalAmount = $amount + $taxAmount;

        // Due date is 7 days from now
        $dueDate = now()->addDays(7);

        return DB::transaction(function () use ($subscription, $amount, $taxAmount, $totalAmount, $dueDate, $price) {
            $invoice = Invoice::create([
                'company_id' => $subscription->company_id,
                'company_subscription_id' => $subscription->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'due_date' => $dueDate,
                'status' => 'pending',
                'items' => [
                    [
                        'description' => "Subscription: {$subscription->subscriptionPlan->name} ({$subscription->subscriptionPlan->duration_months} months)",
                        'quantity' => 1,
                        'unit_price' => $amount,
                        'total' => $amount,
                    ],
                ],
            ]);

            $invoice = $invoice->load(['company', 'companySubscription']);

            // Send email notification to company admins
            $this->sendInvoiceGeneratedEmail($invoice);

            return $invoice;
        });
    }

    public function generateInvoiceForExtension(
        CompanySubscription $subscription,
        int $months,
        ?float $taxRate = 0.11
    ): Invoice {
        $price = $subscription->subscriptionPrice;
        // Calculate prorated amount for extension
        $monthlyPrice = $price->price / $subscription->subscriptionPlan->duration_months;
        $amount = $monthlyPrice * $months;
        $taxAmount = $amount * $taxRate;
        $totalAmount = $amount + $taxAmount;

        $dueDate = now()->addDays(7);

        return DB::transaction(function () use ($subscription, $months, $amount, $taxAmount, $totalAmount, $dueDate) {
            $invoice = Invoice::create([
                'company_id' => $subscription->company_id,
                'company_subscription_id' => $subscription->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'due_date' => $dueDate,
                'status' => 'pending',
                'items' => [
                    [
                        'description' => "Subscription Extension: {$months} months",
                        'quantity' => $months,
                        'unit_price' => $amount / $months,
                        'total' => $amount,
                    ],
                ],
            ]);

            $invoice = $invoice->load(['company', 'companySubscription']);

            // Send email notification to company admins
            $this->sendInvoiceGeneratedEmail($invoice);

            return $invoice;
        });
    }

    public function markInvoiceAsPaid(Invoice $invoice): Invoice
    {
        $invoice->update([
            'status' => 'paid',
            'paid_date' => now(),
        ]);

        return $invoice->fresh();
    }

    public function markInvoiceAsOverdue(Invoice $invoice): Invoice
    {
        if ($invoice->isPending() && $invoice->due_date < now()) {
            $invoice->update([
                'status' => 'overdue',
            ]);
        }

        return $invoice->fresh();
    }

    public function getInvoicesForCompany(Company $company, ?string $status = null)
    {
        $query = Invoice::where('company_id', $company->id)
            ->with(['companySubscription.subscriptionPlan', 'companySubscription.subscriptionPrice']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()->get();
    }

    protected function sendInvoiceGeneratedEmail(Invoice $invoice): void
    {
        try {
            $company = $invoice->company;
            if (! $company) {
                return;
            }

            // Get all company admins
            $admins = $company->admins()->with('user')->get();

            foreach ($admins as $admin) {
                if ($admin->user && $admin->user->email) {
                    Mail::to($admin->user->email)->send(
                        new InvoiceGeneratedMail($admin->user, $invoice)
                    );
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail the invoice generation
            \Log::error('Failed to send invoice generated email: '.$e->getMessage());
        }
    }

    public function verifyInvoiceBelongsToCompany(Invoice $invoice, Company $company): bool
    {
        return $invoice->company_id === $company->id;
    }

    protected function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -8));
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }
}
