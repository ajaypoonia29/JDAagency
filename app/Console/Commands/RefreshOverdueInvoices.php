<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Finance\InvoiceLedgerService;
use Illuminate\Console\Command;

class RefreshOverdueInvoices extends Command
{
    protected $signature = 'finance:refresh-overdue-invoices';

    protected $description =
        'Refresh invoice statuses that depend on due dates and balances.';

    public function handle(InvoiceLedgerService $ledgers): int
    {
        $updated = $ledgers->refreshOpenInvoices();

        $this->info(sprintf(
            'Invoice status refresh complete: %d status change(s).',
            $updated,
        ));

        return self::SUCCESS;
    }
}
