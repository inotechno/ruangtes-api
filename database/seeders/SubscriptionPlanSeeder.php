<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPrice;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 3 Bulan Plan
        $plan3Months = SubscriptionPlan::updateOrCreate(
            ['name' => 'Paket 3 Bulan'],
            [
                'duration_months' => 3,
                'description' => 'Paket berlangganan untuk 3 bulan',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $plan3MonthsPrices = [
            ['user_quota' => 5, 'price' => 500000, 'price_per_additional_user' => 50000],
            ['user_quota' => 10, 'price' => 900000, 'price_per_additional_user' => 45000],
            ['user_quota' => 30, 'price' => 2400000, 'price_per_additional_user' => 40000],
            ['user_quota' => 0, 'price' => 0, 'price_per_additional_user' => 50000], // Custom
        ];

        foreach ($plan3MonthsPrices as $price) {
            SubscriptionPrice::updateOrCreate(
                [
                    'subscription_plan_id' => $plan3Months->id,
                    'user_quota' => $price['user_quota'],
                ],
                [
                    'price' => $price['price'],
                    'price_per_additional_user' => $price['price_per_additional_user'],
                    'is_active' => true,
                ]
            );
        }

        // 6 Bulan Plan
        $plan6Months = SubscriptionPlan::updateOrCreate(
            ['name' => 'Paket 6 Bulan'],
            [
                'duration_months' => 6,
                'description' => 'Paket berlangganan untuk 6 bulan',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $plan6MonthsPrices = [
            ['user_quota' => 30, 'price' => 4500000, 'price_per_additional_user' => 75000],
            ['user_quota' => 60, 'price' => 8400000, 'price_per_additional_user' => 70000],
            ['user_quota' => 100, 'price' => 13000000, 'price_per_additional_user' => 65000],
            ['user_quota' => 0, 'price' => 0, 'price_per_additional_user' => 70000], // Custom
        ];

        foreach ($plan6MonthsPrices as $price) {
            SubscriptionPrice::updateOrCreate(
                [
                    'subscription_plan_id' => $plan6Months->id,
                    'user_quota' => $price['user_quota'],
                ],
                [
                    'price' => $price['price'],
                    'price_per_additional_user' => $price['price_per_additional_user'],
                    'is_active' => true,
                ]
            );
        }

        // 12 Bulan Plan
        $plan12Months = SubscriptionPlan::updateOrCreate(
            ['name' => 'Paket 12 Bulan'],
            [
                'duration_months' => 12,
                'description' => 'Paket berlangganan untuk 12 bulan (1 tahun)',
                'is_active' => true,
                'sort_order' => 3,
            ]
        );

        $plan12MonthsPrices = [
            ['user_quota' => 100, 'price' => 24000000, 'price_per_additional_user' => 120000],
            ['user_quota' => 500, 'price' => 110000000, 'price_per_additional_user' => 110000],
            ['user_quota' => 1000, 'price' => 200000000, 'price_per_additional_user' => 100000],
            ['user_quota' => 0, 'price' => 0, 'price_per_additional_user' => 110000], // Custom
        ];

        foreach ($plan12MonthsPrices as $price) {
            SubscriptionPrice::updateOrCreate(
                [
                    'subscription_plan_id' => $plan12Months->id,
                    'user_quota' => $price['user_quota'],
                ],
                [
                    'price' => $price['price'],
                    'price_per_additional_user' => $price['price_per_additional_user'],
                    'is_active' => true,
                ]
            );
        }
    }
}
