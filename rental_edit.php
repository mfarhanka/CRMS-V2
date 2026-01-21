<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Edit Rental';
$error = '';
$rental_id = intval($_GET['id'] ?? 0);

$conn = getDBConnection();

// Get rental details
if (isAdmin()) {
    $stmt = $conn->prepare("SELECT r.*, c.brand, c.model FROM rentals r JOIN cars c ON r.car_id = c.id WHERE r.id = ?");
    $stmt->bind_param("i", $rental_id);
} else {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT r.*, c.brand, c.model FROM rentals r JOIN cars c ON r.car_id = c.id WHERE r.id = ? AND r.user_id = ?");
    $stmt->bind_param("ii", $rental_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: rentals.php');
    exit();
}

$rental = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = sanitize($_POST['status']);
    $payment_status = sanitize($_POST['payment_status']);
    $amount_paid = floatval($_POST['amount_paid']);
    $actual_return_date = sanitize($_POST['actual_return_date']);
    $notes = sanitize($_POST['notes']);
    
    $update_stmt = $conn->prepare("UPDATE rentals SET status = ?, payment_status = ?, amount_paid = ?, actual_return_date = ?, notes = ? WHERE id = ?");
    $update_stmt->bind_param("ssdssi", $status, $payment_status, $amount_paid, $actual_return_date, $notes, $rental_id);
    
    if ($update_stmt->execute()) {
        // Update car status based on rental status
        if ($status == 'completed' || $status == 'cancelled') {
            $conn->query("UPDATE cars SET status = 'available' WHERE id = " . $rental['car_id']);
        }
        header('Location: rentals.php');
        exit();
    } else {
        $error = 'Something went wrong. Please try again.';
    }
    $update_stmt->close();
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Edit Rental - <?php echo $rental['brand'] . ' ' . $rental['model']; ?></h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Start Date</p>
                            <p class="fw-bold"><?php echo formatDate($rental['start_date']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">End Date</p>
                            <p class="fw-bold"><?php echo formatDate($rental['end_date']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Total Days</p>
                            <p class="fw-bold"><?php echo $rental['total_days']; ?> days</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Total Amount</p>
                            <p class="fw-bold"><?php echo formatCurrency($rental['total_amount']); ?></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Rental Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo $rental['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $rental['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $rental['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="actual_return_date" class="form-label">Actual Return Date</label>
                            <input type="date" class="form-control" id="actual_return_date" name="actual_return_date" 
                                   value="<?php echo $rental['actual_return_date']; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select class="form-select" id="payment_status" name="payment_status">
                                <option value="pending" <?php echo $rental['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="partial" <?php echo $rental['payment_status'] == 'partial' ? 'selected' : ''; ?>>Partial</option>
                                <option value="paid" <?php echo $rental['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="amount_paid" class="form-label">Amount Paid ($)</label>
                            <input type="number" class="form-control" id="amount_paid" name="amount_paid" 
                                   step="0.01" min="0" max="<?php echo $rental['total_amount']; ?>" 
                                   value="<?php echo $rental['amount_paid']; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo $rental['notes']; ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-circle me-2"></i>Update Rental
                        </button>
                        <a href="rentals.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
