<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Add Payment';
$error = '';
$success = '';
$rental_id = intval($_GET['rental_id'] ?? 0);

$conn = getDBConnection();

// Get rental details
if (isAdmin()) {
    $stmt = $conn->prepare("SELECT r.*, c.brand, c.model, c.plate_number, cu.full_name as customer_name 
                           FROM rentals r 
                           JOIN cars c ON r.car_id = c.id 
                           JOIN customers cu ON r.customer_id = cu.id 
                           WHERE r.id = ?");
    $stmt->bind_param("i", $rental_id);
} else {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT r.*, c.brand, c.model, c.plate_number, cu.full_name as customer_name 
                           FROM rentals r 
                           JOIN cars c ON r.car_id = c.id 
                           JOIN customers cu ON r.customer_id = cu.id 
                           WHERE r.id = ? AND r.user_id = ?");
    $stmt->bind_param("ii", $rental_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: rentals.php');
    exit();
}

$rental = $result->fetch_assoc();
$balance = $rental['total_amount'] - $rental['amount_paid'];
$default_amount = number_format($balance, 2, '.', '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = floatval($_POST['amount']);
    $payment_date = sanitize($_POST['payment_date']);
    $payment_method = sanitize($_POST['payment_method']);
    $notes = sanitize($_POST['notes']);
    $user_id = intval($rental['user_id']);
    $default_amount = htmlspecialchars($_POST['amount'] ?? $default_amount);
    
    if (empty($amount) || empty($payment_date)) {
        $error = 'Please fill in all required fields';
    } elseif ($amount <= 0) {
        $error = 'Amount must be greater than zero';
    } elseif ($amount > $balance) {
        $error = 'Payment amount cannot exceed balance of ' . formatCurrency($balance);
    } else {
        // Handle file upload
        $upload_dir = 'uploads/receipts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $receipt_photo = '';
        $allowed_types = array('jpg', 'jpeg', 'png', 'pdf');
        
        if (!empty($_FILES['receipt_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['receipt_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                $receipt_photo = 'receipt_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['receipt_photo']['tmp_name'], $upload_dir . $receipt_photo);
            }
        }
        
        // Insert payment record
        $stmt = $conn->prepare("INSERT INTO payments (rental_id, user_id, amount, payment_date, payment_method, receipt_photo, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iidssss", $rental_id, $user_id, $amount, $payment_date, $payment_method, $receipt_photo, $notes);
        
        if ($stmt->execute()) {
            // Update rental amount_paid and payment_status
            $new_amount_paid = $rental['amount_paid'] + $amount;
            $new_payment_status = 'partial';
            
            if ($new_amount_paid >= $rental['total_amount']) {
                $new_payment_status = 'paid';
                $new_amount_paid = $rental['total_amount']; // Cap at total amount
            }
            
            $update_stmt = $conn->prepare("UPDATE rentals SET amount_paid = ?, payment_status = ? WHERE id = ?");
            $update_stmt->bind_param("dsi", $new_amount_paid, $new_payment_status, $rental_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            header('Location: rental_view.php?id=' . $rental_id);
            exit();
        } else {
            $error = 'Something went wrong. Please try again.';
        }
        
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Rental Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Car</p>
                        <p class="fw-bold"><?php echo $rental['brand'] . ' ' . $rental['model'] . ' (' . $rental['plate_number'] . ')'; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Customer</p>
                        <p class="fw-bold"><?php echo $rental['customer_name']; ?></p>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">Total Amount</p>
                        <h5><?php echo formatCurrency($rental['total_amount']); ?></h5>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">Paid</p>
                        <h5 class="text-success"><?php echo formatCurrency($rental['amount_paid']); ?></h5>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">Balance</p>
                        <h5 class="text-danger"><?php echo formatCurrency($balance); ?></h5>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Add Payment</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($balance <= 0): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>This rental has been fully paid.
                    </div>
                    <a href="rental_view.php?id=<?php echo $rental_id; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Rental
                    </a>
                <?php else: ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Payment Amount (RM) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="amount" name="amount" 
                                   step="0.01" min="0.01" max="<?php echo $balance; ?>" value="<?php echo $default_amount; ?>" required>
                            <small class="text-muted">Maximum: <?php echo formatCurrency($balance); ?></small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="">Select method...</option>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="Online Banking">Online Banking</option>
                            <option value="E-Wallet">E-Wallet</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="receipt_photo" class="form-label">Receipt Photo/Document</label>
                        <input type="file" class="form-control" id="receipt_photo" name="receipt_photo" accept="image/*,.pdf">
                        <small class="text-muted">Upload payment receipt (JPG, PNG, PDF - Max 5MB)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-circle me-2"></i>Record Payment
                        </button>
                        <a href="rental_view.php?id=<?php echo $rental_id; ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
