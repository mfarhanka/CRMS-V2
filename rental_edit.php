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
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $status = sanitize($_POST['status']);
    $notes = sanitize($_POST['notes']);
    
    // Calculate total days and amount
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $total_days = $end->diff($start)->days + 1;
    $total_amount = $total_days * $rental['daily_rate'];
    
    $update_stmt = $conn->prepare("UPDATE rentals SET start_date = ?, end_date = ?, total_days = ?, total_amount = ?, status = ?, notes = ? WHERE id = ?");
    $update_stmt->bind_param("ssidssi", $start_date, $end_date, $total_days, $total_amount, $status, $notes, $rental_id);
    
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
                
                <form method="POST" action="" id="rentalEditForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $rental['start_date']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $rental['end_date']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted">Daily Rate</p>
                                    <h6 id="daily_rate_display"><?php echo formatCurrency($rental['daily_rate']); ?></h6>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted">Total Days</p>
                                    <h6 id="total_days_display"><?php echo $rental['total_days']; ?> days</h6>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted">Total Amount</p>
                                    <h6 id="total_amount_display"><?php echo formatCurrency($rental['total_amount']); ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Rental Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo $rental['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="completed" <?php echo $rental['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $rental['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Payment tracking:</strong> Use the "View Details" page to add and manage payments with receipt proof.
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const dailyRate = <?php echo $rental['daily_rate']; ?>;
    
    function calculateTotal() {
        if (startDate.value && endDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            const diffTime = end - start;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            if (diffDays > 0) {
                const totalAmount = diffDays * dailyRate;
                document.getElementById('total_days_display').textContent = diffDays + ' days';
                document.getElementById('total_amount_display').textContent = 'RM ' + totalAmount.toFixed(2);
            }
        }
    }
    
    startDate.addEventListener('change', function() {
        if (this.value) {
            endDate.min = this.value;
        }
        calculateTotal();
    });
    
    endDate.addEventListener('change', calculateTotal);
});
</script>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
