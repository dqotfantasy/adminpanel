<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\ContestCategory;
use App\Models\ContestTemplate;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        Redis::connection()->client()->flushDB();

        Admin::query()->firstOrCreate([
            'email' => 'admin@gmail.com'
        ], [
            'name' => 'Admin',
            'password' => Hash::make('admin'),
            'pin' => 1234
        ]);

        Setting::query()->insert([
            [
                'key' => 'name',
                'value' => 'Lions11'
            ],
            [
                'key' => 'email',
                'value' => 'info@lions11.com'
            ],
            [
                'key' => 'phone',
                'value' => '9999999999'
            ],
            [
                'key' => 'teams',
                'value' => json_encode([
                    'min_players' => 11,
                    'max_players' => 11,
                    'max_players_per_team' => 7,
                    'min_wicket_keepers' => 1,
                    'max_wicket_keepers' => 4,
                    'min_batsmen' => 3,
                    'max_batsmen' => 6,
                    'min_all_rounders' => 1,
                    'max_all_rounders' => 4,
                    'min_bowlers' => 3,
                    'max_bowlers' => 4,
                ])
            ],
            [
                'key' => 'entity_sport',
                'value' => ''
            ],
            [
                'key' => 'withdraw',
                'value' => json_encode([
                    'min_amount' => 50,
                    'max_amount' => 10000,
                ])
            ],
            [
                'key' => 'referral_price',
                'value' => 100
            ],
            [
                'key' => 'tds_deduction',
                'value' => 5.5
            ],
            [
                'key' => 'personal_contest_commission',
                'value' => 5.5
            ],
            [
                'key' => 'private_contest',
                'value' => json_encode([
                    'min_contest_size' => 2,
                    'max_contest_size' => 10000,
                    'min_entry_fee' => 5,
                    'max_entry_fee' => 10000,
                    'min_allow_multi' => 1,
                    'max_allow_multi' => 11,
                    'commission_value' => 10,
                    'commission_on_fee' => 5,

                ])
            ],
            [
                'key' => 'level_limit',
                'value' => json_encode([
                    'limit' => 20,
                    'bonus' => 5
                ])
            ],
            [
                'key' => 'razorpay',
                'value' => json_encode([
                    'access_key' => 'rzp_test_JbgazDtjxYOoFZ',
                    'secret_key' => 'tNWsMzMiRtnCf57gIRoqIO2x',
                    'account_number' => '123',
                    'webhook_secret' => '12345',
                ])
            ]
        ]);

        $contestCategory = ContestCategory::query()->create([
            'name' => 'H2H',
            'tagline' => 'H2H'
        ]);

        ContestTemplate::query()->create([
            'contest_category_id' => $contestCategory->id,
            'name' => 'H2H',
            'description' => null,
            'total_teams' => 2,
            'entry_fee' => 25,
            'max_team' => 1,
            'prize' => 50,
            'winner_percentage' => 50,
            'is_confirmed' => 0,
            'prize_breakup' => [
                [
                    'from' => 1,
                    'to' => 1,
                    'percentage' => 100,
                    'prize' => 50
                ]
            ],
            'auto_add' => 1,
            'auto_create_on_full' => 1,
            'commission' => 0,
            'type' => CONTEST_TYPE[1],
            'discount' => 0,
            'is_mega_contest' => 0,

        ]);

        $contestCategory = ContestCategory::query()->create([
            'name' => 'SUPER10',
            'tagline' => 'SP10'
        ]);

        ContestTemplate::query()->create([
            'contest_category_id' => $contestCategory->id,
            'name' => 'SUPER10',
            'description' => null,
            'total_teams' => 10,
            'entry_fee' => 20,
            'max_team' => 1,
            'prize' => 200,
            'winner_percentage' => 30,
            'is_confirmed' => 0,
            'prize_breakup' => [
                [
                    'from' => 1,
                    'to' => 1,
                    'percentage' => 50,
                    'prize' => 100
                ],
                [
                    'from' => 2,
                    'to' => 3,
                    'percentage' => 25,
                    'prize' => 50
                ]
            ],
            'auto_add' => 1,
            'auto_create_on_full' => 1,
            'commission' => 0,
            'type' => CONTEST_TYPE[1],
            'discount' => 0,
            'is_mega_contest' => 0,
        ]);

        $razorpay = [
            'access_key' => 'rzp_test_JbgazDtjxYOoFZ',
            'secret_key' => 'tNWsMzMiRtnCf57gIRoqIO2x',
            'account_number' => '123',
            'webhook_secret' => '123',
        ];

        $version = [
            'name' => '1.0.0',
            'code' => 1,
            'force_update' => false,
            'description' => 'Bug fixes and improvements.',
            'url' => ''
        ];

        Redis::set('razorpay', json_encode($razorpay));
        Redis::set('version', json_encode($version));
    }
}
