<?php
include "db.php";

$success = false;
$complaint_id = "";
$department = "";
$priority = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $student_name = trim($_POST["student_name"] ?? "");
    $title = trim($_POST["title"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if ($student_name == "" || $title == "" || $category == "" || $location == "" || $description == "") {
        $error = "Please fill all the fields.";
    } else {

        /* Smart Department Recommendation */

        if ($category == "Electrical") {
            $department = "Electrical Department";
        } elseif ($category == "IT Support") {
            $department = "IT Support Department";
        } elseif ($category == "Maintenance") {
            $department = "Maintenance Department";
        } elseif ($category == "Hostel") {
            $department = "Hostel Administration";
        } elseif ($category == "Library") {
            $department = "Library Department";
        } else {
            $department = "General Administration";
        }

        /* Smart Priority Detection */

        $text = strtolower($title . " " . $description);

        $high_words = array(
            "fire",
            "electric shock",
            "short circuit",
            "danger",
            "emergency",
            "not working",
            "broken",
            "water leakage",
            "leakage",
            "security"
        );

        $medium_words = array(
            "problem",
            "issue",
            "slow",
            "damage",
            "noise",
            "dirty"
        );

        $priority = "Low";

        foreach ($high_words as $word) {
            if (strpos($text, $word) !== false) {
                $priority = "High";
                break;
            }
        }

        if ($priority == "Low") {
            foreach ($medium_words as $word) {
                if (strpos($text, $word) !== false) {
                    $priority = "Medium";
                    break;
                }
            }
        }

        $status = "Pending";

        /* Insert Complaint */

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO complaints
            (student_name, title, category, location, description, priority, status, department)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "ssssssss",
                $student_name,
                $title,
                $category,
                $location,
                $description,
                $priority,
                $status,
                $department
            );

            if (mysqli_stmt_execute($stmt)) {

                $db_id = mysqli_insert_id($conn);

                $complaint_id = "CC" . str_pad(
                    $db_id,
                    3,
                    "0",
                    STR_PAD_LEFT
                );

                $success = true;

            } else {
                $error = "Unable to submit complaint. Please try again.";
            }

            mysqli_stmt_close($stmt);

        } else {
            $error = "Database error. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>

<html lang="en">

<head>

```
<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>CampusCare - Smart Campus Complaint System</title>

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

    /* Header */

    .header {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        padding: 22px 8%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .logo {
        font-size: 28px;
        font-weight: bold;
    }

    .tagline {
        font-size: 14px;
        opacity: 0.9;
        margin-top: 4px;
    }

    .admin-btn {
        text-decoration: none;
        color: white;
        background: rgba(255,255,255,0.15);
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: bold;
    }

    .admin-btn:hover {
        background: rgba(255,255,255,0.25);
    }

    /* Hero */

    .hero {
        text-align: center;
        padding: 55px 20px 30px;
    }

    .hero h1 {
        font-size: 38px;
        color: #312e81;
        margin-bottom: 12px;
    }

    .hero p {
        color: #666;
        font-size: 17px;
        max-width: 700px;
        margin: auto;
        line-height: 1.6;
    }

    /* Main Card */

    .container {
        width: 90%;
        max-width: 850px;
        margin: 25px auto 60px;
    }

    .card {
        background: white;
        border-radius: 18px;
        padding: 35px;
        box-shadow: 0 10px 35px rgba(0,0,0,0.10);
    }

    .card-title {
        text-align: center;
        margin-bottom: 28px;
    }

    .card-title h2 {
        color: #312e81;
        margin-bottom: 8px;
    }

    .card-title p {
        color: #777;
    }

    /* Form */

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        font-weight: bold;
        margin-bottom: 8px;
        color: #333;
    }

    input,
    select,
    textarea {
        width: 100%;
        padding: 13px 15px;
        border: 1px solid #d5d5d5;
        border-radius: 10px;
        font-size: 15px;
        outline: none;
        transition: 0.3s;
    }

    input:focus,
    select:focus,
    textarea:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
    }

    textarea {
        resize: vertical;
        min-height: 120px;
    }

    .submit-btn {
        width: 100%;
        border: none;
        padding: 15px;
        border-radius: 10px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        font-size: 17px;
        font-weight: bold;
        cursor: pointer;
        margin-top: 8px;
    }

    .submit-btn:hover {
        opacity: 0.92;
        transform: translateY(-1px);
    }

    /* Error */

    .error {
        background: #fee2e2;
        color: #b91c1c;
        padding: 14px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: bold;
    }

    /* Success */

    .success-box {
        text-align: center;
    }

    .success-icon {
        font-size: 60px;
        margin-bottom: 10px;
    }

    .success-box h2 {
        color: #15803d;
        margin-bottom: 10px;
    }

    .complaint-id {
        display: inline-block;
        background: #eef2ff;
        color: #4338ca;
        padding: 12px 25px;
        border-radius: 10px;
        font-size: 24px;
        font-weight: bold;
        margin: 15px 0 25px;
    }

    /* Smart Analysis */

    .analysis {
        background: #f8fafc;
        border-radius: 14px;
        padding: 20px;
        margin: 20px 0;
        text-align: left;
    }

    .analysis h3 {
        color: #312e81;
        margin-bottom: 15px;
    }

    .analysis-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .analysis-row:last-child {
        border-bottom: none;
    }

    .analysis-label {
        font-weight: bold;
        color: #555;
    }

    .analysis-value {
        font-weight: bold;
        color: #312e81;
    }

    .status-pending {
        color: #d97706;
    }

    .track-btn {
        display: inline-block;
        text-decoration: none;
        background: #16a34a;
        color: white;
        padding: 13px 22px;
        border-radius: 9px;
        font-weight: bold;
        margin: 8px;
    }

    .new-btn {
        display: inline-block;
        text-decoration: none;
        background: #4f46e5;
        color: white;
        padding: 13px 22px;
        border-radius: 9px;
        font-weight: bold;
        margin: 8px;
    }

    /* Features */

    .features {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-top: 25px;
    }

    .feature {
        background: white;
        padding: 22px;
        border-radius: 14px;
        text-align: center;
        box-shadow: 0 5px 20px rgba(0,0,0,0.07);
    }

    .feature-icon {
        font-size: 32px;
        margin-bottom: 10px;
    }

    .feature h3 {
        color: #312e81;
        margin-bottom: 7px;
    }

    .feature p {
        color: #777;
        font-size: 14px;
        line-height: 1.5;
    }

    /* Footer */

    .footer {
        text-align: center;
        padding: 25px;
        color: #777;
        font-size: 14px;
    }

    /* Mobile */

    @media (max-width: 700px) {

        .header {
            padding: 18px 5%;
        }

        .logo {
            font-size: 22px;
        }

        .tagline {
            font-size: 12px;
        }

        .admin-btn {
            padding: 8px 12px;
            font-size: 13px;
        }

        .hero h1 {
            font-size: 29px;
        }

        .card {
            padding: 22px;
        }

        .features {
            grid-template-columns: 1fr;
        }

        .analysis-row {
            flex-direction: column;
            gap: 5px;
        }
    }

</style>
```

</head>

<body>

```
<div class="header">

    <div>
        <div class="logo">🏫 CampusCare</div>
        <div class="tagline">Smart Campus Complaint & Grievance Management</div>
    </div>

    <a href="admin_login.php" class="admin-btn">
        🔐 Admin Login
    </a>

</div>


<section class="hero">

    <h1>Smart Campus Complaint System</h1>

    <p>
        Report campus issues easily and track their resolution.
        CampusCare automatically recommends the department and
        determines complaint priority.
    </p>

</section>


<div class="container">

    <div class="card">

        <?php if ($success): ?>

            <div class="success-box">

                <div class="success-icon">✅</div>

                <h2>Complaint Submitted Successfully!</h2>

                <p>Your complaint has been registered in CampusCare.</p>

                <div class="complaint-id">
                    <?php echo htmlspecialchars($complaint_id); ?>
                </div>

                <div class="analysis">

                    <h3>🤖 Smart Analysis</h3>

                    <div class="analysis-row">

                        <span class="analysis-label">
                            Recommended Department
                        </span>

                        <span class="analysis-value">
                            <?php echo htmlspecialchars($department); ?>
                        </span>

                    </div>

                    <div class="analysis-row">

                        <span class="analysis-label">
                            Priority
                        </span>

                        <span class="analysis-value">
                            <?php echo htmlspecialchars($priority); ?>
                        </span>

                    </div>

                    <div class="analysis-row">

                        <span class="analysis-label">
                            Current Status
                        </span>

                        <span class="analysis-value status-pending">
                            Pending
                        </span>

                    </div>

                </div>

                <a
                    href="track.php?id=<?php echo urlencode($complaint_id); ?>"
                    class="track-btn"
                >
                    📍 Track Complaint
                </a>

                <a
                    href="index.php"
                    class="new-btn"
                >
                    ➕ Submit Another
                </a>

            </div>

        <?php else: ?>

            <div class="card-title">

                <h2>📝 Submit a Complaint</h2>

                <p>
                    Tell us about the issue and our system will
                    intelligently process it.
                </p>

            </div>


            <?php if ($error != ""): ?>

                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="">

                <div class="form-group">

                    <label for="student_name">
                        Student Name
                    </label>

                    <input
                        type="text"
                        id="student_name"
                        name="student_name"
                        placeholder="Enter your name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="title">
                        Complaint Title
                    </label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        placeholder="Example: Fan is not working"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="category">
                        Complaint Category
                    </label>

                    <select
                        id="category"
                        name="category"
                        required
                    >

                        <option value="">
                            Select Category
                        </option>

                        <option value="Electrical">
                            ⚡ Electrical
                        </option>

                        <option value="IT Support">
                            💻 IT Support
                        </option>

                        <option value="Maintenance">
                            🔧 Maintenance
                        </option>

                        <option value="Hostel">
                            🏠 Hostel
                        </option>

                        <option value="Library">
                            📚 Library
                        </option>

                        <option value="Security">
                            🛡️ Security
                        </option>

                        <option value="Other">
                            📌 Other
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="location">
                        Location
                    </label>

                    <input
                        type="text"
                        id="location"
                        name="location"
                        placeholder="Example: CSE H Block, Room 204"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="description">
                        Complaint Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Describe the issue clearly..."
                        required
                    ></textarea>

                </div>


                <button
                    type="submit"
                    class="submit-btn"
                >
                    🚀 Submit Complaint
                </button>

            </form>

        <?php endif; ?>

    </div>


    <div class="features">

        <div class="feature">

            <div class="feature-icon">🤖</div>

            <h3>Smart Analysis</h3>

            <p>
                Automatically recommends the correct department
                and complaint priority.
            </p>

        </div>


        <div class="feature">

            <div class="feature-icon">📍</div>

            <h3>Track Status</h3>

            <p>
                Track your complaint from submission to resolution
                using your Complaint ID.
            </p>

        </div>


        <div class="feature">

            <div class="feature-icon">📊</div>

            <h3>Admin Analytics</h3>

            <p>
                Administrators can monitor complaints, departments,
                priorities and feedback.
            </p>

        </div>

    </div>

</div>


<div class="footer">

    © 2026 CampusCare | Smart Campus Complaint Management System

</div>
```

</body>

</html>
