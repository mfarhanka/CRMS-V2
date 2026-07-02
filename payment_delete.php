<?php
require_once 'config/database.php';
require_once 'config/config.php';
requireLogin();

$conn = getDBConnection();

if (!isset($_GET['id']) || !isset($_GET['rental_id'])) {
    header('Location: rentals.php');
    exit;
}

$payment_id = (int)$_GET['id'];
$rental_id = (int)$_GET['rental_id'];

// Verify payment belongs to user's rental (or user is admin)
if (isAdmin()) {
    $payment_query = $conn->prepare("SELECT p.* FROM payments p WHERE p.id = ?");
    $payment_query->bind_param("i", $payment_id);
} else {
    $payment_query = $conn->prepare("SELECT p.* FROM payments p 
                                      INNER JOIN rentals r ON p.rental_id = r.id 
                                      WHERE p.id = ? AND r.user_id = ?");
    $payment_query->bind_param("ii", $payment_id, $_SESSION['user_id']);
}

$payment_query->execute();
$result = $payment_query->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Payment not found or access denied.";
    header("Location: rentals.php?view=$rental_id");
    exit;
}

$payment = $result->fetch_assoc();

// Delete receipt file if exists
if ($payment['receipt_photo'] && file_exists("uploads/receipts/" . $payment['receipt_photo'])) {
    unlink("uploads/receipts/" . $payment['receipt_photo']);
}

// Delete payment record
$delete_query = $conn->prepare("DELETE FROM payments WHERE id = ?");
$delete_query->bind_param("i", $payment_id);

if ($delete_query->execute()) {
    // Recalculate rental's amount_paid from remaining payments
    $sum_query = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE rental_id = ?");
    $sum_query->bind_param("i", $rental_id);
    $sum_query->execute();
    $sum_result = $sum_query->get_result()->fetch_assoc();
    $new_amount_paid = $sum_result['total_paid'];
    
    // Get rental total amount
    $rental_query = $conn->prepare("SELECT total_amount FROM rentals WHERE id = ?");
    $rental_query->bind_param("i", $rental_id);
    $rental_query->execute();
    $rental_result = $rental_query->get_result()->fetch_assoc();
    $total_amount = $rental_result['total_amount'];
    
    // Determine new payment status
    $new_status = 'pending';
    if ($new_amount_paid >= $total_amount) {
        $new_status = 'paid';
    } elseif ($new_amount_paid > 0) {
        $new_status = 'partial';
    }
    
    // Update rental
    $update_rental = $conn->prepare("UPDATE rentals SET amount_paid = ?, payment_status = ? WHERE id = ?");
    $update_rental->bind_param("dsi", $new_amount_paid, $new_status, $rental_id);
    $update_rental->execute();
    
    $_SESSION['success'] = "Payment deleted successfully. Rental payment status updated.";
} else {
    $_SESSION['error'] = "Failed to delete payment.";
}

header("Location: rentals.php?view=$rental_id");
exit;
?>
