<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Rentals Management';
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Handle duplicate
if (isset($_GET['duplicate'])) {
    $rental_id = intval($_GET['duplicate']);
    
    // Get rental details
    if (isAdmin()) {
        $dup_result = $conn->query("SELECT * FROM rentals WHERE id = $rental_id");
    } else {
        $dup_result = $conn->query("SELECT * FROM rentals WHERE id = $rental_id AND user_id = $user_id");
    }
    
    if ($dup_result->num_rows > 0) {
        $dup_rental = $dup_result->fetch_assoc();
        
        // Insert duplicated rental with status as active
        $stmt = $conn->prepare("INSERT INTO rentals (user_id, car_id, customer_id, start_date, end_date, total_days, daily_rate, total_amount, payment_status, amount_paid, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, 'active', ?)");
        $stmt->bind_param("iiissiids", $dup_rental['user_id'], $dup_rental['car_id'], $dup_rental['customer_id'], $dup_rental['start_date'], $dup_rental['end_date'], $dup_rental['total_days'], $dup_rental['daily_rate'], $dup_rental['total_amount'], $dup_rental['notes']);
        $stmt->execute();
        $stmt->close();
    }
    
    header('Location: rentals.php');
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $rental_id = intval($_GET['delete']);
    
    // Get car_id before deleting rental
    if (isAdmin()) {
        $car_result = $conn->query("SELECT car_id FROM rentals WHERE id = $rental_id");
    } else {
        $car_result = $conn->query("SELECT car_id FROM rentals WHERE id = $rental_id AND user_id = $user_id");
    }
    
    if ($car_result->num_rows > 0) {
        $car_id = $car_result->fetch_assoc()['car_id'];
        
        // Delete rental
        $delete_query = isAdmin() ? "DELETE FROM rentals WHERE id = $rental_id" : "DELETE FROM rentals WHERE id = $rental_id AND user_id = $user_id";
        if ($conn->query($delete_query)) {
            // Update car status to available
            $conn->query("UPDATE cars SET status = 'available' WHERE id = $car_id");
        }
    }
    
    header('Location: rentals.php');
    exit();
}

// Get rentals
if (isAdmin()) {
    $rentals = $conn->query("SELECT r.*, c.brand, c.model, c.plate_number, cu.full_name as customer_name, u.company_name, u.full_name as agent_name 
                             FROM rentals r 
                             JOIN cars c ON r.car_id = c.id 
                             JOIN customers cu ON r.customer_id = cu.id 
                             JOIN users u ON r.user_id = u.id 
                             ORDER BY r.start_date DESC");
} else {
    $rentals = $conn->query("SELECT r.*, c.brand, c.model, c.plate_number, cu.full_name as customer_name 
                             FROM rentals r 
                             JOIN cars c ON r.car_id = c.id 
                             JOIN customers cu ON r.customer_id = cu.id 
                             WHERE r.user_id = $user_id 
                             ORDER BY r.start_date DESC");
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Rentals Management</h2>
        <p class="text-muted">Manage all rental transactions</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="rental_add.php" class="btn btn-dark">
            <i class="bi bi-plus-circle me-2"></i>New Rental
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if ($rentals->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Car</th>
                        <th>Customer</th>
                        <?php if (isAdmin()): ?>
                        <th>Company</th>
                        <?php endif; ?>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Total Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($rental = $rentals->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo $rental['brand'] . ' ' . $rental['model']; ?></strong><br>
                            <small class="text-muted"><?php echo $rental['plate_number']; ?></small>
                        </td>
                        <td><?php echo $rental['customer_name']; ?></td>
                        <?php if (isAdmin()): ?>
                        <td><?php echo $rental['company_name'] ?? $rental['agent_name']; ?></td>
                        <?php endif; ?>
                        <td><?php echo formatDate($rental['start_date']); ?></td>
                        <td><?php echo formatDate($rental['end_date']); ?></td>
                        <td><?php echo formatCurrency($rental['total_amount']); ?></td>
                        <td><?php echo formatCurrency($rental['amount_paid']); ?></td>
                        <td>
                            <?php
                            $status_class = '';
                            switch($rental['status']) {
                                case 'active': $status_class = 'bg-primary'; break;
                                case 'completed': $status_class = 'bg-success'; break;
                                case 'cancelled': $status_class = 'bg-danger'; break;
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($rental['status']); ?></span>
                        </td>
                        <td>
                            <a href="rental_view.php?id=<?php echo $rental['id']; ?>" class="btn btn-sm btn-outline-info" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="rental_edit.php?id=<?php echo $rental['id']; ?>" class="btn btn-sm btn-outline-dark" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="rentals.php?duplicate=<?php echo $rental['id']; ?>" class="btn btn-sm btn-outline-success" title="Duplicate"
                               onclick="return confirm('Create a duplicate of this rental?');">
                                <i class="bi bi-files"></i>
                            </a>
                            <?php if ($rental['status'] != 'completed'): ?>
                            <a href="rentals.php?delete=<?php echo $rental['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete"
                               onclick="return confirm('Are you sure you want to delete this rental?');">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-clipboard-check fs-1 text-muted"></i>
            <p class="text-muted mt-3">No rentals found. Create your first rental to get started!</p>
            <a href="rental_add.php" class="btn btn-dark mt-2">
                <i class="bi bi-plus-circle me-2"></i>New Rental
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
