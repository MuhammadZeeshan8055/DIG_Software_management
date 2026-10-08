<?php

namespace App\Console\Commands;

use App\Support\SendDueVisitorReminders;
use Illuminate\Console\Command;

class SendVisitorReminders extends Command
{
    protected $signature = 'visitors:send-reminders';

    protected $description = 'Notify staff about due visitor reminders';

    public function handle(): int
    {
        $sent = SendDueVisitorReminders::run();

        $this->info('Sent '.$sent.' reminder notification(s).');

        return self::SUCCESS;
    }
}
