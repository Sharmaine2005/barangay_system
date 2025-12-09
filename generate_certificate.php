<?php
require_once 'includes/db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 1. Fetch Request Details
    // REMOVED 'u.civil_status' from the list below to fix the error
    $sql = "SELECT r.*, u.barangay, u.age 
            FROM requests r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if (!$request) { die("Request not found."); }

    // 2. Fetch Captain
    $user_barangay = $request['barangay'];
    $cap_stmt = $conn->prepare("SELECT captain FROM barangays WHERE name = ?");
    $cap_stmt->bind_param("s", $user_barangay);
    $cap_stmt->execute();
    $cap_result = $cap_stmt->get_result();
    $captain_name = ($cap_result->num_rows > 0) ? $cap_result->fetch_assoc()['captain'] : "JUAN DELA CRUZ";

    // 3. DYNAMIC TITLE LOGIC
    $doc_title = strtoupper($request['doc_type']);
    
    // Special handling for RA 11261
    if ($doc_title == "FIRST TIME JOB SEEKER OATH") {
        $doc_title = "CERTIFICATION (RA 11261)";
    }

} else {
    die("Invalid Request.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($doc_title); ?></title>
    <style>
        body { font-family: "Times New Roman", serif; background: #ccc; margin: 0; padding: 20px; }
        .certificate-container { width: 800px; height: 1000px; background: white; margin: 0 auto; padding: 50px; position: relative; }
        .header { text-align: center; margin-bottom: 50px; }
        .header h1 { margin: 10px 0; }
        .content { font-size: 18px; line-height: 1.6; text-align: justify; margin-top: 50px; }
        .highlight { font-weight: bold; text-transform: uppercase; }
        .footer { margin-top: 100px; display: flex; justify-content: flex-end; }
        .official { text-align: center; }
        .official .line { border-bottom: 1px solid black; width: 250px; margin: 0 auto 5px auto; }
        @media print { 
            body { background: white; margin: 0; padding: 0; } 
            .no-print { display: none; } 
            .certificate-container { margin: 0; width: 100%; height: auto; box-shadow: none; }
        }
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn-print { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Print Certificate</button>
    </div>

    <div class="certificate-container">
        
        <div class="header">
            <p style="font-size: 14px;">Republic of the Philippines<br>Province of Cavite<br>City of Bacoor</p>
            <h2>BARANGAY <?php echo strtoupper($request['barangay']); ?></h2>
            <p style="font-size:12px;">Office of the Barangay Captain</p>
            <br>
            <h1 style="text-decoration: underline;"><?php echo $doc_title; ?></h1>
        </div>

        <div class="content">
            <p><strong>TO WHOM IT MAY CONCERN:</strong></p>

            <p>This is to certify that <span class="highlight"><?php echo htmlspecialchars($request['full_name']); ?></span>, 
            of legal age, is a bona fide resident of 
            <span class="highlight">Barangay <?php echo htmlspecialchars($request['barangay']); ?></span>, Bacoor City.</p>

            <?php if ($request['doc_type'] == 'Certificate of Good Moral Character'): ?>
                <p>This is to further certify that the above-named person is known to be of <strong>Good Moral Character</strong> and has no derogatory record on file in this office.</p>
            <?php elseif ($request['doc_type'] == 'Certificate of Indigency'): ?>
                <p>This is to certify that the above-named person belongs to an <strong>indigent family</strong> in this barangay and is in need of financial assistance.</p>
            <?php endif; ?>

            <p>This certification is being issued upon the request of the interested party for the purpose of: <br>
            <span class="highlight" style="text-decoration: underline;"><?php echo htmlspecialchars($request['purpose']); ?></span>.</p>

            <p>Given this <span class="highlight"><?php echo date('jS'); ?></span> day of <span class="highlight"><?php echo date('F, Y'); ?></span> at Barangay <?php echo htmlspecialchars($request['barangay']); ?>, City of Bacoor.</p>
        </div>

        <div class="footer">
            <div class="official">
                <div class="line"></div>
                <strong>HON. <?php echo strtoupper($captain_name); ?></strong><br>
                Punong Barangay
            </div>
        </div>
        
        <div style="position: absolute; bottom: 50px; right: 50px; text-align: center;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=Valid:<?php echo $request['id']; ?>-<?php echo urlencode($captain_name); ?>" alt="QR Code" style="border: 1px solid #333;">
            <p style="font-size: 10px;">Scan to Verify</p>
        </div>

    </div>

</body>
</html>