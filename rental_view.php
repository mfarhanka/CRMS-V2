<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'View Rental';
$rental_id = intval($_GET['id'] ?? 0);

$conn = getDBConnection();

// Get rental details
if (isAdmin()) {
    $stmt = $conn->prepare("SELECT r.*, c.brand, c.model, c.plate_number, c.color, cu.full_name as customer_name, cu.phone, cu.email, cu.license_number, u.company_name, u.full_name as agent_name 
                           FROM rentals r 
                           JOIN cars c ON r.car_id = c.id 
                           JOIN customers cu ON r.customer_id = cu.id 
                           JOIN users u ON r.user_id = u.id 
                           WHERE r.id = ?");
    $stmt->bind_param("i", $rental_id);
} else {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT r.*, c.brand, c.model, c.plate_number, c.color, cu.full_name as customer_name, cu.phone, cu.email, cu.license_number 
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

// Get payment history
$payments_query = $conn->query("SELECT * FROM payments WHERE rental_id = $rental_id ORDER BY payment_date DESC, created_at DESC");

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Rental Details - #<?php echo str_pad($rental['id'], 6, '0', STR_PAD_LEFT); ?></h5>
                <div>
                    <a href="rentals.php?edit=<?php echo $rental['id']; ?>" class="btn btn-sm btn-light">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                    <a href="rentals.php" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Car Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Brand & Model:</th>
                                <td><?php echo $rental['brand'] . ' ' . $rental['model']; ?></td>
                            </tr>
                            <tr>
                                <th>Plate Number:</th>
                                <td><?php echo $rental['plate_number']; ?></td>
                            </tr>
                            <tr>
                                <th>Color:</th>
                                <td><?php echo $rental['color']; ?></td>
                            </tr>
                            <tr>
                                <th>Daily Rate:</th>
                                <td><?php echo formatCurrency($rental['daily_rate']); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Customer Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Name:</th>
                                <td><?php echo $rental['customer_name']; ?></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td><?php echo $rental['phone']; ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo $rental['email'] ?? 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>License Number:</th>
                                <td><?php echo $rental['license_number'] ?? 'N/A'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="text-muted mb-3">Rental Information</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <p class="mb-1 text-muted">Start Date</p>
                                <h6><?php echo formatDate($rental['start_date']); ?></h6>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1 text-muted">End Date</p>
                                <h6><?php echo formatDate($rental['end_date']); ?></h6>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1 text-muted">Total Days</p>
                                <h6><?php echo $rental['total_days']; ?> days</h6>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1 text-muted">Status</p>
                                <h6>
                                    <?php
                                    $status_class = '';
                                    switch($rental['status']) {
                                        case 'active': $status_class = 'bg-primary'; break;
                                        case 'completed': $status_class = 'bg-success'; break;
                                        case 'cancelled': $status_class = 'bg-danger'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($rental['status']); ?></span>
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-muted mb-0">Payment Information</h6>
                            <?php if ($rental['payment_status'] != 'paid'): ?>
                            <a href="payment_add.php?rental_id=<?php echo $rental['id']; ?>" class="btn btn-sm btn-success">
                                <i class="bi bi-plus-circle me-1"></i>Add Payment
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <p class="mb-1 text-muted">Total Amount</p>
                                <h5 class="text-dark"><?php echo formatCurrency($rental['total_amount']); ?></h5>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1 text-muted">Amount Paid</p>
                                <h5 class="text-success"><?php echo formatCurrency($rental['amount_paid']); ?></h5>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1 text-muted">Balance</p>
                                <h5 class="text-danger"><?php echo formatCurrency($rental['total_amount'] - $rental['amount_paid']); ?></h5>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1 text-muted">Payment Status</p>
                                <h6>
                                    <?php
                                    $payment_class = '';
                                    switch($rental['payment_status']) {
                                        case 'paid': $payment_class = 'bg-success'; break;
                                        case 'partial': $payment_class = 'bg-warning'; break;
                                        case 'pending': $payment_class = 'bg-danger'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $payment_class; ?>"><?php echo ucfirst($rental['payment_status']); ?></span>
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($payments_query->num_rows > 0): ?>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="text-muted mb-3">Payment History</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Receipt</th>
                                        <th>Notes</th>
                                        <th>Recorded</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payment = $payments_query->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo formatDate($payment['payment_date']); ?></td>
                                        <td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                                        <td><?php echo $payment['payment_method'] ?? 'N/A'; ?></td>
                                        <td>
                                            <?php if ($payment['receipt_photo']): ?>
                                            <a href="uploads/receipts/<?php echo $payment['receipt_photo']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-file-earmark-image"></i> View
                                            </a>
                                            <?php else: ?>
                                            <span class="text-muted">No receipt</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $payment['notes'] ? substr($payment['notes'], 0, 30) . (strlen($payment['notes']) > 30 ? '...' : '') : '-'; ?></td>
                                        <td><small class="text-muted"><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></small></td>
                                        <td>
                                            <a href="payment_delete.php?id=<?php echo $payment['id']; ?>&rental_id=<?php echo $rental['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this payment? The rental balance will be recalculated.');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($rental['notes']): ?>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="text-muted mb-2">Notes</h6>
                        <p><?php echo nl2br($rental['notes']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isAdmin()): ?>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="text-muted mb-2">Agent/Company</h6>
                        <p><?php echo $rental['company_name'] ?? $rental['agent_name']; ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <hr>
                <small class="text-muted">Created: <?php echo date('M d, Y H:i', strtotime($rental['created_at'])); ?></small>
            </div>
        </div>
    </div>
</div>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
