<?php
include "db.php";

$complaint = null;
$error = "";
$message = "";
$search_id = "";

if (isset($_GET["id"])) {
    $search_id = trim($_GET["id"]);
}

if (isset($_POST["complaint_id"])) {
    $search_id = trim($_POST["complaint_id"]);
}

$db_id = 0;

if ($search_id != "") {

    $clean_id = strtoupper($search_id);

    if (substr($clean_id, 0, 2) == "CC") {

        $number = substr($clean_id, 2);

        if (is_numeric($number)) {
            $db_id = intval($number);
        }
    }
}


/* Save Feedback */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST["feedback"])) {

        $feedback = $_POST["feedback"];

        if ($db_id > 0) {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO complaint_feedback (complaint_id, feedback)
                 VALUES (?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "is",
                $db_id,
                $feedback
            );

            if (mysqli_stmt_execute($stmt)) {
                $message = "Thank you! Your feedback has been recorded.";
            } else {
                $error = "Unable to save feedback.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}


/* Get Complaint */

if ($db_id > 0) {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM complaints WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $db_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {

        $complaint = mysqli_fetch_assoc($result);

    } else {

        $error = "Complaint ID not found.";
    }

    mysqli_stmt_close($stmt);
}


/* Status Rank */

$status_rank = 0;

if ($complaint != null) {

    if ($complaint["status"] == "Assigned") {
        $status_rank = 1;
    }

    if ($complaint["status"] == "In Progress") {
        $status_rank = 2;
    }

    if ($complaint["status"] == "Resolved") {
        $status_rank = 3;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Track Complaint - CampusCare</title>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #eef2ff, #f8f9ff);
    min-height: 100vh;
    color: #222;
}

.header {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    padding: 22px 8%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 28px;
    font-weight: bold;
}

.tagline {
    font-size: 14px;
    margin-top: 5px;
}

.home-btn {
    color: white;
    text-decoration: none;
    background: rgba(255,255,255,0.18);
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: bold;
}

.container {
    width: 90%;
    max-width: 900px;
    margin: 45px auto;
}

.search-card {
    background: white;
    padding: 30px;
    border-radius: 18px;
    box-shadow: 0 10px 35px rgba(0,0,0,0.10);
    text-align: center;
}

.search-card h1 {
    color: #312e81;
    margin-bottom: 10px;
}

.search-card p {
    color: #777;
    margin-bottom: 22px;
}

.search-form {
    display: flex;
    gap: 10px;
}

.search-form input {
    flex: 1;
    padding: 14px;
    border: 1px solid #ccc;
    border-radius: 10px;
    font-size: 16px;
}

.search-form button {
    padding: 14px 25px;
    border: none;
    border-radius: 10px;
    background: #4f46e5;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.error {
    background: #fee2e2;
    color: #b91c1c;
    padding: 14px;
    border-radius: 10px;
    margin-top: 20px;
}

.success {
    background: #dcfce7;
    color: #166534;
    padding: 14px;
    border-radius: 10px;
    margin-top: 20px;
}

.complaint-card {
    background: white;
    margin-top: 25px;
    padding: 30px;
    border-radius: 18px;
    box-shadow: 0 10px 35px rgba(0,0,0,0.10);
}

.complaint-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.complaint-header h2 {
    color: #312e81;
}

.id-badge {
    background: #eef2ff;
    color: #4338ca;
    padding: 10px 16px;
    border-radius: 9px;
    font-weight: bold;
}

.title-box {
    background: #f8fafc;
    padding: 18px;
    border-radius: 12px;
    margin-bottom: 22px;
}

.title-box h3 {
    margin-bottom: 8px;
}

.title-box p {
    color: #666;
    line-height: 1.5;
}

.details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.detail {
    background: #f8fafc;
    padding: 15px;
    border-radius: 10px;
}

.detail-label {
    color: #777;
    font-size: 13px;
    margin-bottom: 5px;
}

.detail-value {
    font-weight: bold;
    color: #312e81;
}

.status-section {
    margin-top: 35px;
}

.status-section h2 {
    text-align: center;
    color: #312e81;
    margin-bottom: 30px;
}

.timeline {
    max-width: 650px;
    margin: auto;
}

.step {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 25px;
}

.circle {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: bold;
    font-size: 20px;
}

.completed {
    background: #4f46e5;
    color: white;
}

.current {
    background: #16a34a;
    color: white;
}

.step-text h3 {
    margin-bottom: 5px;
}

.step-text p {
    color: #777;
    font-size: 14px;
}

.feedback {
    margin-top: 35px;
    padding: 25px;
    background: #f8fafc;
    border-radius: 14px;
    text-align: center;
}

.feedback h2 {
    color: #312e81;
    margin-bottom: 8px;
}

.feedback p {
    color: #777;
    margin-bottom: 20px;
}

.feedback-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.feedback button {
    border: none;
    padding: 13px 20px;
    border-radius: 9px;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.yes {
    background: #16a34a;
}

.no {
    background: #dc2626;
}

.footer {
    text-align: center;
    padding: 25px;
    color: #777;
}

@media(max-width:650px) {

    .header {
        padding: 18px 5%;
    }

    .logo {
        font-size: 22px;
    }

    .search-form {
        flex-direction: column;
    }

    .details {
        grid-template-columns: 1fr;
    }

    .complaint-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .feedback-buttons {
        flex-direction: column;
    }

}

</style>

</head>

<body>

<div class="header">

```
<div>

    <div class="logo">
        🏫 CampusCare
    </div>

    <div class="tagline">
        Smart Campus Complaint & Grievance Management
    </div>

</div>

<a href="index.php" class="home-btn">
    🏠 Student Portal
</a>
```

</div>

<div class="container">

<div class="search-card">

```
<h1>📍 Track Your Complaint</h1>

<p>
    Enter your Complaint ID to check your complaint status.
</p>

<form method="GET" action="track.php" class="search-form">

    <input
        type="text"
        name="id"
        placeholder="Example: CC011"
        value="<?php echo htmlspecialchars($search_id); ?>"
        required
    >

    <button type="submit">
        🔍 Track
    </button>

</form>


<?php if ($error != "") { ?>

    <div class="error">
        <?php echo htmlspecialchars($error); ?>
    </div>

<?php } ?>


<?php if ($message != "") { ?>

    <div class="success">
        <?php echo htmlspecialchars($message); ?>
    </div>

<?php } ?>
```

</div>

<?php if ($complaint != null) { ?>

<div class="complaint-card">

```
<div class="complaint-header">

    <h2>Complaint Details</h2>

    <div class="id-badge">

        CC<?php echo str_pad(
            $complaint["id"],
            3,
            "0",
            STR_PAD_LEFT
        ); ?>

    </div>

</div>


<div class="title-box">

    <h3>
        <?php echo htmlspecialchars($complaint["title"]); ?>
    </h3>

    <p>
        <?php echo htmlspecialchars($complaint["description"]); ?>
    </p>

</div>


<div class="details">


    <div class="detail">

        <div class="detail-label">
            Student
        </div>

        <div class="detail-value">
            <?php echo htmlspecialchars($complaint["student_name"]); ?>
        </div>

    </div>


    <div class="detail">

        <div class="detail-label">
            Category
        </div>

        <div class="detail-value">
            <?php echo htmlspecialchars($complaint["category"]); ?>
        </div>

    </div>


    <div class="detail">

        <div class="detail-label">
            Location
        </div>

        <div class="detail-value">
            <?php echo htmlspecialchars($complaint["location"]); ?>
        </div>

    </div>


    <div class="detail">

        <div class="detail-label">
            Department
        </div>

        <div class="detail-value">
            <?php echo htmlspecialchars($complaint["department"]); ?>
        </div>

    </div>


    <div class="detail">

        <div class="detail-label">
            Priority
        </div>

        <div class="detail-value">
            <?php echo htmlspecialchars($complaint["priority"]); ?>
        </div>

    </div>


    <div class="detail">

        <div class="detail-label">
            Current Status
        </div>

        <div class="detail-value">
            <?php echo htmlspecialchars($complaint["status"]); ?>
        </div>

    </div>


</div>


<div class="status-section">

    <h2>📊 Complaint Progress</h2>


    <div class="timeline">


        <div class="step">

            <div class="circle completed">
                ✓
            </div>

            <div class="step-text">

                <h3>
                    Complaint Submitted
                </h3>

                <p>
                    Complaint successfully registered.
                </p>

            </div>

        </div>


        <div class="step">

            <div class="circle
            <?php
            if ($status_rank >= 1) {
                echo "completed";
            }
            ?>
            ">

            <?php
            if ($status_rank >= 1) {
                echo "✓";
            } else {
                echo "2";
            }
            ?>

            </div>

            <div class="step-text">

                <h3>
                    Assigned
                </h3>

                <p>
                    Complaint assigned to the concerned department.
                </p>

            </div>

        </div>


        <div class="step">

            <div class="circle
            <?php
            if ($status_rank >= 2) {
                echo "completed";
            }
            ?>
            ">

            <?php
            if ($status_rank >= 2) {
                echo "✓";
            } else {
                echo "3";
            }
            ?>

            </div>

            <div class="step-text">

                <h3>
                    In Progress
                </h3>

                <p>
                    Department is working on the complaint.
                </p>

            </div>

        </div>


        <div class="step">

            <div class="circle
            <?php
            if ($status_rank >= 3) {
                echo "current";
            }
            ?>
            ">

            <?php
            if ($status_rank >= 3) {
                echo "✓";
            } else {
                echo "4";
            }
            ?>

            </div>

            <div class="step-text">

                <h3>
                    Resolved
                </h3>

                <p>
                    Complaint has been resolved.
                </p>

            </div>

        </div>


    </div>

</div>


<?php if ($complaint["status"] == "Resolved") { ?>


<div class="feedback">

    <h2>⭐ Was Your Issue Resolved?</h2>

    <p>
        Your feedback helps improve campus services.
    </p>


    <div class="feedback-buttons">


        <form method="POST">

            <input
                type="hidden"
                name="complaint_id"
                value="<?php echo htmlspecialchars($search_id); ?>"
            >

            <button
                type="submit"
                name="feedback"
                value="Resolved"
                class="yes"
            >
                👍 Yes, Issue Resolved
            </button>

        </form>


        <form method="POST">

            <input
                type="hidden"
                name="complaint_id"
                value="<?php echo htmlspecialchars($search_id); ?>"
            >

            <button
                type="submit"
                name="feedback"
                value="Not Resolved"
                class="no"
            >
                ❌ Still Not Resolved
            </button>

        </form>


    </div>

</div>


<?php } ?>
```

</div>

<?php } ?>

</div>

<div class="footer">

```
© 2026 CampusCare | Smart Campus Complaint Management System
```

</div>

</body>

</html>
