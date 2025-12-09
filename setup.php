<?php
// setup.php
$host = "localhost";
$username = "root";
$password = ""; 
$port = 3307; 

$conn = new mysqli($host, $username, $password, "", $port);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$conn->query("DROP DATABASE IF EXISTS barangay_system");
$conn->query("CREATE DATABASE barangay_system");
$conn->select_db("barangay_system");

// 1. CREATE BARANGAYS TABLE (New)
$sql = "CREATE TABLE barangays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    captain VARCHAR(100) NOT NULL,
    contact VARCHAR(50) DEFAULT NULL
)";
$conn->query($sql);

// 2. INSERT BARANGAY DATA (The specific list you provided)
$barangays = [
    ["Aniban I (Aniban I, III, V)", "AGCOPRA, JOSE JR. HABELITO", "417-7024"],
    ["Aniban II (Aniban II and IV)", "ENRIQUEZ, NARCISA LARA", ""],
    ["Bayanan", "GAWARAN, ALFIE OGALESCO", "424-9453"],
    ["Dulong Bayan", "PALMA, JADE EMSON JAVIER", ""],
    ["Habay I", "CASTILLO, ROBERT VELASQUEZ", "519-0223"],
    ["Habay II", "BAUTISTA, MA. ELIZA HONDOLERO", ""],
    ["Kaingin Digman", "GAWARAN, EDWIN GERVACIO", ""],
    ["Ligas I (Ligas I and II)", "MEDINA, GEORGE GREGORIO", ""],
    ["Ligas II (Ligas III)", "MORALES, PABLO NAVARETTE", "417-7787"],
    ["Mabolo (Mabolo I, II, and III)", "MAÑAGO, ROEHL III REDONDO", "436-0036"],
    ["Maliksi I", "UGALDE, LOUIE ROLAND FELIZARDO", "417-8754"],
    ["Maliksi II (Maliksi II and III)", "DIZON, LIBERATO ROMERO", ""],
    ["Mambog I", "DE GUZMAN, NARCISO NOLASCO", ""],
    ["Mambog II (Mambog II and V)", "PILAR, ROGELIO JAVINAL", ""],
    ["Mambog III", "ADVINCULA, JOHN ERNEST CANDELARIA", "472-2726"],
    ["Mambog IV", "NOLASCO, ROBERT GARCIA", ""],
    ["Molino I", "DOMINGUEZ, JEO MEDINA", "424-2559"],
    ["Molino II", "SAQUITAN, MICHAEL JIMENEZ", "477-1539"],
    ["Molino III", "ADVINCULA, APOLONIO JR. ILAS", "454-4667"],
    ["Molino IV", "CAMPAÑA, JEFFREY PAKINGAN", "477-1533"],
    ["Molino V", "BINGHAY, BOB FALLA", ""],
    ["Molino VI", "JAVIER, RONALDO JAVIER", "476-0461 / 572-0455"],
    ["Molino VII", "BLANQUERA, ANTONIO NACARIO", ""],
    ["Niog (Niog 1, 2, and 3)", "CAMARCE, ALMA ROBLES", ""],
    ["P.F. Espiritu I", "TORRIJOS, MAY MARASIGAN", ""],
    ["P.F. Espiritu II (PF Espiritu II and III)", "MACAVINTA, ALVIN BRYAN MALUBAY", "417-4140"],
    ["P.F. Espiritu III (PF Espiritu IV)", "DOMINGO, ROMMIE FRANCISCO", ""],
    ["P.F. Espiritu IV (PF Espiritu V and VI)", "BAUTISTA, JORDEAN ZYVON", ""],
    ["P.F. Espiritu V (PF Espiritu VII)", "PATERNO, RAFAEL III VENTURA", ""],
    ["P.F. Espiritu VI (PF Espiritu VIII)", "BARBA, FERDINAND SEGISMUNDO", ""],
    ["Poblacion (Tabing Dagat, Daang Bukid, Campo Santo)", "FRANCISCO, RANDY CELESTINO", "571-5114"],
    ["Queens Row Central", "OBLIGACION, ARMANDO", ""],
    ["Queens Row East", "MONTES, THEODORE FERNANDEZ", ""],
    ["Queens Row West", "ABUG, JAIME PAGUILANAN", ""],
    ["Real (Real I and II)", "AGANUS, BRIAN CALAWIGAN", "471-2116"],
    ["Salinas I", "ESPIRITU, NICANOR DOMINGUES", ""],
    ["Salinas II (Salinas II, III, and IV)", "TORNO, MARK ANTHONY GUERRERO", ""],
    ["San Nicolas I", "KALINISAN, ALFREDO TORIO", ""],
    ["San Nicolas II", "EUSEBIO, JONNEL TROPEL", ""],
    ["San Nicolas III", "AREVALO, ALFREDO FELIZARDO", "417-5001"],
    ["Sinbanali (Sineguelasan, Banalo, and Alima)", "SANCHEZ, CARIDAD JAVIER", "431-2569"],
    ["Talaba I (Talaba I, III, and VII)", "BAÑAS, JOSEFINO TORRES", ""],
    ["Talaba II", "BAÑAS, RODOLFO JR. TORRIJOS", ""],
    ["Talaba III (Talaba IV, V, and VI)", "TRINIDAD, EMMANUEL TAMPOS", ""],
    ["Zapote I (Zapote I and II)", "RAMIREZ, VIVIAN GAWARAN", "519-3452"],
    ["Zapote II (Zapote III and IV)", "TORRES, EDWIN CABRERA", ""],
    ["Zapote III (Zapote V)", "DE ROSAS, ERNESTO GARCIA", ""]
];

$stmt = $conn->prepare("INSERT INTO barangays (name, captain, contact) VALUES (?, ?, ?)");
foreach ($barangays as $b) {
    $stmt->bind_param("sss", $b[0], $b[1], $b[2]);
    $stmt->execute();
}

// 3. CREATE USERS TABLE
$sql = "CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('city_admin', 'barangay_admin', 'resident') DEFAULT 'resident',
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) NOT NULL,
    sex ENUM('Male', 'Female') NOT NULL,
    birthday DATE NOT NULL,
    age INT NOT NULL,
    contact_number VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    barangay VARCHAR(100) NULL, -- Matches 'name' in barangays table
    account_status ENUM('pending', 'active', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// 4. CREATE REQUESTS TABLE
$sql = "CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    doc_type VARCHAR(100) NOT NULL,
    purpose TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($sql);

// 5. CREATE AUDIT LOGS
$conn->query("CREATE TABLE audit_logs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, user_role VARCHAR(50), action VARCHAR(255), timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

// 6. DEFAULT CITY ADMIN
$pass = password_hash("admin123", PASSWORD_DEFAULT);
$conn->query("INSERT INTO users (username, password, role, first_name, last_name, sex, birthday, age, contact_number, address, barangay, account_status) 
VALUES ('city_admin', '$pass', 'city_admin', 'Mayor', 'Staff', 'Male', '1980-01-01', 44, '09123456789', 'Bacoor City Hall', 'City Hall', 'active')");

echo "<h2>System Updated: Barangays & Captains Loaded. <a href='index.php'>Go to Login</a></h2>";
?>