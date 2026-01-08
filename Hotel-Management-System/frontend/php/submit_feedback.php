<?php
session_start();

// Include configuration file
require_once __DIR__ . '/../../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

function redirectTo(string $location): void
{
    header("Location: {$location}");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data (email removed for anonymous feedback)
    $userName = trim($_POST['userName'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $roomID = intval($_POST['roomID'] ?? 0);
    $comments = trim($_POST['comments'] ?? '');

    // Validation
    $errors = [];

    if (empty($userName) || strlen($userName) > 300) {
        $errors[] = "Please enter a valid name (max 300 characters).";
    }



    if ($rating < 1 || $rating > 5) {
        $errors[] = "Please select a rating between 1 and 5 stars.";
    }

    if ($roomID <= 0) {
        $errors[] = "Please select a valid room.";
    }

    if (empty($comments) || strlen($comments) < 10) {
        $errors[] = "Please enter comments (minimum 10 characters).";
    }

    // Verify room exists
    if ($roomID > 0) {
        $roomCheck = $conn->prepare("SELECT roomID FROM rooms WHERE roomID = ?");
        $roomCheck->bind_param("i", $roomID);
        $roomCheck->execute();
        if ($roomCheck->get_result()->num_rows === 0) {
            $errors[] = "Selected room does not exist.";
        }
        $roomCheck->close();
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO feedback (userName, rating, roomID, comments) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $userName, $rating, $roomID, $comments);

        if ($stmt->execute()) {
            $_SESSION['feedback_success'] = "Thank you for your feedback! Your review has been submitted successfully.";
            $stmt->close();
            $conn->close();
            redirectTo("../../index.php");
        } else {
            $_SESSION['feedback_error'] = "An error occurred while submitting your feedback. Please try again.";
            $stmt->close();
            $conn->close();
            redirectTo("../userFeedback.php?error=1");
        }
    } else {
        $_SESSION['feedback_error'] = implode("<br>", $errors);
        $conn->close();
        redirectTo("../userFeedback.php?error=1");
    }
} else {
    redirectTo("../userFeedback.php");
}
