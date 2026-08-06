<?php
include 'db.php';

// Get batch_id from URL
$batch_id = $_GET['batch_id'] ?? null;
if (!$batch_id) die("Batch ID is required");

// Function to format numbers
function formatNumber($value) {
    if ($value === null) return 'N/A';
    if (floor($value) == $value) return number_format($value, 0);
    return number_format($value, 2);
}

// Function to get English date string
function getEnglishDateForConversion($dateObj) {
    if (!$dateObj) return '';
    return $dateObj->format('Y-m-d');
}

// Fetch employees for this batch WITH fiscal year from table
$sql = "
SELECT
    i.International_tada_id, i.Batch_id, i.Chalani_id, i.form_date, i.EmpPersonalCode,
    i.fiscal_year_master_id,
    fy.fy AS FiscalYear,
    emp.EmpName AS EmployeeName, emp.EmpNameInNepali AS EmployeeNamenepali,
    dt.designationTypeInNepali AS DesignationInNepali,
    c.Country_name, c.extra33percent_country, ci.City_name, i.travel_objective,
    i.travelDateStart, i.travelDateEnd, t.tadaInUSD,
    CASE WHEN c.extra33percent_country = 1 
         THEN t.tadaInUSD * 1.33 
         ELSE t.tadaInUSD 
    END AS tadaInUSD_Final,
    i.totalday,
    -- Dress Allowance: if 0 then 0 (show message), if 1 then calculate by level
    CASE 
        WHEN i.DressAllowance = 0 THEN 0
        WHEN i.DressAllowance = 1 AND emp.LevelName_id IN (5, 11) THEN 10000
        WHEN i.DressAllowance = 1 THEN 8000
        ELSE 0
    END AS DressAllowance
FROM International_tada i
LEFT JOIN fiscal_year_master fy ON i.fiscal_year_master_id = fy.fiscal_year_master_id
LEFT JOIN CountryMaster c ON i.Country_id = c.Country_id
LEFT JOIN CityMaster ci ON i.City_id = ci.City_id
LEFT JOIN TadaDefinerMasterBylevel t ON i.TadaDefinerMasterBylevel_id = t.TadaDefinerMasterBylevel_id
LEFT JOIN Employee_Information emp ON i.EmpPersonalCode = emp.EmpPersonalCode
LEFT JOIN DesignationTypeMaster dt ON emp.Designation_id = dt.id
WHERE i.Batch_id = ?
ORDER BY i.International_tada_id
";

$stmt = sqlsrv_query($conn, $sql, array($batch_id));
if ($stmt === false) die("Query Error: " . print_r(sqlsrv_errors(), true));

$employees = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $employees[] = $row;
}

if (empty($employees)) die("No records found for this batch");

// Get fiscal year from the table itself (not TOP 1)
$fyText = $employees[0]['FiscalYear'] ?? 'N/A';

// Get USD conversion rate - EXACT match with form_date
$form_date = $employees[0]['form_date'];

// Format the date properly for exact comparison
$formDateFormatted = $form_date->format('Y-m-d');

$conversionRateSql = "SELECT amount, conversion_date 
                      FROM USDforex 
                      WHERE CAST(conversion_date AS DATE) = ?";

$conversionStmt = sqlsrv_query($conn, $conversionRateSql, array($formDateFormatted));

if ($conversionStmt === false) {
    $usdRate = null;
    $usdConversionDate = null;
} else {
    $conversionRate = sqlsrv_fetch_array($conversionStmt, SQLSRV_FETCH_ASSOC);
    
    if ($conversionRate) {
        $usdRate = $conversionRate['amount'];
        $usdConversionDate = $conversionRate['conversion_date'];
    } else {
        $usdRate = null;
        $usdConversionDate = null;
    }
}

// Display values (use N/A if not found)
$usdRateDisplay = ($usdRate !== null) ? $usdRate : 'N/A';
$usdDateDisplay = ($usdConversionDate !== null) ? $usdConversionDate->format('Y-m-d') : 'N/A';

?>

<!DOCTYPE html>
<html lang="ne">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>International TADA Forms - <?php echo $batch_id; ?></title>

<link href="https://nepalidatepicker.sajanmaharjan.com.np/v5/nepali.datepicker/css/nepali.datepicker.v5.0.6.min.css" rel="stylesheet">

<style>
* {
    box-sizing: border-box;
}

@media print {
    .no-print { 
        display: none !important; 
    }
    @page { 
        size: A4; 
        margin: 10mm 8mm 8mm 10mm; 
    }
    body { 
        margin: 0; 
        padding: 0;
    }
    .page-break { 
        page-break-after: always; 
        page-break-inside: avoid; 
    }
    .print-container {
        width: 100% !important;
        height: auto !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        page-break-after: always;
    }
    .header {
        text-align: center !important;
    }
    .header-line {
        text-align: center !important;
    }
    .bottom-section {
        display: flex !important;
        gap: 5px !important;
        page-break-inside: avoid !important;
    }
    .bottom-left {
        flex: 0 0 32% !important;
        width: 32% !important;
    }
    .bottom-right {
        flex: 0 0 66% !important;
        width: 66% !important;
    }
    .bottom-right-content {
        white-space: normal !important;
        word-wrap: break-word !important;
    }
    table {
        width: 100% !important;
    }
    th, td {
        border: 1px solid #000 !important;
    }
}

body {
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
    font-size: 11pt;
    margin: 0;
    padding: 0;
    background-color: #f5f5f5;
    line-height: 1.3;
}

.print-container {
    width: 210mm;
    min-height: 297mm;
    height: auto;
    margin: 0 auto 20px;
    background: white;
    padding: 10mm 10mm 8mm 10mm;
    box-sizing: border-box;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    position: relative;
    display: flex;
    flex-direction: column;
}

.header {
    text-align: center;
    margin-bottom: 10px;
    padding-bottom: 0;
}

.header-line {
    margin: 1px 0;
    font-size: 11pt;
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
    line-height: 1.2;
    text-align: center;
}

.header-number-date {
    text-align: right;
    margin: 3px 0 10px 0;
    font-size: 11pt;
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
    padding-right: 0;
}

.form-info { 
    margin: 6px 0; 
    line-height: 1.3; 
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
}

.form-info div { 
    margin: 2px 0; 
    font-size: 11pt;
}

table { 
    width: 100%; 
    border-collapse: collapse; 
    margin: 6px 0; 
    font-size: 11pt;
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
}

table, th, td { 
    border: 1px solid #000; 
}

th, td { 
    padding: 4px 6px; 
    text-align: left; 
    vertical-align: middle;
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
    font-size: 11pt;
    line-height: 1.3;
}

th { 
    text-align: center; 
    line-height: 1.2;
}

.text-right { 
    text-align: right; 
}

.text-center { 
    text-align: center; 
}

.bottom-section { 
    display: flex;
    width: 100%;
    margin-top: 10px;
    flex-shrink: 0;
    gap: 5px;
    page-break-inside: avoid;
}

.bottom-left { 
    flex: 0 0 32%;
    font-size: 11pt;
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
    vertical-align: top;
}

.bottom-right {
    flex: 0 0 66%;
    border: 1px solid #000;
    padding: 8px 10px;
    font-size: 11pt;
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
    min-height: 120px;
    vertical-align: top;
}

.bottom-right-title {
    text-align: center;
    margin-bottom: 6px;
    font-size: 11pt;
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
}

.bottom-right-subtitle {
    margin: 6px 0;
    text-align: center;
    font-size: 11pt;
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
}

.bottom-right-content {
    margin: 6px 0;
    line-height: 1.4;
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
    font-size: 11pt;
    word-wrap: break-word;
    white-space: normal;
}

.bottom-right-signatures {
    display: flex; 
    justify-content: space-between; 
    margin-top: 12px;
    gap: 12px;
}

.bottom-right-sig-left {
    flex: 1;
    font-size: 11pt;
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
    white-space: nowrap;
}

.bottom-right-sig-right {
    flex: 1;
    text-align: right;
    font-size: 11pt;
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
    white-space: nowrap;
}

.print-btn {
    position: fixed; 
    top: 20px; 
    right: 20px;
    padding: 10px 20px; 
    background-color: #1E3A8A; 
    color: #fff;
    border: none; 
    border-radius: 5px; 
    cursor: pointer; 
    z-index: 1000;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.print-btn:hover { 
    background-color: #1e40af; 
}

.employee-count {
    position: fixed; 
    top: 70px; 
    right: 20px;
    background: #10B981; 
    color: #fff;
    padding: 8px 15px; 
    border-radius: 5px; 
    font-size: 14px; 
    z-index: 1000;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

small {
    font-size: 11pt;
    font-family: 'Kalimati', 'Noto Sans Devanagari', Arial, sans-serif;
}
</style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">🖨️ Print All Forms</button>
<div class="employee-count no-print">
    <?php echo count($employees); ?> Employee(s) - <?php echo count($employees); ?> Form(s)
</div>
<?php foreach ($employees as $index => $employee): 
$isLast = ($index === count($employees) - 1);
$empNameNepali = $employee['EmployeeNamenepali'] ?? $employee['EmployeeName'] ?? '-';
$dailyRate = $employee['tadaInUSD_Final'];
$days = $employee['totalday'];
$totalDailyUSD = $dailyRate * $days;
$totalDailyNPR = $totalDailyUSD * $usdRate;
$dressAllowance = $employee['DressAllowance'] ?? 0;
$grandTotalNPR = $totalDailyNPR + $dressAllowance;
?>
<div class="print-container <?php echo !$isLast ? 'page-break' : ''; ?>">
    <div class="header">
        <div class="header-line">अनुसूची – ५</div>
        <div class="header-line">(नियम ३८ को उपनियम (३) सँग सम्बन्धित)</div>
        <div class="header-line">भ्रमण आदेश</div>
        <div class="header-line">अन्तरदेशिय । अन्तर्राष्ट्रिय</div>
    </div>
    <div class="header-number-date">
        संख्या: <?php echo htmlspecialchars($employee['Chalani_id']).'/'.$fyText; ?>
        <br>
        मिति: 
        <span class="nepali-date" data-ad-date="<?php echo getEnglishDateForConversion($employee['form_date']); ?>">
            <?php echo $employee['form_date'] ? $employee['form_date']->format('Y-m-d') : '-'; ?>
        </span>
    </div>
    <div class="form-info">
        <div>१. भ्रमण गर्ने पदाधिकारी वा कर्मचारीको नाम: श्री <?php echo htmlspecialchars($empNameNepali); ?></div>
        <div>२. पद: <?php echo htmlspecialchars($employee['DesignationInNepali'] ?? '-'); ?></div>
        <div>३. कार्यालय: नेपाल धितोपत्र बोर्ड</div>
        <div>४. भ्रमण गर्ने स्थान (विदेश भए मुलुक र शहर खुलाउने): <?php echo htmlspecialchars($employee['City_name']); ?>, <?php echo htmlspecialchars($employee['Country_name']); ?></div>
        <div>५. भ्रमणको उद्देश्य: <?php echo htmlspecialchars($employee['travel_objective']); ?> </div>
        <div>
            ६. भ्रमण गर्ने अवधि: 
            <span class="nepali-date" data-ad-date="<?php echo getEnglishDateForConversion($employee['travelDateStart']); ?>">
                <?php echo $employee['travelDateStart']->format('Y-m-d'); ?>
            </span> देखि 
            <span class="nepali-date" data-ad-date="<?php echo getEnglishDateForConversion($employee['travelDateEnd']); ?>">
                <?php echo $employee['travelDateEnd']->format('Y-m-d'); ?>
            </span> (तदनुसार  
            <?php echo $employee['travelDateStart']->format('Y/m/d'); ?> देखि 
            <?php echo $employee['travelDateEnd']->format('Y/m/d'); ?>) सम्म 
            <?php echo formatNumber($days); ?> दिन ।
        </div>
        <div>७. भ्रमण गर्ने साधन: हवाईजहाज तथा ट्याक्सी ।</div>
        <div>८. भ्रमणको निमित्त माग गरेको पेश्की: रु. <?php echo formatNumber($grandTotalNPR); ?>/-</div>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">सि.नं.</th>
                <th style="width: 30%;">विवरण</th>
                <th style="width: 10%;">दर <br>(यु.एस.डि.)</th>
                <th style="width: 7%;">दिन</th>
                <th style="width: 13%;">जम्मा भत्ता<br>(यु.एस.डि.)</th>
                <th style="width: 15%;">विनिमय दर<br>(मिति <span class="nepali-date" data-ad-date="<?php echo getEnglishDateForConversion($employee['form_date']); ?>">
                <?php echo $employee['form_date'] ? $employee['form_date']->format('Y-m-d') : '-'; ?>
            </span>)</th>
                <th style="width: 20%;">जम्मा रकम रु.</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">१</td>
                <td>दैनिक भत्ता<br/><small>(नेपाल धितोपत्र बोर्ड आर्थिक प्रशासन सम्बन्धी नियमावली २०६६ को नियम ४७ को व्यवस्था बमोजिम। )</small></td>
                <td class="text-right"><?php echo formatNumber($dailyRate); ?></td>
                <td class="text-center"><?php echo formatNumber($days); ?></td>
                <td class="text-right"><?php echo formatNumber($totalDailyUSD); ?></td>
                <td class="text-right"><?php echo formatNumber($usdRate); ?></td>
                <td class="text-right"><?php echo formatNumber($totalDailyNPR); ?></td>
            </tr>
            <tr style="height: 50px;">
                <td class="text-center">२</td>
                <td>लुगा भत्ता<br/><small>(नेपाल धितोपत्र बोर्ड आर्थिक प्रशासन सम्बन्धी नियमावली २०६६ को नियम ४८ को व्यवस्था बमोजिम। )</small></td>
                <?php if ($dressAllowance > 0): ?>
                <td colspan="4"></td>
                <td class="text-right"><?php echo formatNumber($dressAllowance); ?></td>
                <?php else: ?>
                <td colspan="5" class="text-center"><em>दुई वर्ष पूरा नभएको ।</em></td>
                <?php endif; ?>
            </tr>
            <tr>
                <td colspan="6" class="text-right">जम्मा</td>
                <td colspan="2"class="text-right"><?php echo formatNumber($grandTotalNPR); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="bottom-section">
        <div class="bottom-left">
            <div style="margin-bottom: 8px;">९. भ्रमण सम्बन्धी अन्य आदेश:</div>
            <div style="margin: 25px 0 8px 0;">.............................................</div>
            <div>भ्रमण आदेश दिने अधिकारी</div>
            <div>मिति: <span class="nepali-date" data-ad-date="<?php echo getEnglishDateForConversion($employee['form_date']); ?>">
            <?php echo $employee['form_date'] ? $employee['form_date']->format('Y-m-d') : '-'; ?>
        </span></div>
            <div style="margin-top: 20px;">बोधार्थ:</div>
            <div>१. श्री लेखा तथा वित्त शाखा</div>
        </div>
        
        <div class="bottom-right">
            <div class="bottom-right-title">
                (लेखा तथा वित्त शाखाको प्रयोजनका लागि)
            </div>
            <div class="bottom-right-subtitle">भ्रमण खर्च</div>
            <div class="bottom-right-content">
                बजेट नं. शीर्षक बाट नगद÷चेक नं. .............रु.............मात्र दिइएको छ ।
            </div><br>
            <div class="bottom-right-signatures">
                <div class="bottom-right-sig-left">
                    <div>बुझिलिनेको सही ...............</div>
                    <div style="margin-top: 8px;">नाम, थरः श्री <?php echo htmlspecialchars($empNameNepali); ?></div>
                    <div style="margin-top: 4px;">मितिः <span class="nepali-date" data-ad-date="<?php echo getEnglishDateForConversion($employee['form_date']); ?>">
            <?php echo $employee['form_date'] ? $employee['form_date']->format('Y-m-d') : '-'; ?>
        </span></div>
                </div>
                <div class="bottom-right-sig-right">
                    <div>.......................</div>
                    <div style="margin-top: 8px;">(सहायक निर्देशक, लेखा तथा वित्त शाखा)</div>
                    <div style="margin-top: 4px;">मिति:<span class="nepali-date" data-ad-date="<?php echo getEnglishDateForConversion($employee['form_date']); ?>">
            <?php echo $employee['form_date'] ? $employee['form_date']->format('Y-m-d') : '-'; ?>
        </span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div> <br><br>
                </div>
            </div>
        </div>
    </div>
</div> 
<?php endforeach; ?>

<script src="https://nepalidatepicker.sajanmaharjan.com.np/v5/nepali.datepicker/js/nepali.datepicker.v5.0.6.min.js"></script>
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