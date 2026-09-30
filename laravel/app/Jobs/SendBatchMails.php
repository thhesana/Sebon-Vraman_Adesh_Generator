<?php

namespace App\Jobs;

use App\Services\MailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends the per-employee travel notification mails for a TADA batch.
 * Dispatch with SendBatchMails::dispatchAfterResponse('Domestic'|'International', $batchId)
 * so the user's request finishes before the (slow) SMTP work starts.
 */
class SendBatchMails implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public string $source, public string $batchId)
    {
    }

    public function handle(MailService $mail): void
    {
        match ($this->source) {
            'Domestic'      => $mail->sendDomesticBatch($this->batchId),
            'International' => $mail->sendInternationalBatch($this->batchId),
        };
    }
}
