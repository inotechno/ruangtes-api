<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\CompanySubscription;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire subscriptions that have passed their expiry date';

    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting subscription expiration process...');

        $expiredSubscriptions = CompanySubscription::where('status', SubscriptionStatus::Active)
            ->where('expires_at', '<=', now())
            ->get();

        if ($expiredSubscriptions->isEmpty()) {
            $this->info('No subscriptions to expire.');

            return Command::SUCCESS;
        }

        $this->info("Found {$expiredSubscriptions->count()} subscription(s) to expire.");

        $expiredCount = 0;
        foreach ($expiredSubscriptions as $subscription) {
            try {
                $this->subscriptionService->expireSubscription($subscription);
                $expiredCount++;
                $this->line("Expired subscription ID: {$subscription->id} for company: {$subscription->company->name}");
            } catch (\Exception $e) {
                $this->error("Failed to expire subscription ID: {$subscription->id} - {$e->getMessage()}");
            }
        }

        $this->info("Successfully expired {$expiredCount} subscription(s).");

        return Command::SUCCESS;
    }
}
