<?php

namespace Database\Seeders;

use App\Models\ReceivingAccount;
use Illuminate\Database\Seeder;

class ReceivingAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (ReceivingAccount::query()->exists()) {
            return;
        }

        $accounts = [
            ['method' => 'BANK', 'name' => 'HBL Main Account'],
            ['method' => 'BANK', 'name' => 'Meezan Business Account'],
            ['method' => 'CASH', 'name' => 'Office Cash'],
            ['method' => 'EASYPAISA', 'name' => 'Easypaisa — 0300 1234567'],
            ['method' => 'JAZZCASH', 'name' => 'JazzCash — 0300 7654321'],
            ['method' => 'CARD', 'name' => 'POS Terminal'],
        ];

        foreach ($accounts as $account) {
            ReceivingAccount::create([
                ...$account,
                'is_active' => true,
            ]);
        }
    }
}
