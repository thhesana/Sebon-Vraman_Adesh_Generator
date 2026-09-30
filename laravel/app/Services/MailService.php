<?php

namespace App\Services;

use App\Models\BatchMailQueue;
use App\Models\DomesticTada;
use App\Models\InternationalTada;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends the per-employee travel notification mails for a TADA batch through
 * PHPMailer + Office 365 SMTP (XOAUTH2, client-credentials flow).
 *
 * ClientID / ClientSecret / TenantID are read from MicrosoftOAuthConfig.
 * Replaces the legacy domestic_mail_notifier.py / international_mail_notifier.py.
 */
class MailService
{
    public function sendDomesticBatch(string $batchId): bool
    {
        $rows = $this->domesticRows($batchId);

        return $this->sendBatch(
            $batchId,
            'Domestic',
            $rows,
            fn ($emp) => "Domestic Travel Notification - Batch {$batchId} | {$emp->District_name}",
            fn ($emp) => View::make('emails.domestic_batch', ['emp' => $emp])->render(),
            fn ($emp) => "Domestic travel notification for {$emp->EmpName}: {$emp->District_name}, "
                . substr((string) $emp->domestic_travelDateStart, 0, 10) . ' to ' . substr((string) $emp->domestic_travelDateEnd, 0, 10) . '.',
            fn ($emp) => $emp->EmpName
        );
    }

    public function sendInternationalBatch(string $batchId): bool
    {
        $rows = $this->internationalRows($batchId);

        return $this->sendBatch(
            $batchId,
            'International',
            $rows,
            fn ($emp) => "Travel Notification - Batch {$batchId} | {$emp->Country}",
            fn ($emp) => View::make('emails.international_batch', ['emp' => $emp])->render(),
            fn ($emp) => "International travel notification for {$emp->EmployeeName}: {$emp->Country}, "
                . substr((string) $emp->travelDateStart, 0, 10) . ' to ' . substr((string) $emp->travelDateEnd, 0, 10) . '.',
            fn ($emp) => $emp->EmployeeName
        );
    }

    /** @return array<int,object> */
    private function domesticRows(string $batchId): array
    {
        return DomesticTada::query()
            ->from('DomesticTada as dt')
            ->leftJoin('Employee_Information as e', 'dt.EmpPersonalCode', '=', 'e.EmpPersonalCode')
            ->leftJoin('DistrictMaster as dm', 'dt.District_id', '=', 'dm.District_id')
            ->leftJoin('DomesticTadaDefinerMasterBylevel as dtd', 'e.LevelName', '=', 'dtd.DomesticTadaDefinerMasterBylevel_name')
            ->where('dt.domestic_Batch_id', $batchId)
            ->select([
                'dt.domestic_tada_id', 'dt.EmpPersonalCode', 'e.EmpName', 'e.Gender', 'e.Email', 'e.Designation', 'e.LevelName',
                'dm.District_name',
                'dt.domestic_travel_objective', 'dt.domestic_travelDateStart', 'dt.domestic_travelDateEnd',
                'dt.domestic_totalday', 'dt.domestic_tada', 'dtd.tadaInNepali',
            ])
            ->selectRaw("CASE WHEN dt.domestic_isTwentyPercentExtra = 1 THEN 'Yes' ELSE 'No' END AS isTwentyPercentExtra")
            ->toBase()
            ->get()
            ->all();
    }

    /** @return array<int,object> */
    private function internationalRows(string $batchId): array
    {
        // Travel days count both end days, minus half a day; countries flagged extra33percent get +33%.
        $days = '(DATEDIFF(DAY, i.travelDateStart, i.travelDateEnd) + 1 - 0.5)';

        return InternationalTada::query()
            ->from('International_tada as i')
            ->leftJoin('CountryMaster as c', 'i.Country_id', '=', 'c.Country_id')
            ->leftJoin('CityMaster as ci', 'i.City_id', '=', 'ci.City_id')
            ->leftJoin('TadaDefinerMasterBylevel as t', 'i.TadaDefinerMasterBylevel_id', '=', 't.TadaDefinerMasterBylevel_id')
            ->leftJoin('Employee_Information as emp', 'i.EmpPersonalCode', '=', 'emp.EmpPersonalCode')
            ->where('i.Batch_id', $batchId)
            ->select([
                'i.Batch_id', 'i.EmpPersonalCode', 'emp.EmpName as EmployeeName', 'emp.Gender', 'emp.Email',
                'c.Country_name as Country', 'ci.City_name as City', 'i.travel_objective',
                'i.travelDateStart', 'i.travelDateEnd',
            ])
            ->selectRaw("CAST({$days} AS DECIMAL(5,2)) AS totalday")
            ->selectRaw("CASE WHEN c.extra33percent_country = 1 THEN (t.tadaInUSD * 1.33) * {$days} ELSE t.tadaInUSD * {$days} END AS tadaInUSD_Final")
            ->selectRaw("CASE WHEN i.DressAllowance = 1 THEN 'Yes' ELSE 'No' END AS DressAllowance")
            ->toBase()
            ->get()
            ->all();
    }

    /**
     * @param  array<int,object>  $rows
     */
    private function sendBatch(string $batchId, string $source, array $rows, callable $subject, callable $body, callable $alt, callable $displayName): bool
    {
        set_time_limit(0);

        $config = $this->oauthConfig();
        if (! $config) {
            $this->log("Batch {$batchId}: no OAuth config found");

            return false;
        }

        $pending = BatchMailQueue::where('SourceTable', $source)
            ->where('mailSentFlag', 0)
            ->where('Batch_id', $batchId)
            ->exists();
        if (! $pending) {
            $this->log("Batch {$batchId}: no pending BatchMailQueue row");

            return false;
        }

        $senderEmail = config('mail.sender_email');

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host          = 'smtp.office365.com';
            $mail->Port          = 587;
            $mail->SMTPSecure    = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAuth      = true;
            $mail->AuthType      = 'XOAUTH2';
            $mail->SMTPKeepAlive = true;
            $mail->setOAuth(new MicrosoftClientCredentialsProvider(
                $config->ClientID, $config->ClientSecret, $config->TenantID, $senderEmail
            ));
            $mail->Username = $senderEmail;
            $mail->CharSet  = 'UTF-8';
            $mail->isHTML(true);
            $mail->setFrom($senderEmail, config('mail.sender_name'));
        } catch (Exception $e) {
            $this->log("Batch {$batchId}: mailer setup failed: " . $e->getMessage());

            return false;
        }

        $sent = 0;
        $failed = 0;
        foreach ($rows as $emp) {
            if (empty($emp->Email)) {
                $failed++;
                $this->log("Batch {$batchId}: {$emp->EmpPersonalCode} has no email");

                continue;
            }

            try {
                $mail->clearAddresses();
                $mail->addAddress($emp->Email, (string) $displayName($emp));
                $mail->Subject = $subject($emp);
                $mail->Body    = $body($emp);
                $mail->AltBody = $alt($emp);
                $mail->send();
                $sent++;
                $this->log("Batch {$batchId}: sent to {$emp->Email}");
            } catch (MailException $e) {
                $failed++;
                $this->log("Batch {$batchId}: FAILED for {$emp->Email}: " . $mail->ErrorInfo);
            } catch (Exception $e) {
                $failed++;
                $this->log("Batch {$batchId}: FAILED for {$emp->Email}: " . $e->getMessage());
            }
        }
        $mail->smtpClose();

        // Mark done only when nothing failed so a failed batch can be retried.
        if ($sent > 0 && $failed === 0) {
            BatchMailQueue::where('Batch_id', $batchId)->where('SourceTable', $source)->update(['mailSentFlag' => 1]);
        }

        $this->log("Batch {$batchId} done. Sent: {$sent}, Failed: {$failed}");

        return $failed === 0;
    }

    private function oauthConfig(): ?object
    {
        try {
            $table = config('mail.oauth_config_table');
            $rows  = DB::select("SELECT TOP 1 ClientID, ClientSecret, TenantID FROM {$table} ORDER BY ConfigID DESC");

            return $rows->first();
        } catch (Exception $e) {
            $this->log('Could not read MicrosoftOAuthConfig: ' . $e->getMessage());

            return null;
        }
    }

    private function log(string $message): void
    {
        Log::channel('mail')->info($message);
    }
}
