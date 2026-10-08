<?php
session_start();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST["username"];
    $password = $_POST["password"];

    // Event prototype admin credentials
    if ($username == "admin" && $password == "admin123") {

        $_SESSION["admin_logged_in"] = true;

        header("Location: admin.php");
        exit();

    } else {
        $message = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>CampusCare Admin Login</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-box {
            background: white;
            width: 350px;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }

        h1 {
            color: #333;
        }

        input {
            width: 90%;
            padding: 12px;
            margin: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        button {
            width: 95%;
            padding: 12px;
            margin-top: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #5568d9;
        }

        .error {
            color: red;
            margin-top: 15px;
        }

        .icon {
            font-size: 45px;
        }
    </style>
</head>

<body>

<div class="login-box">

    <div class="icon">🔐</div>

    <h1>CampusCare</h1>
    <h3>Admin Login</h3>

    <form method="POST">

        <input
            type="text"
            name="username"
            placeholder="Username"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="Password"
            required
        >

        <button type="submit">
            Login
        </button>

    </form>

    <?php
    if ($message != "") {
        echo "<div class='error'>$message</div>";
    }
    ?>

</div>

</body>
</html>