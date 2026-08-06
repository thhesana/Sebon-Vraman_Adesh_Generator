<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

if ($conn === false) {
    die("Database connection failed!");
}

$batch_id = isset($_GET['batch_id']) ? $_GET['batch_id'] : '';

if (empty($batch_id)) {
    die("Invalid Batch ID!");
}

// Fetch all records in this batch
$sql = "
SELECT 
    dt.*,
    e.EmpName,
    e.EmpNameInNepali,
    e.Designation,
    e.LevelName,
    dt.domestic_travel_objective,
    dm.District_name_nepali,
    dtd.DomesticTadaDefinerMasterBylevel_name,
    dtd.tadaInNepali,
    dtm.designationTypeInNepali,
    ttm.type AS travel_type,
    u.username AS created_by_name,
    fy.fy AS fiscal_year,
    tv.tadaverifierPost AS verifier_post
FROM DomesticTada dt
LEFT JOIN Employee_Information e ON dt.EmpPersonalCode = e.EmpPersonalCode
LEFT JOIN DistrictMaster dm ON dt.District_id = dm.District_id
LEFT JOIN DomesticTadaDefinerMasterBylevel dtd ON e.LevelName = dtd.DomesticTadaDefinerMasterBylevel_name
LEFT JOIN DesignationTypeMaster dtm ON e.Designation = dtm.designationType
LEFT JOIN TraveltypeMaster ttm ON dt.TadaTypeMaster_id = ttm.TadaTypeMaster_id
LEFT JOIN Users u ON dt.domestic_createdBy = u.user_id
LEFT JOIN [Vraman_Adesh_Generator].[dbo].[fiscal_year_master] fy 
       ON dt.fiscal_year_master_id = fy.fiscal_year_master_id
LEFT JOIN [Vraman_Adesh_Generator].[dbo].[tadaverifier] tv
       ON dt.tadaverifier_id = tv.tadaverifier_id
WHERE dt.domestic_Batch_id = ?
ORDER BY dt.domestic_Chalani_id
";

$stmt = sqlsrv_query($conn, $sql, array($batch_id));

if ($stmt === false) {
    die("Error fetching records: " . print_r(sqlsrv_errors(), true));
}

$records = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $records[] = $row;
}

if (empty($records)) {
    die("No records found for this batch!");
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>भ्रमण आदेश - Batch <?php echo htmlspecialchars($batch_id); ?></title>
    <script src="https://nepalidatepicker.sajanmaharjan.com.np/v5/nepali.datepicker/js/nepali.datepicker.v5.0.6.min.js"></script>
    <style>
@page {
    size: A4;
    margin: 15mm 20mm;
}

html, body, div, span, p, h1, h2, h3, h4, h5, h6,
table, tr, td, th, input, textarea, select, label,
ul, ol, li, section, article, header, footer {
    font-family: 'Kalimati', sans-serif !important;
    font-size: 12pt !important;
    font-weight: normal !important;
    margin: 0 !important;
    padding: 0 !important;
    line-height: 1.5 !important;
}

.print-container {
    max-width: 210mm;
    margin: 0 auto;
    padding: 15mm;
    background: white;
}

.page-break {
    page-break-after: always;
}

.header, .header h2, .header-subtitle, .header-right {
    text-align: center;
    font-weight: bold !important;
    font-size: 12pt !important;
    margin: 5px 0 !important;
}

.header-right {
    text-align: right;
    line-height: 1.5 !important;
}

.form-info, .form-info > div, .tada-calculation {
    font-size: 12pt !important;
    line-height: 1.5 !important;
}

.bottom-section {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    margin-top: 25px !important;
}

.bottom-left {
    flex: 0.8;
}

.bottom-right {
    flex: 1.4;
    border: 2px solid #000 !important;
    padding: 4px !important;
    margin: 0 !important;
    font-size: 12pt !important;
    font-weight: normal !important;
    line-height: 1.35 !important;
}

.bottom-right * {
    font-family: 'Kalimati', sans-serif !important;
    font-size: 12pt !important;
    font-weight: normal !important;
    margin: 0 !important;
    padding: 0 !important;
    line-height: 1.35 !important;
}

.bottom-right-subtitle {
    text-align: center;
    font-weight: bold !important;
    text-decoration: underline;
    margin-bottom: 2px !important;
}

.bottom-right-content {
    line-height: 1.35 !important;
}

.bottom-right-signatures {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 5px !important;
}

.bottom-right-sig-left, .bottom-right-sig-right {
    flex: 1;
    line-height: 1.35 !important;
}

@media print {
    body {
        margin: 0;
        padding: 0;
    }
    .no-print {
        display: none !important;
    }
    .print-container {
        padding: 0 !important;
        max-width: 100% !important;
    }
    .bottom-right {
        border: 2px solid #000 !important;
        page-break-inside: avoid !important;
    }
    .page-break {
        page-break-after: always !important;
    }
}

.print-button {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #10B981;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 1000;
}

.print-button:hover {
    background: #059669;
}

.record-container {
    margin-bottom: 40px;
}
</style>
</head>
<body>

<button onclick="window.print()" class="print-button no-print">🖨️ Print All (<?php echo count($records); ?> records)</button>

<?php 
$recordCount = count($records);
foreach ($records as $index => $record) { 
    $form_date  = $record['domestic_form_date']      instanceof DateTime ? $record['domestic_form_date']->format('Y-m-d')      : 'N/A';
    $start_date = $record['domestic_travelDateStart'] instanceof DateTime ? $record['domestic_travelDateStart']->format('Y-m-d') : 'N/A';
    $end_date   = $record['domestic_travelDateEnd']   instanceof DateTime ? $record['domestic_travelDateEnd']->format('Y-m-d')   : 'N/A';
    
    $total_days          = $record['domestic_totalday'];
    $empNameNepali       = $record['EmpNameInNepali'] ?? $record['EmpName'] ?? '-';
    $designationNepali   = $record['designationTypeInNepali'] ?? $record['Designation'] ?? '-';
    $daily_rate_base     = $record['tadaInNepali'] ?? 0;
    $is_twenty_percent_extra = $record['domestic_isTwentyPercentExtra'] ?? 0;
    $total_tada          = $record['domestic_tada'] ?? 0;
    $travel_mode         = $record['travel_type'] ?? 'सार्वजनिक यातायात';
    $fy_mode             = $record['fiscal_year'] ?? '';

    // ============= NEW: Dynamic verifier post =============
    $verifier_post = !empty($record['verifier_post']) ? $record['verifier_post'] : 'कार्यकारी निर्देशक';
    // ============= END =============
    
    if ($is_twenty_percent_extra == 1) {
        $daily_rate_display = $daily_rate_base * 1.20;
    } else {
        $daily_rate_display = $daily_rate_base;
    }
?>

<div class="print-container record-container<?php echo ($index < $recordCount - 1) ? ' page-break' : ''; ?>">
    <div class="header">
        <h2 class="nepali-text bold-header">अनुसूची – ५</h2>
        <div class="header-subtitle nepali-text bold-header">(नियम ३८ को उपनियम (३) सँग सम्बन्धित)</div>
        <h2 class="nepali-text bold-header">भ्रमण आदेश</h2>
        <div class="header-subtitle nepali-text bold-header">अन्तरदेशिय । अन्तर्राष्ट्रिय</div>
        <div class="header-right">
            <strong class="nepali-text">संख्या:</strong> <span class="nepali-number"><?php echo htmlspecialchars($record['domestic_Chalani_id']); ?>/<?php echo htmlspecialchars($fy_mode); ?></span><br>
            <strong class="nepali-text">मिति:</strong> 
            <span class="nepali-date nepali-text" data-ad-date="<?php echo htmlspecialchars($form_date); ?>">
                <?php echo htmlspecialchars($form_date); ?>
            </span>
        </div>
    </div>

    <div class="form-info">
        <div><span class="nepali-text nepali-number">१.</span> <span class="nepali-text">भ्रमण गर्ने पदाधिकारी वा कर्मचारीको नाम: श्री</span> <span class="nepali-number"><?php echo htmlspecialchars($empNameNepali); ?></span></div> <br>
        <div><span class="nepali-text nepali-number">२.</span> <span class="nepali-text">पद:</span> <span class="nepali-number"><?php echo htmlspecialchars($designationNepali); ?></span></div><br>
        <div class="nepali-text nepali-number">३. कार्यालय: नेपाल धितोपत्र बोर्ड</div><br>
        <div><span class="nepali-text nepali-number">४.</span> <span class="nepali-text">भ्रमण गर्ने स्थान (विदेश भए मुलुक र शहर खुलाउने):</span> <span class="nepali-number"><?php echo htmlspecialchars($record['District_name_nepali']); ?></span></div><br>
        <div>
            <span class="nepali-text nepali-number">५.</span> <span class="nepali-text">भ्रमणको उद्देश्य:</span>
            <span style="font-family: Arial, sans-serif;"><?php echo htmlspecialchars($record['domestic_travel_objective']); ?></span>
        </div><br>
        <div>
            <span class="nepali-text nepali-number">६.</span> <span class="nepali-text">भ्रमण गर्ने अवधि:</span> 
            <span class="nepali-date nepali-text nepali-number" data-ad-date="<?php echo htmlspecialchars($start_date); ?>">
                <?php echo htmlspecialchars($start_date); ?>
            </span>
            <span class="nepali-text">देखि</span> 
            <span class="nepali-date nepali-text nepali-number" data-ad-date="<?php echo htmlspecialchars($end_date); ?>">
                <?php echo htmlspecialchars($end_date); ?>
            </span>
            <span class="nepali-text">सम्म</span> 
            <span class="nepali-number"><?php echo number_format($total_days, 0); ?></span> 
            <span class="nepali-text">दिन ।</span> <br>
        </div><br>
        <div class="nepali-text nepali-number">७. भ्रमण गर्ने साधन: <?php echo htmlspecialchars($travel_mode); ?> ।</div> <br>
        <div>
            <span class="nepali-text nepali-number">८.</span> <span class="nepali-text">भ्रमणको निमित्त माग गरेको पेश्की: रु.</span> <span class="nepali-number"><?php echo number_format($total_tada, 2); ?></span><span class="nepali-text">/-</span>
            <div class="tada-calculation nepali-text nepali-number">
                दैनिक भत्ताः (दैनिक भत्ता रु. <?php echo number_format($daily_rate_display, 0); ?> X <?php echo number_format($total_days, 0); ?> दिनको) को जम्मा रु. <?php echo number_format($total_tada, 0); ?>।–
                <br>(नेपाल धितोपत्र बोर्ड आर्थिक प्रशासन सम्बन्धी नियमावली २०६६ को नियम ४२ ले व्यवस्था गरे बमोजिम । )<BR>
            </div> <br>
            <span class="nepali-text nepali-number">९. </span><span class="nepali-text">भ्रमण सम्बन्धी अन्य आदेश:</span>
        </div>
    </div>

    <div class="bottom-section">
        <div class="bottom-left">
            <div style="margin: 35px 0 8px 0;">.......................................</div>
            <div class="nepali-text nepali-number">भ्रमण आदेश दिने अधिकारी</div>
            <!-- ============= UPDATED: Dynamic verifier post from tadaverifier table ============= -->
            <div class="nepali-text nepali-number"><?php echo htmlspecialchars($verifier_post); ?></div>
            <!-- ============= END ============= -->
            <div class="nepali-text">मिति: <span class="nepali-date nepali-text" data-ad-date="<?php echo htmlspecialchars($form_date); ?>"></div> <br>
            <div style="margin-top: 25px;"><strong class="nepali-text bold-header">बोधार्थ:</strong></div>
            <div class="nepali-text nepali-number">१. श्री लेखा तथा वित्त शाखा</div>
        </div>
        
        <div class="bottom-right">
            <div class="bottom-right-subtitle nepali-text nepali-number">भ्रमण खर्च</div>
            <div class="bottom-right-content nepali-text nepali-number">
                बजेट नं. शीर्षक बाट नगद/चेक नं. .........रु.	|- मात्र दिइएको छ ।
            </div><br><br>
            <div class="bottom-right-signatures">
                <div class="bottom-right-sig-left">
                    <div class="nepali-text nepali-number">बुझिलिनेको सही ............................</div>
                    <div style="margin-top: 8px;"><span class="nepali-text">नाम, थरः श्री</span> <span class="nepali-number"><?php echo htmlspecialchars($empNameNepali); ?></span></div> <br>
                    <div style="margin-top: 4px;" class="nepali-text">मितिः <span class="nepali-date nepali-text" data-ad-date="<?php echo htmlspecialchars($form_date); ?>"></div> 
                </div>
                <div class="bottom-right-sig-right">
                    <div class="nepali-number">....................................&nbsp;&nbsp;&nbsp;</div>
                    <div style="margin-top: 8px;" class="nepali-text nepali-number">(सहायक निर्देशक, लेखा तथा वित्त शाखा)</div> <br>
                    <div style="margin-top: 4px;" class="nepali-text">मिति: <span class="nepali-date nepali-text" data-ad-date="<?php echo htmlspecialchars($form_date); ?>"></div> <br>
                </div>
            </div>
        </div>
    </div>
</div>

<?php } ?>

<script>
window.onload = function() {
    setTimeout(function() {
        document.querySelectorAll('.nepali-date').forEach(function(el) {
            var adDate = el.getAttribute('data-ad-date');
            if(adDate) {
                try { 
                    el.textContent = NepaliFunctions.AD2BS(adDate, "YYYY-MM-DD", "YYYY/MM/DD"); 
                } catch(e){ 
                    console.error("Date conversion error:", adDate, e); 
                }
            }
        });
    }, 500);
};
</script>

</body>
</html>