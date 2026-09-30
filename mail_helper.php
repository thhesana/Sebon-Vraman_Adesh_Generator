<?php
// Mail helper: PHPMailer + Office 365 SMTP using OAuth2 (XOAUTH2, client-credentials flow).
// ClientID / ClientSecret / TenantID come from [Employee Insurance Detail ].[dbo].[MicrosoftOAuthConfig].
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\OAuthTokenProvider;

const MAIL_SENDER_EMAIL = 'hr@sebon.gov.np'; // Must be the mailbox the Azure app is allowed to send as
const MAIL_SENDER_NAME  = 'HR Section, Securities Board of Nepal';
const MAIL_LOG_FILE     = __DIR__ . '/mail_execution.log';

function mailLog($message) {
    file_put_contents(MAIL_LOG_FILE, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}

// Client-credentials token provider for PHPMailer's XOAUTH2
class MicrosoftClientCredentialsProvider implements OAuthTokenProvider {
    private $clientId, $clientSecret, $tenantId, $email, $token = null;

    public function __construct($clientId, $clientSecret, $tenantId, $email) {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tenantId     = $tenantId;
        $this->email        = $email;
    }

    private function fetchToken() {
        $ch = curl_init("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope'         => 'https://outlook.office365.com/.default',
                'grant_type'    => 'client_credentials',
            ]),
        ]);
        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) throw new Exception("OAuth token request failed: $error");
        $data = json_decode($response, true);
        if (empty($data['access_token'])) {
            throw new Exception('OAuth token error: ' . ($data['error_description'] ?? $response));
        }
        return $data['access_token'];
    }

    public function getOauth64() {
        if ($this->token === null) $this->token = $this->fetchToken();
        return base64_encode("user={$this->email}\001auth=Bearer {$this->token}\001\001");
    }
}

function getMicrosoftOAuthConfig($conn) {
    $sql  = "SELECT TOP 1 ClientID, ClientSecret, TenantID
             FROM [Employee Insurance Detail ].[dbo].[MicrosoftOAuthConfig]
             ORDER BY ConfigID DESC";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        mailLog('Could not read MicrosoftOAuthConfig: ' . print_r(sqlsrv_errors(), true));
        return null;
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    return $row ?: null;
}

function mailFmtDate($v) {
    return $v instanceof DateTimeInterface ? $v->format('Y-m-d') : htmlspecialchars((string)$v);
}

function mailRow($label, $value, $shaded) {
    $bg = $shaded ? '#f8f9fa' : '#ffffff';
    $td = "padding: 10px 14px; font-family: 'Times New Roman', Times, serif; font-size: 15px; border: 1px solid #dee2e6; background-color: $bg;";
    return "<tr><td style=\"$td color: #495057; width: 45%;\">$label</td><td style=\"$td color: #2c3e50;\">$value</td></tr>";
}

function mailSection($title) {
    return "<tr><td colspan=\"2\" style=\"background-color: #28a745; color: #ffffff; padding: 12px 14px; font-family: 'Times New Roman', Times, serif; font-size: 16px; border: 1px solid #28a745;\">$title</td></tr>";
}

function buildDomesticMailBody($emp) {
    $h          = fn($v) => htmlspecialchars((string)$v);
    $salutation = ($emp['Gender'] ?? '') === 'Male' ? 'Sir' : "Ma'am";
    $font       = "font-family: 'Times New Roman', Times, serif;";

    $rows  = mailSection('👤 Employee Information');
    $rows .= mailRow('Designation', $h($emp['Designation']), false);
    $rows .= mailRow('Level', $h($emp['LevelName']), true);
    $rows .= mailSection('✈️ Travel Information');
    $rows .= mailRow('District', $h($emp['District_name']), false);
    $rows .= mailRow('Travel Objective', $h($emp['domestic_travel_objective']), true);
    $rows .= mailSection('📅 Duration &amp; Allowances');
    $rows .= mailRow('Travel Start Date', mailFmtDate($emp['domestic_travelDateStart']), false);
    $rows .= mailRow('Travel End Date', mailFmtDate($emp['domestic_travelDateEnd']), true);
    $rows .= mailRow('Total Days', $h($emp['domestic_totalday']) . ' days', false);
    $rows .= mailRow('Daily TADA Rate', 'NPR ' . number_format((float)$emp['tadaInNepali'], 2), true);
    $rows .= mailRow('Total TADA Amount', 'NPR ' . number_format((float)$emp['domestic_tada'], 2), false);
    $rows .= mailRow('20% Extra Allowance', $h($emp['isTwentyPercentExtra']), true);

    return "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"></head>
<body style=\"$font margin: 0; padding: 0; background-color: #f4f4f4;\">
<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"background-color: #f4f4f4; padding: 20px;\"><tr><td align=\"center\">
<table width=\"650\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"background-color: #ffffff; border-radius: 12px; overflow: hidden;\">
<tr><td style=\"padding: 35px 30px;\">
<p style=\"$font font-size: 15px; margin: 0 0 20px 0; color: #2c3e50;\">Dear {$h($emp['EmpName'])} $salutation,</p>
<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin: 20px 0;\"><tr>
<td style=\"background-color: #d4edda; border-left: 5px solid #28a745; padding: 15px; border-radius: 5px;\">
<p style=\"$font margin: 0; font-size: 14px; color: #155724;\">Your Travel Details are: </p></td></tr></table>
<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"1\" style=\"margin: 25px 0; border-collapse: collapse; border: 1px solid #dee2e6;\">$rows</table>
<p style=\"$font margin: 25px 0 0 0; font-size: 15px; color: #2c3e50;\">This module has also been successfully integrated into the SEBON MIS (<a href=\"http://10.10.0.199/sebonmis/index.php\" style=\"color: #28a745; text-decoration: none;\">http://10.10.0.199/sebonmis/index.php</a>) under the 'भ्रमण आदेश' tab.</p>
<p style=\"$font margin: 25px 0 0 0; font-size: 15px; color: #2c3e50;\">Regards,<br>HR Section<br>Securities Board of Nepal</p>
</td></tr>
<tr><td style=\"background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 3px solid #28a745;\">
<p style=\"$font margin: 0; font-size: 13px; color: #6c757d;\"><em>** This is an automated mail generated from the application. Please do not reply to this email.</em></p>
</td></tr></table></td></tr></table></body></html>";
}

// Sends one mail per employee row returned by $empSql for a batch, then marks the BatchMailQueue row as sent.
function sendBatchMails($conn, $batchId, $source, $empSql, callable $subjectFn, callable $bodyFn, callable $altFn) {
    set_time_limit(0);

    $config = getMicrosoftOAuthConfig($conn);
    if (!$config) { mailLog("Batch $batchId: no OAuth config found"); return false; }

    $queueStmt = sqlsrv_query($conn,
        "SELECT Queue_id FROM BatchMailQueue WHERE SourceTable = ? AND mailSentFlag = 0 AND Batch_id = ?",
        [$source, $batchId]);
    if ($queueStmt === false || !sqlsrv_fetch_array($queueStmt)) {
        mailLog("Batch $batchId: no pending BatchMailQueue row");
        return false;
    }
    sqlsrv_free_stmt($queueStmt);

    $empStmt = sqlsrv_query($conn, $empSql, [$batchId]);
    if ($empStmt === false) { mailLog("Batch $batchId: employee query failed"); return false; }

    $mail = new PHPMailer(true);
    try {
        $provider = new MicrosoftClientCredentialsProvider(
            $config['ClientID'], $config['ClientSecret'], $config['TenantID'], MAIL_SENDER_EMAIL);

        $mail->isSMTP();
        $mail->Host          = 'smtp.office365.com';
        $mail->Port          = 587;
        $mail->SMTPSecure    = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAuth      = true;
        $mail->AuthType      = 'XOAUTH2';
        $mail->SMTPKeepAlive = true;
        $mail->setOAuth($provider);
        $mail->Username      = MAIL_SENDER_EMAIL; // XOAUTH2 uses the OAuth token, this is the mailbox identity
        $mail->CharSet       = 'UTF-8';
        $mail->isHTML(true);
        $mail->setFrom(MAIL_SENDER_EMAIL, MAIL_SENDER_NAME);
    } catch (Exception $e) {
        mailLog("Batch $batchId: mailer setup failed: " . $e->getMessage());
        return false;
    }

    $sent = 0; $failed = 0;
    while ($emp = sqlsrv_fetch_array($empStmt, SQLSRV_FETCH_ASSOC)) {
        if (empty($emp['Email'])) { $failed++; mailLog("Batch $batchId: {$emp['EmpPersonalCode']} has no email"); continue; }
        try {
            $mail->clearAddresses();
            $mail->addAddress($emp['Email'], (string)($emp['EmpName'] ?? $emp['EmployeeName'] ?? ''));
            $mail->Subject = $subjectFn($emp);
            $mail->Body    = $bodyFn($emp);
            $mail->AltBody = $altFn($emp);
            $mail->send();
            $sent++;
            mailLog("Batch $batchId: sent to {$emp['Email']}");
        } catch (MailException $e) {
            $failed++;
            mailLog("Batch $batchId: FAILED for {$emp['Email']}: " . $mail->ErrorInfo);
        }
    }
    sqlsrv_free_stmt($empStmt);
    $mail->smtpClose();

    // Mark done only when nothing failed so a failed batch can be retried
    if ($sent > 0 && $failed === 0) {
        sqlsrv_query($conn,
            "UPDATE BatchMailQueue SET mailSentFlag = 1 WHERE Batch_id = ? AND SourceTable = ?", [$batchId, $source]);
    }
    mailLog("Batch $batchId done. Sent: $sent, Failed: $failed");
    return $failed === 0;
}

function sendDomesticBatchMails($conn, $batchId) {
    $empSql = "
        SELECT dt.domestic_tada_id, dt.EmpPersonalCode, e.EmpName, e.Gender, e.Email, e.Designation, e.LevelName,
               dm.District_name,
               CASE WHEN dt.domestic_isTwentyPercentExtra = 1 THEN 'Yes' ELSE 'No' END AS isTwentyPercentExtra,
               dt.domestic_travel_objective, dt.domestic_travelDateStart, dt.domestic_travelDateEnd,
               dt.domestic_totalday, dt.domestic_tada, dtd.tadaInNepali
        FROM DomesticTada dt
        LEFT JOIN Employee_Information e ON dt.EmpPersonalCode = e.EmpPersonalCode
        LEFT JOIN DistrictMaster dm ON dt.District_id = dm.District_id
        LEFT JOIN DomesticTadaDefinerMasterBylevel dtd ON e.LevelName = dtd.DomesticTadaDefinerMasterBylevel_name
        WHERE dt.domestic_Batch_id = ?";

    return sendBatchMails($conn, $batchId, 'Domestic', $empSql,
        fn($emp) => "Domestic Travel Notification - Batch $batchId | {$emp['District_name']}",
        'buildDomesticMailBody',
        fn($emp) => "Domestic travel notification for {$emp['EmpName']}: {$emp['District_name']}, "
                  . mailFmtDate($emp['domestic_travelDateStart']) . ' to ' . mailFmtDate($emp['domestic_travelDateEnd']) . '.');
}

function buildInternationalMailBody($emp) {
    $h          = fn($v) => htmlspecialchars((string)$v);
    $salutation = ($emp['Gender'] ?? '') === 'Male' ? 'Sir' : "Ma'am";
    $font       = "font-family: 'Times New Roman', Times, serif;";

    $rows  = mailSection('Travel Information');
    $rows .= mailRow('Country', $h($emp['Country']), false);
    $rows .= mailRow('City', $h($emp['City']), true);
    $rows .= mailRow('Travel Objective', $h($emp['travel_objective']), false);
    $rows .= mailSection('Duration &amp; Allowances');
    $rows .= mailRow('Travel Start Date', mailFmtDate($emp['travelDateStart']), false);
    $rows .= mailRow('Travel End Date', mailFmtDate($emp['travelDateEnd']), true);
    $rows .= mailRow('Total Days', $h($emp['totalday']) . ' days', false);
    $rows .= mailRow('TADA Amount', '$' . number_format((float)$emp['tadaInUSD_Final'], 2) . ' USD', true);
    $rows .= mailRow('Dress Allowance', $h($emp['DressAllowance']), false);

    return "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"></head>
<body style=\"$font margin: 0; padding: 0; background-color: #f4f4f4;\">
<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"background-color: #f4f4f4; padding: 20px;\"><tr><td align=\"center\">
<table width=\"650\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"background-color: #ffffff; border-radius: 12px; overflow: hidden;\">
<tr><td style=\"background-color: #0066cc; color: #ffffff; padding: 15px; text-align: center; $font\"><h2 style=\"margin: 0;\">International Travel Notification</h2></td></tr>
<tr><td style=\"padding: 35px 30px;\">
<p style=\"$font font-size: 15px; margin: 0 0 20px 0; color: #2c3e50;\">Dear <strong>{$h($emp['EmployeeName'])}</strong> $salutation,</p>
<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin: 20px 0;\"><tr>
<td style=\"background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 12px;\">
<p style=\"$font margin: 0; font-size: 14px; color: #856404;\">Your Travel Details are: </p></td></tr></table>
<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"1\" style=\"margin: 25px 0; border-collapse: collapse; border: 1px solid #dee2e6;\">$rows</table>
<p style=\"$font margin: 25px 0 0 0; font-size: 15px; color: #2c3e50;\">This module has also been successfully integrated into the SEBON MIS (<a href=\"http://10.10.0.199/sebonmis/index.php\">http://10.10.0.199/sebonmis/index.php</a>) under the 'भ्रमण आदेश' tab.</p>
<p style=\"$font margin: 25px 0 0 0; font-size: 15px; color: #2c3e50;\"><strong>Regards,</strong><br>HR Section<br>Securities Board of Nepal</p>
</td></tr>
<tr><td style=\"background-color: #f0f0f0; padding: 15px; text-align: center;\">
<p style=\"$font margin: 0; font-size: 12px; color: #666666;\"><em>** This is an automated mail generated from the application. Please do not reply to this email.</em></p>
</td></tr></table></td></tr></table></body></html>";
}

function sendInternationalBatchMails($conn, $batchId) {
    $empSql = "
        SELECT i.Batch_id, i.EmpPersonalCode, emp.EmpName AS EmployeeName, emp.Gender, emp.Email,
               c.Country_name AS Country, ci.City_name AS City, i.travel_objective,
               i.travelDateStart, i.travelDateEnd,
               CAST(DATEDIFF(DAY, i.travelDateStart, i.travelDateEnd) + 1 - 0.5 AS DECIMAL(5,2)) AS totalday,
               CASE WHEN c.extra33percent_country = 1
                    THEN (t.tadaInUSD * 1.33) * (DATEDIFF(DAY, i.travelDateStart, i.travelDateEnd) + 1 - 0.5)
                    ELSE t.tadaInUSD * (DATEDIFF(DAY, i.travelDateStart, i.travelDateEnd) + 1 - 0.5)
               END AS tadaInUSD_Final,
               CASE WHEN i.DressAllowance = 1 THEN 'Yes' ELSE 'No' END AS DressAllowance
        FROM International_tada i
        LEFT JOIN CountryMaster c ON i.Country_id = c.Country_id
        LEFT JOIN CityMaster ci ON i.City_id = ci.City_id
        LEFT JOIN TadaDefinerMasterBylevel t ON i.TadaDefinerMasterBylevel_id = t.TadaDefinerMasterBylevel_id
        LEFT JOIN Employee_Information emp ON i.EmpPersonalCode = emp.EmpPersonalCode
        WHERE i.Batch_id = ?";

    return sendBatchMails($conn, $batchId, 'International', $empSql,
        fn($emp) => "Travel Notification - Batch $batchId | {$emp['Country']}",
        'buildInternationalMailBody',
        fn($emp) => "International travel notification for {$emp['EmployeeName']}: {$emp['Country']}, "
                  . mailFmtDate($emp['travelDateStart']) . ' to ' . mailFmtDate($emp['travelDateEnd']) . '.');
}

