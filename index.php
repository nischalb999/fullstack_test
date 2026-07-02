<?php
// =========================================================================
// 1. DATABASE CONFIGURATION
// =========================================================================
$host = 'localhost';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$db   = 'school_db'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (\PDOException $e) {
    die("Database connection failed.");
}

// =========================================================================
// CONFIGURATION SETTINGS
// =========================================================================
$TABLE_NAME = 'students';       
$COL_1      = 'student_name';  // Form input1 & Search Filter 1
$COL_2      = 'roll_number';   // Form input2 & Search Filter 2
$COL_3      = 'grade';         // Form input3
$COL_4      = 'attendance_pct';// Form input4

// =========================================================================
// 2. LIVE AJAX SEARCH ENGINE
// =========================================================================
if (isset($_GET['ajax_search'])) {
    $search1 = isset($_GET['search1']) ? trim($_GET['search1']) : '';
    $search2 = isset($_GET['search2']) ? trim($_GET['search2']) : '';

    $query = "SELECT * FROM `$TABLE_NAME` WHERE 1=1";
    $params = [];

    if ($search1 !== '') {
        $query .= " AND `$COL_1` LIKE :search1";
        $params['search1'] = '%' . $search1 . '%';
    }
    if ($search2 !== '') {
        $query .= " AND `$COL_2` = :search2";
        $params['search2'] = $search2;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    if (count($records) > 0) {
        foreach ($records as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row[$COL_1]) . "</td>";
            echo "<td>" . htmlspecialchars($row[$COL_2]) . "</td>";
            echo "<td>" . htmlspecialchars($row[$COL_3]) . "</td>";
            echo "<td>" . htmlspecialchars($row[$COL_4]) . "%</td>";
            echo "<td>
                    <a href='index.php?action=edit&id=" . $row['id'] . "' style='color: orange; font-weight: bold; margin-right: 15px;'>Edit</a>
                    <a href='index.php?action=delete&id=" . $row['id'] . "' onclick='return confirm(\"Are you sure?\")' style='color: red; font-weight: bold;'>Delete</a>
                  </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6' style='text-align:center; color: gray;'>No matching records found.</td></tr>";
    }
    exit;
}

// =========================================================================
// 3. CREATE & UPDATE CONTROLLER
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $val1 = trim($_POST['input1']);
    $val2 = trim($_POST['input2']);
    $val3 = trim($_POST['input3']);
    $val4 = trim($_POST['input4']);
    
    $form_action = $_POST['form_action'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if (!empty($val1) && !empty($val2)) {
        if ($form_action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO `$TABLE_NAME` (`$COL_1`, `$COL_2`, `$COL_3`, `$COL_4`) VALUES (?, ?, ?, ?)");
            $stmt->execute([$val1, $val2, $val3, $val4]);
        } elseif ($form_action === 'edit' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE `$TABLE_NAME` SET `$COL_1` = ?, `$COL_2` = ?, `$COL_3` = ?, `$COL_4` = ? WHERE id = ?");
            $stmt->execute([$val1, $val2, $val3, $val4, $id]);
        }
    }
    header("Location: index.php");
    exit;
}

// =========================================================================
// 4. DELETE CONTROLLER
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM `$TABLE_NAME` WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    header("Location: index.php");
    exit;
}

// =========================================================================
// 5. EDIT MODE POPULATION FETCH
// =========================================================================
$edit_val1 = $edit_val2 = $edit_val3 = $edit_val4 = '';
$current_action = 'add';
$edit_id = 0;

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM `$TABLE_NAME` WHERE id = ?");
    $stmt->execute([$edit_id]);
    $recordToEdit = $stmt->fetch();
    if ($recordToEdit) {
        $edit_val1 = $recordToEdit[$COL_1];
        $edit_val2 = $recordToEdit[$COL_2];
        $edit_val3 = $recordToEdit[$COL_3];
        $edit_val4 = $recordToEdit[$COL_4];
        $current_action = 'edit';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Management System Workspace</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f5f7; margin: 40px; color: #333; }
        .wrapper { max-width: 1100px; margin: 0 auto; display: flex; gap: 30px; }
        .main-panel { flex: 2; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .side-panel { flex: 1; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); height: fit-content; }
        .search-row { display: flex; gap: 15px; margin-bottom: 20px; }
        input { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #2c3e50; color: white; }
        .btn { display: block; width: 100%; background: #27ae60; color: white; padding: 12px; text-align: center; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; text-decoration: none; }
        .btn-blue { background: #2980b9; }
        .cancel-btn { display: block; text-align: center; margin-top: 10px; color: #7f8c8d; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

<h2 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">💻 Workspace Dashboard</h2>

<div class="wrapper">
    
    <div class="main-panel">
        <h3>Records Directory</h3>
        
        <div class="search-row">
            <input type="text" id="searchFilter1" placeholder="Search by Student Name..." onkeyup="triggerLiveSearch()">
            <input type="text" id="searchFilter2" placeholder="Search by Roll Number..." onkeyup="triggerLiveSearch()">
        </div>

        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Roll Number</th>
                    <th>Grade</th>
                    <th>Attendance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tableContent">
                <!-- Controlled completely by AJAX -->
            </tbody>
        </table>
    </div>

    <div class="side-panel">
        <h3><?php echo $current_action === 'edit' ? "✏️ Edit Record" : "➕ Add New Entry"; ?></h3>
        <form action="index.php" method="POST">
            <input type="hidden" name="form_action" value="<?php echo $current_action; ?>">
            <input type="hidden" name="id" value="<?php echo $edit_id; ?>">

            <label>Student Name</label>
            <input type="text" name="input1" value="<?php echo htmlspecialchars($edit_val1); ?>" required>

            <label>Roll Number</label>
            <input type="text" name="input2" value="<?php echo htmlspecialchars($edit_val2); ?>" required>

            <label>Grade</label>
            <input type="text" name="input3" value="<?php echo htmlspecialchars($edit_val3); ?>" required>

            <label>Attendance (%)</label>
            <input type="text" name="input4" value="<?php echo htmlspecialchars($edit_val4); ?>" required>

            <button type="submit" class="btn <?php echo $current_action === 'edit' ? 'btn-blue' : ''; ?>">
                <?php echo $current_action === 'edit' ? "Save Updates" : "ADD"; ?>
            </button>
            
            <?php if ($current_action === 'edit'): ?>
                <a href="index.php" class="cancel-btn">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

</div>

<script>
function triggerLiveSearch() {
    let s1 = document.getElementById('searchFilter1').value;
    let s2 = document.getElementById('searchFilter2').value;

    fetch('index.php?ajax_search=1&search1=' + encodeURIComponent(s1) + '&search2=' + encodeURIComponent(s2))
        .then(res => res.text())
        .then(htmlOutput => {
            document.getElementById('tableContent').innerHTML = htmlOutput;
        });
}
window.onload = triggerLiveSearch;
</script>

</body>
</html>