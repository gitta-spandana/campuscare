<?php
session_start();

/* =========================
   DATABASE CONNECTION
========================= */
$conn = mysqli_connect("localhost", "root", "", "campuscare");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

/* =========================
   STATUS UPDATE
   ONLY ADMIN PAGE CAN DO THIS
========================= */

if (isset($_POST['update_status'])) {

    $id = intval($_POST['complaint_id']);
    $new_status = $_POST['status'];

    /* Get current status */
    $check = mysqli_query(
        $conn,
        "SELECT status FROM complaints WHERE id = $id"
    );

    $row = mysqli_fetch_assoc($check);

    if ($row) {

        $current_status = $row['status'];

        /*
        Allowed workflow:

        Pending
           ↓
        Assigned
           ↓
        In Progress
           ↓
        Resolved
        */

        $allowed = false;

        if ($current_status == "Pending" && $new_status == "Assigned") {
            $allowed = true;
        }

        if ($current_status == "Assigned" && $new_status == "In Progress") {
            $allowed = true;
        }

        if ($current_status == "In Progress" && $new_status == "Resolved") {
            $allowed = true;
        }

        /* Prevent changing Resolved complaints */
        if ($current_status == "Resolved") {
            $allowed = false;
        }

        if ($allowed) {

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE complaints SET status = ? WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $new_status,
                $id
            );

            mysqli_stmt_execute($stmt);

            /*
            Store status history
            Only if complaint_history table exists
            */
            $history_table = mysqli_query(
                $conn,
                "SHOW TABLES LIKE 'complaint_history'"
            );

            if (mysqli_num_rows($history_table) > 0) {

                $history = mysqli_prepare(
                    $conn,
                    "INSERT INTO complaint_history
                    (complaint_id, old_status, new_status)
                    VALUES (?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $history,
                    "iss",
                    $id,
                    $current_status,
                    $new_status
                );

                mysqli_stmt_execute($history);
            }

            header("Location: admin.php?updated=1");
            exit();

        } else {

            header("Location: admin.php?error=invalid");
            exit();
        }
    }
}

/* =========================
   DASHBOARD COUNTS
========================= */

$total = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS count FROM complaints"
    )
)['count'];

$pending = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS count FROM complaints WHERE status='Pending'"
    )
)['count'];

$assigned = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS count FROM complaints WHERE status='Assigned'"
    )
)['count'];

$progress = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS count FROM complaints WHERE status='In Progress'"
    )
)['count'];

$resolved = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS count FROM complaints WHERE status='Resolved'"
    )
)['count'];

$high = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS count FROM complaints WHERE priority='High'"
    )
)['count'];

$medium = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS count FROM complaints WHERE priority='Medium'"
    )
)['count'];

$low = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS count FROM complaints WHERE priority='Low'"
    )
)['count'];

/* =========================
   DEPARTMENT ANALYTICS
========================= */

$departments = mysqli_query(
    $conn,
    "SELECT department, COUNT(*) AS total
     FROM complaints
     GROUP BY department
     ORDER BY total DESC"
);

/* =========================
   SEARCH
========================= */

$search = "";

if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string(
        $conn,
        $_GET['search']
    );
}

if ($search != "") {

    $complaints = mysqli_query(
        $conn,
        "SELECT *
         FROM complaints
         WHERE student_name LIKE '%$search%'
         OR title LIKE '%$search%'
         OR category LIKE '%$search%'
         OR location LIKE '%$search%'
         OR department LIKE '%$search%'
         ORDER BY id DESC"
    );

} else {

    $complaints = mysqli_query(
        $conn,
        "SELECT *
         FROM complaints
         ORDER BY id DESC"
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>CampusCare Admin Dashboard</title>

<style>

/* =========================
   GENERAL
========================= */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6fb;
    color: #1f2937;
}

/* =========================
   SIDEBAR
========================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;

    width: 240px;
    height: 100vh;

    background: #111827;
    color: white;

    padding: 25px 18px;
}

.logo {
    font-size: 25px;
    font-weight: bold;

    margin-bottom: 35px;
    text-align: center;
}

.logo span {
    color: #60a5fa;
}

.nav {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.nav a {
    text-decoration: none;
    color: #d1d5db;

    padding: 13px 15px;
    border-radius: 10px;

    transition: 0.2s;
}

.nav a:hover,
.nav a.active {
    background: #2563eb;
    color: white;
}

/* =========================
   MAIN
========================= */

.main {
    margin-left: 240px;
    padding: 30px;
}

/* =========================
   TOPBAR
========================= */

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;

    margin-bottom: 25px;
}

.topbar h1 {
    margin: 0;
    font-size: 28px;
}

.admin-badge {
    background: white;
    padding: 10px 16px;

    border-radius: 20px;

    box-shadow: 0 3px 10px rgba(0,0,0,0.06);
}

/* =========================
   ALERT
========================= */

.alert {
    padding: 14px 18px;

    border-radius: 10px;
    margin-bottom: 20px;

    background: #dcfce7;
    color: #166534;
}

.error {
    background: #fee2e2;
    color: #991b1b;
}

/* =========================
   KPI CARDS
========================= */

.cards {
    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(180px, 1fr));

    gap: 18px;

    margin-bottom: 30px;
}

.card {
    background: white;

    padding: 20px;

    border-radius: 15px;

    box-shadow:
        0 4px 15px rgba(0,0,0,0.06);
}

.card-title {
    color: #6b7280;
    font-size: 14px;
}

.card-number {
    font-size: 30px;
    font-weight: bold;

    margin-top: 8px;
}

/* =========================
   STATUS CARDS
========================= */

.status-section {
    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(180px, 1fr));

    gap: 15px;

    margin-bottom: 30px;
}

.status-card {
    background: white;

    padding: 18px;

    border-radius: 13px;

    border-left: 5px solid #2563eb;
}

/* =========================
   ANALYTICS
========================= */

.analytics {
    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(300px, 1fr));

    gap: 20px;

    margin-bottom: 30px;
}

.analytics-box {
    background: white;

    padding: 22px;

    border-radius: 15px;

    box-shadow:
        0 4px 15px rgba(0,0,0,0.06);
}

.analytics-box h2 {
    margin-top: 0;
}

/* =========================
   SEARCH
========================= */

.search-box {
    background: white;

    padding: 18px;

    border-radius: 15px;

    margin-bottom: 20px;
}

.search-box form {
    display: flex;
    gap: 10px;
}

.search-box input {
    flex: 1;

    padding: 12px;

    border: 1px solid #d1d5db;

    border-radius: 8px;

    outline: none;
}

.search-box button {
    padding: 12px 20px;

    border: none;

    background: #2563eb;
    color: white;

    border-radius: 8px;

    cursor: pointer;
}

/* =========================
   TABLE
========================= */

.table-box {
    background: white;

    padding: 20px;

    border-radius: 15px;

    box-shadow:
        0 4px 15px rgba(0,0,0,0.06);

    overflow-x: auto;
}

.table-box h2 {
    margin-top: 0;
}

table {
    width: 100%;

    border-collapse: collapse;

    min-width: 1050px;
}

th {
    background: #f3f4f6;

    padding: 13px;

    text-align: left;

    font-size: 13px;
}

td {
    padding: 13px;

    border-bottom: 1px solid #e5e7eb;

    font-size: 13px;
}

/* =========================
   STATUS BADGES
========================= */

.badge {
    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;
}

.pending {
    background: #fef3c7;
    color: #92400e;
}

.assigned {
    background: #dbeafe;
    color: #1e40af;
}

.progress {
    background: #ede9fe;
    color: #6d28d9;
}

.resolved {
    background: #dcfce7;
    color: #166534;
}

/* =========================
   STATUS UPDATE
========================= */

.status-form {
    display: flex;

    align-items: center;

    gap: 7px;
}

.status-form select {
    padding: 7px;

    border: 1px solid #d1d5db;

    border-radius: 7px;

    font-size: 12px;

    background: white;
}

.status-form button {
    padding: 7px 10px;

    border: none;

    border-radius: 7px;

    background: #2563eb;

    color: white;

    cursor: pointer;

    font-size: 12px;
}

.status-form button:hover {
    background: #1d4ed8;
}

.locked {
    color: #16a34a;
    font-size: 12px;
    font-weight: bold;
}

/* =========================
   DEPARTMENT
========================= */

.department-row {
    margin-bottom: 15px;
}

.department-name {
    display: flex;

    justify-content: space-between;

    margin-bottom: 6px;

    font-size: 14px;
}

.bar {
    height: 9px;

    background: #e5e7eb;

    border-radius: 20px;

    overflow: hidden;
}

.bar-fill {
    height: 100%;

    background: #2563eb;
}

/* =========================
   RESPONSIVE
========================= */

@media(max-width: 800px) {

    .sidebar {
        position: relative;

        width: 100%;
        height: auto;
    }

    .main {
        margin-left: 0;
    }

    .nav {
        flex-direction: row;
        flex-wrap: wrap;
    }

    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

}

</style>

</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

    <div class="logo">
        Campus<span>Care</span>
    </div>

    <div class="nav">

        <a href="admin.php" class="active">
            📊 Dashboard
        </a>

        <a href="index.php">
            🎓 Student Portal
        </a>

        <a href="track.php">
            🔍 Track Complaint
        </a>

        <a href="logout.php">
            🚪 Logout
        </a>

    </div>

</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">

    <div class="topbar">

        <div>
            <h1>Admin Dashboard</h1>

            <p>
                Manage campus complaints and monitor resolution.
            </p>
        </div>

        <div class="admin-badge">
            👨‍💼 Administrator
        </div>

    </div>


    <?php if(isset($_GET['updated'])): ?>

        <div class="alert">
            ✅ Complaint status updated successfully.
        </div>

    <?php endif; ?>


    <?php if(isset($_GET['error'])): ?>

        <div class="alert error">
            ❌ Invalid status transition.
        </div>

    <?php endif; ?>


    <!-- =========================
         MAIN KPI
    ========================= -->

    <div class="cards">

        <div class="card">
            <div class="card-title">
                Total Complaints
            </div>

            <div class="card-number">
                <?= $total ?>
            </div>
        </div>


        <div class="card">
            <div class="card-title">
                High Priority
            </div>

            <div class="card-number">
                <?= $high ?>
            </div>
        </div>


        <div class="card">
            <div class="card-title">
                Medium Priority
            </div>

            <div class="card-number">
                <?= $medium ?>
            </div>
        </div>


        <div class="card">
            <div class="card-title">
                Low Priority
            </div>

            <div class="card-number">
                <?= $low ?>
            </div>
        </div>

    </div>


    <!-- =========================
         STATUS
    ========================= -->

    <div class="status-section">

        <div class="status-card">
            <strong>Pending</strong>

            <div class="card-number">
                <?= $pending ?>
            </div>
        </div>


        <div class="status-card">
            <strong>Assigned</strong>

            <div class="card-number">
                <?= $assigned ?>
            </div>
        </div>


        <div class="status-card">
            <strong>In Progress</strong>

            <div class="card-number">
                <?= $progress ?>
            </div>
        </div>


        <div class="status-card">
            <strong>Resolved</strong>

            <div class="card-number">
                <?= $resolved ?>
            </div>
        </div>

    </div>


    <!-- =========================
         ANALYTICS
    ========================= -->

    <div class="analytics">

        <div class="analytics-box">

            <h2>📊 Complaint Status</h2>

            <p>
                Pending: <strong><?= $pending ?></strong>
            </p>

            <p>
                Assigned: <strong><?= $assigned ?></strong>
            </p>

            <p>
                In Progress: <strong><?= $progress ?></strong>
            </p>

            <p>
                Resolved: <strong><?= $resolved ?></strong>
            </p>

        </div>


        <div class="analytics-box">

            <h2>🏢 Department-wise Complaints</h2>

            <?php

            $max_department = 1;

            $department_data = [];

            while($d = mysqli_fetch_assoc($departments)) {

                $department_data[] = $d;

                if($d['total'] > $max_department) {
                    $max_department = $d['total'];
                }
            }

            ?>


            <?php foreach($department_data as $d): ?>

                <div class="department-row">

                    <div class="department-name">

                        <span>
                            <?= htmlspecialchars($d['department']) ?>
                        </span>

                        <strong>
                            <?= $d['total'] ?>
                        </strong>

                    </div>

                    <div class="bar">

                        <div
                            class="bar-fill"
                            style="width:
                            <?= ($d['total'] / $max_department) * 100 ?>%">
                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>


    <!-- =========================
         SEARCH
    ========================= -->

    <div class="search-box">

        <form method="GET">

            <input
                type="text"
                name="search"
                placeholder="Search complaints..."
                value="<?= htmlspecialchars($search) ?>"
            >

            <button type="submit">
                🔍 Search
            </button>

        </form>

    </div>


    <!-- =========================
         COMPLAINT TABLE
    ========================= -->

    <div class="table-box">

        <h2>📋 Complaint Management</h2>

        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Student</th>

                    <th>Title</th>

                    <th>Category</th>

                    <th>Location</th>

                    <th>Department</th>

                    <th>Priority</th>

                    <th>Status</th>

                    <th>Update</th>

                </tr>

            </thead>


            <tbody>

            <?php while($c = mysqli_fetch_assoc($complaints)): ?>

                <tr>

                    <td>
                        CC<?= str_pad($c['id'], 3, "0", STR_PAD_LEFT) ?>
                    </td>


                    <td>
                        <?= htmlspecialchars($c['student_name']) ?>
                    </td>


                    <td>
                        <?= htmlspecialchars($c['title']) ?>
                    </td>


                    <td>
                        <?= htmlspecialchars($c['category']) ?>
                    </td>


                    <td>
                        <?= htmlspecialchars($c['location']) ?>
                    </td>


                    <td>
                        <?= htmlspecialchars($c['department']) ?>
                    </td>


                    <td>

                        <span class="badge pending">

                            <?= htmlspecialchars($c['priority']) ?>

                        </span>

                    </td>


                    <td>

                        <?php

                        $status_class = "pending";

                        if($c['status'] == "Assigned") {
                            $status_class = "assigned";
                        }

                        if($c['status'] == "In Progress") {
                            $status_class = "progress";
                        }

                        if($c['status'] == "Resolved") {
                            $status_class = "resolved";
                        }

                        ?>

                        <span class="badge <?= $status_class ?>">

                            <?= htmlspecialchars($c['status']) ?>

                        </span>

                    </td>


                    <td>

                        <?php if($c['status'] == "Pending"): ?>

                            <form
                                method="POST"
                                class="status-form"
                            >

                                <input
                                    type="hidden"
                                    name="complaint_id"
                                    value="<?= $c['id'] ?>"
                                >

                                <select name="status">

                                    <option value="Assigned">
                                        Assigned
                                    </option>

                                </select>

                                <button
                                    type="submit"
                                    name="update_status"
                                >
                                    Update
                                </button>

                            </form>


                        <?php elseif($c['status'] == "Assigned"): ?>

                            <form
                                method="POST"
                                class="status-form"
                            >

                                <input
                                    type="hidden"
                                    name="complaint_id"
                                    value="<?= $c['id'] ?>"
                                >

                                <select name="status">

                                    <option value="In Progress">
                                        In Progress
                                    </option>

                                </select>

                                <button
                                    type="submit"
                                    name="update_status"
                                >
                                    Update
                                </button>

                            </form>


                        <?php elseif($c['status'] == "In Progress"): ?>

                            <form
                                method="POST"
                                class="status-form"
                            >

                                <input
                                    type="hidden"
                                    name="complaint_id"
                                    value="<?= $c['id'] ?>"
                                >

                                <select name="status">

                                    <option value="Resolved">
                                        Resolved
                                    </option>

                                </select>

                                <button
                                    type="submit"
                                    name="update_status"
                                >
                                    Update
                                </button>

                            </form>


                        <?php else: ?>

                            <span class="locked">
                                🔒 Closed
                            </span>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>