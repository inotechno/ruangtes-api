<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\CompanySubscription;
use App\Services\Subscription\InvoiceService;
use Illuminate\Console\Command;

class GeneratePostPaidInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-post-paid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invoices for post-paid subscriptions that have expired';

    public function __construct(
        protected InvoiceService $invoiceService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting post-paid invoice generation process...');

        // Find post-paid subscriptions that expired today or yesterday (to catch any missed)
        $expiredPostPaidSubscriptions = CompanySubscription::where('billing_type', 'post_paid')
            ->where('status', SubscriptionStatus::Active)
            ->where('expires_at', '<=', now())
            ->where('expires_at', '>=', now()->subDay())
            ->get();

        if ($expiredPostPaidSubscriptions->isEmpty()) {
            $this->info('No post-paid subscriptions to generate invoices for.');

            return Command::SUCCESS;
        }

        $this->info("Found {$expiredPostPaidSubscriptions->count()} post-paid subscription(s) to generate invoices for.");

        $generatedCount = 0;
        foreach ($expiredPostPaidSubscriptions as $subscription) {
            try {
                // Check if invoice already exists for this subscription
                $existingInvoice = $subscription->invoices()
                    ->where('status', 'pending')
                    ->whereDate('created_at', '>=', $subscription->expires_at->copy()->subDay())
                    ->first();

                if ($existingInvoice) {
                    $this->line("Invoice already exists for subscription ID: {$subscription->id} - Invoice: {$existingInvoice->invoice_number}");
                    continue;
                }

                $invoice = $this->invoiceService->generateInvoiceForPostPaid($subscription);
                $generatedCount++;
                $this->line("Generated invoice {$invoice->invoice_number} for subscription ID: {$subscription->id} - Company: {$subscription->company->name}");
            } catch (\Exception $e) {
                $this->error("Failed to generate invoice for subscription ID: {$subscription->id} - {$e->getMessage()}");
            }
        }

        $this->info("Successfully generated {$generatedCount} invoice(s).");

        return Command::SUCCESS;
    }
}
