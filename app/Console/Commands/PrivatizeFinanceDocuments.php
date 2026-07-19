<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Documents\PaymentDocumentStorage;
use Illuminate\Console\Command;

class PrivatizeFinanceDocuments extends Command
{
    protected $signature = 'finance:privatize-documents
        {--dry-run : Report legacy public documents without moving them}';

    protected $description =
        'Move receipt and payment statement PDFs from public to private storage';

    public function handle(PaymentDocumentStorage $documents): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $found = 0;
        $moved = 0;

        Payment::query()
            ->withTrashed()
            ->select([
                'id',
                'receipt_pdf',
                'statement_pdf',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($payments) use (
                $documents,
                $dryRun,
                &$found,
                &$moved,
            ): void {
                foreach ($payments as $payment) {
                    foreach (
                        [$payment->receipt_pdf, $payment->statement_pdf]
                        as $path
                    ) {
                        if (
                            blank($path)
                            || ! $documents->existsOnLegacyPublic($path)
                        ) {
                            continue;
                        }

                        $found++;

                        if (! $dryRun) {
                            $documents->privatize($path);
                            $moved++;
                        }
                    }
                }
            });

        if ($dryRun) {
            $this->info(
                "Dry run complete: {$found} legacy public document(s) found."
            );

            return self::SUCCESS;
        }

        $this->info(
            "Privatization complete: {$moved} document(s) moved."
        );

        return self::SUCCESS;
    }
}
