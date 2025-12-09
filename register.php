<?php
// register.php
require_once 'includes/db_connect.php';

$message = "";

// FETCH BARANGAYS FOR DROPDOWN
$barangay_list = [];
$b_result = $conn->query("SELECT name FROM barangays ORDER BY name ASC");
while ($row = $b_result->fetch_assoc()) {
    $barangay_list[] = $row['name'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $message = "<div class='alert error'>Passwords do not match.</div>";
    } else {
        $first_name = strtoupper(trim($_POST['first_name']));
        $middle_name = strtoupper(trim($_POST['middle_name']));
        $last_name = strtoupper(trim($_POST['last_name']));
        
        $birthday = $_POST['birthday']; 
        $age = intval($_POST['age']);
        $sex = $_POST['sex'];
        
        // NEW FIELDS
        $civil_status = $_POST['civil_status'];
        $is_voter = intval($_POST['is_voter']); // 1 or 0
        $years_resident = intval($_POST['years_resident']);
        
        // CONTACT NUMBER FIX (Auto-remove leading 0 and add 63)
        $raw_contact = trim($_POST['contact_number']);
        if (substr($raw_contact, 0, 1) === '0') { $raw_contact = substr($raw_contact, 1); }
        $contact_number = "63" . $raw_contact; 
        
        $house_no = strtoupper(trim($_POST['house_no']));
        $street = strtoupper(trim($_POST['street']));
        $subdivision = strtoupper(trim($_POST['subdivision']));
        $barangay = $_POST['barangay']; 
        $city = "Bacoor";

        $full_address = "$house_no, $street, $subdivision, $barangay, $city";

        // CHECK USERNAME
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $message = "<div class='alert error'>Username taken.</div>";
        } else {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            
            // SQL INSERT (Updated for new columns)
            // We added: civil_status, is_voter, years_resident
            $sql = "INSERT INTO users (username, password, role, first_name, middle_name, last_name, sex, civil_status, is_voter, birthday, age, contact_number, address, years_resident, barangay, account_status) 
                    VALUES (?, ?, 'resident', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            $stmt = $conn->prepare($sql);
            
            // Types: s=string, i=integer
            // Order: user(s), pass(s), first(s), mid(s), last(s), sex(s), civ(s), voter(i), bday(s), age(i), contact(s), addr(s), years(i), brgy(s)
            // Total: 14 parameters -> "sssssssissisis"
            $stmt->bind_param("sssssssissisis", $username, $hashed_pass, $first_name, $middle_name, $last_name, $sex, $civil_status, $is_voter, $birthday, $age, $contact_number, $full_address, $years_resident, $barangay);

            if ($stmt->execute()) {
                header("Location: registration_pending.php");
                exit;
            } else {
                $message = "<div class='alert error'>Error: " . $conn->error . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Bacoor System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .register-card { max-width: 1000px !important; width: 100%; padding: 25px; background: white; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-top: 5px solid var(--accent-color); }
        h2 { text-align: center; color: var(--primary-color); font-size: 1.5rem; margin-bottom: 15px; }
        .form-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .panel-left { border-right: 1px solid #eee; padding-right: 20px; }
        .section-header { font-size: 1rem; font-weight: bold; color: var(--primary-color); border-bottom: 2px solid #eee; margin-bottom: 10px; padding-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
        label { font-size: 0.8rem; font-weight: 600; color: #555; margin-bottom: 2px; }
        input, select { padding: 8px; font-size: 0.9rem; margin-bottom: 8px; width: 100%; border: 1px solid #ccc; border-radius: 4px; }
        .req { color: red; }
        .contact-group { display: flex; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; margin-bottom: 8px; }
        .prefix { background: #eee; padding: 8px 10px; font-size: 0.9rem; color: #555; font-weight: bold; border-right: 1px solid #ddd; }
        .contact-group input { border: none; margin: 0; }
        #user-msg { font-size: 0.8rem; font-weight: bold; display: block; margin-top: -5px; margin-bottom: 5px; height: 15px; }
        .txt-ok { color: green; } .txt-err { color: red; }
        
        @media (max-width: 1024px) { .form-layout { grid-template-columns: 1fr; gap: 20px; } .panel-left { border-right: none; padding-right: 0; border-bottom: 2px solid #f0f0f0; padding-bottom: 20px; } }
        @media (max-width: 600px) { .row-2, .row-3 { grid-template-columns: 1fr; } }
        
        .modal { display: none; position: fixed; z-index: 9999; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(2px); }
        .modal-box { background: white; width: 90%; max-width: 450px; margin: 5% auto; padding: 20px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .review-row { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding: 8px 0; font-size: 0.9rem; }
        .review-label { color: #777; }
        .review-val { font-weight: bold; text-align: right; }
        .modal-btns { display: flex; gap: 10px; margin-top: 20px; }
        .btn-back { background: #777; color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="register-card">
        <h2>Resident Registration</h2>
        <?php echo $message; ?>

        <form id="regForm" action="register.php" method="POST">
            <div class="form-layout">
                
                <div class="panel-left">
                    <div class="section-header">1. Personal Details</div>
                    <div class="row-3">
                        <div><label>First Name <span class="req">*</span></label><input type="text" id="fname" name="first_name" required style="text-transform: uppercase;"></div>
                        <div><label>Middle Name</label><input type="text" id="mname" name="middle_name" style="text-transform: uppercase;"></div>
                        <div><label>Last Name <span class="req">*</span></label><input type="text" id="lname" name="last_name" required style="text-transform: uppercase;"></div>
                    </div>
                    <div class="row-2">
                        <div>
                            <label>Sex <span class="req">*</span></label>
                            <select name="sex" id="sex">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label>Civil Status <span class="req">*</span></label>
                            <select name="civil_status" id="civil_status">
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                                <option value="Solo Parent">Solo Parent</option>
                            </select>
                        </div>
                    </div>
                    <div class="row-2">
                        <div>
                            <label>Registered Voter? <span class="req">*</span></label>
                            <select name="is_voter" id="is_voter">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div>
                            <label>Contact No. <span class="req">*</span></label>
                            <div class="contact-group">
                                <span class="prefix">+63</span>
                                <input type="text" id="contact" name="contact_number" placeholder="9xxxxxxxxx or 09" maxlength="11" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>
                        </div>
                    </div>
                    <div class="row-3">
                        <div style="grid-column: span 2;"><label>Birthday <span class="req">*</span></label><input type="date" id="bday" name="birthday" required onchange="calcAge()"></div>
                        <div><label>Age</label><input type="text" id="age" name="age" readonly style="background:#eee;"></div>
                    </div>

                    <div class="section-header" style="margin-top:15px;">2. Credentials</div>
                    <div class="row-1">
                        <label>Username <span class="req">*</span></label>
                        <input type="text" id="user" name="username" required autocomplete="off">
                        <span id="user-msg"></span>
                    </div>
                    <div class="row-2">
                        <div><label>Password <span class="req">*</span></label><input type="password" id="pass" name="password" required></div>
                        <div><label>Confirm Password <span class="req">*</span></label><input type="password" id="cpass" name="confirm_password" required></div>
                    </div>
                </div>

                <div>
                    <div class="section-header">3. Address</div>
                    <label>Barangay <span class="req">*</span></label>
                    <select name="barangay" id="brgy" required>
                        <option value="" disabled selected>Select...</option>
                        <?php foreach ($barangay_list as $brgy): ?>
                            <option value="<?php echo htmlspecialchars($brgy); ?>"><?php echo htmlspecialchars($brgy); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <div class="row-2">
                        <div><label>House No. <span class="req">*</span></label><input type="text" id="house" name="house_no" required style="text-transform: uppercase;"></div>
                        <div><label>Street <span class="req">*</span></label><input type="text" id="street" name="street" required style="text-transform: uppercase;"></div>
                    </div>
                    <div class="row-2">
                         <div><label>Subdivision <span class="req">*</span></label><input type="text" id="subd" name="subdivision" required style="text-transform: uppercase;"></div>
                         <div><label>Years of Residence</label><input type="number" id="years" name="years_resident" required min="0" placeholder="e.g. 5"></div>
                    </div>
                    
                    <button type="button" onclick="review()" class="btn-primary" style="margin-top:20px; padding:12px;">Register Account</button>
                    <div style="text-align:center; margin-top:10px;"><a href="index.php" style="color:var(--primary-color); font-size:0.9rem;">Back to Login</a></div>
                </div>

            </div>
        </form>
    </div>
</div>

<div id="modal" class="modal">
    <div class="modal-box">
        <h3 style="text-align:center; color:var(--primary-color); border-bottom:1px solid #eee; padding-bottom:10px;">Confirm Details</h3>
        <div id="review-content"></div>
        <div class="modal-btns">
            <button class="btn-back" onclick="document.getElementById('modal').style.display='none'">Edit</button>
            <button class="btn-primary" onclick="document.getElementById('regForm').submit()">Confirm & Submit</button>
        </div>
    </div>
</div>

<script>
    function calcAge() {
        const b = new Date(document.getElementById('bday').value);
        const t = new Date();
        let a = t.getFullYear() - b.getFullYear();
        if (t < new Date(t.getFullYear(), b.getMonth(), b.getDate())) a--;
        document.getElementById('age').value = a;
    }

    const uInput = document.getElementById('user');
    const uMsg = document.getElementById('user-msg');
    let uValid = false;

    uInput.addEventListener('input', function() {
        if(this.value.length < 3) { uMsg.textContent=''; return; }
        const fd = new FormData(); fd.append('username', this.value);
        fetch('check_username.php', { method:'POST', body:fd })
        .then(r=>r.text()).then(d=>{
            if(d==='taken'){ uMsg.textContent='❌ Taken'; uMsg.className='txt-err'; uValid=false; }
            else { uMsg.textContent='✔ Available'; uMsg.className='txt-ok'; uValid=true; }
        });
    });

    function review() {
        const f = document.getElementById('regForm');
        if(!f.checkValidity()) { f.reportValidity(); return; }
        if(document.getElementById('pass').value !== document.getElementById('cpass').value) { alert("Passwords mismatch"); return; }
        
        // Show corrected phone in review (remove leading 0 if present)
        let ph = document.getElementById('contact').value.replace(/^0+/, '');

        const data = [
            ['Name', document.getElementById('fname').value + ' ' + document.getElementById('lname').value],
            ['Details', document.getElementById('sex').value + ' / ' + document.getElementById('age').value + 'yo'],
            ['Status', document.getElementById('civil_status').value],
            ['Voter?', (document.getElementById('is_voter').value == '1' ? 'Yes' : 'No')],
            ['Contact', '+63' + ph],
            ['Barangay', document.getElementById('brgy').value]
        ];

        let html = '';
        data.forEach(d => {
            html += `<div class="review-row"><span class="review-label">${d[0]}</span><span class="review-val">${d[1]}</span></div>`;
        });
        document.getElementById('review-content').innerHTML = html;
        document.getElementById('modal').style.display = 'block';
    }
</script>

</body>
</html>