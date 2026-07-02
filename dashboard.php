<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Dashboard';

// Get statistics
$conn = getDBConnection();
ensurePricingSchema($conn);

if (isAdmin()) {
    // Admin sees all data
    $total_cars = $conn->query("SELECT COUNT(*) as count FROM cars")->fetch_assoc()['count'];
    $available_cars = $conn->query("SELECT COUNT(*) as count FROM cars WHERE status = 'available'")->fetch_assoc()['count'];
    $total_rentals = $conn->query("SELECT COUNT(*) as count FROM rentals")->fetch_assoc()['count'];
    $active_rentals = $conn->query("SELECT COUNT(*) as count FROM rentals WHERE status = 'active'")->fetch_assoc()['count'];
    $overdue_rentals = $conn->query("SELECT COUNT(*) as count FROM rentals WHERE status = 'active' AND end_date < CURDATE()")->fetch_assoc()['count'];
    $total_customers = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
    $total_revenue = $conn->query("SELECT SUM(amount_paid) as total FROM rentals")->fetch_assoc()['total'] ?? 0;
    
    // Recent rentals
    $recent_rentals = $conn->query("SELECT r.*, c.brand, c.model, cu.full_name as customer_name, u.company_name 
                                     FROM rentals r 
                                     JOIN cars c ON r.car_id = c.id 
                                     JOIN customers cu ON r.customer_id = cu.id 
                                     JOIN users u ON r.user_id = u.id 
                                     ORDER BY r.created_at DESC LIMIT 5");
} else {
    // Agent sees only their data
    $user_id = $_SESSION['user_id'];
    $total_cars = $conn->query("SELECT COUNT(*) as count FROM cars WHERE user_id = $user_id")->fetch_assoc()['count'];
    $available_cars = $conn->query("SELECT COUNT(*) as count FROM cars WHERE user_id = $user_id AND status = 'available'")->fetch_assoc()['count'];
    $total_rentals = $conn->query("SELECT COUNT(*) as count FROM rentals WHERE user_id = $user_id")->fetch_assoc()['count'];
    $active_rentals = $conn->query("SELECT COUNT(*) as count FROM rentals WHERE user_id = $user_id AND status = 'active'")->fetch_assoc()['count'];
    $overdue_rentals = $conn->query("SELECT COUNT(*) as count FROM rentals WHERE user_id = $user_id AND status = 'active' AND end_date < CURDATE()")->fetch_assoc()['count'];
    $total_customers = $conn->query("SELECT COUNT(*) as count FROM customers WHERE user_id = $user_id")->fetch_assoc()['count'];
    $total_revenue = $conn->query("SELECT SUM(amount_paid) as total FROM rentals WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
    
    // Recent rentals
    $recent_rentals = $conn->query("SELECT r.*, c.brand, c.model, cu.full_name as customer_name 
                                     FROM rentals r 
                                     JOIN cars c ON r.car_id = c.id 
                                     JOIN customers cu ON r.customer_id = cu.id 
                                     WHERE r.user_id = $user_id 
                                     ORDER BY r.created_at DESC LIMIT 5");
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2>Dashboard</h2>
        <p class="text-muted">Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Cars</p>
                        <h3 class="mb-0"><?php echo $total_cars; ?></h3>
                    </div>
                    <div class="bg-dark bg-opacity-10 p-3 rounded">
                        <i class="bi bi-car-front fs-2 text-dark"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Overdue Returns</p>
                        <h3 class="mb-0"><?php echo $overdue_rentals; ?></h3>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded">
                        <i class="bi bi-exclamation-triangle fs-2 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Available Cars</p>
                        <h3 class="mb-0"><?php echo $available_cars; ?></h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-check-circle fs-2 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Customers</p>
                        <h3 class="mb-0"><?php echo $total_customers; ?></h3>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="bi bi-people fs-2 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Rentals</p>
                        <h3 class="mb-0"><?php echo $total_rentals; ?></h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i class="bi bi-clipboard-check fs-2 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Active Rentals</p>
                        <h3 class="mb-0"><?php echo $active_rentals; ?></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-hourglass-split fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Revenue</p>
                        <h3 class="mb-0"><?php echo formatCurrency($total_revenue); ?></h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-currency-dollar fs-2 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Rentals</h5>
            </div>
            <div class="card-body">
                <?php if ($recent_rentals->num_rows > 0): ?>
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
                                <th>Plan</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($rental = $recent_rentals->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $rental['brand'] . ' ' . $rental['model']; ?></td>
                                <td><?php echo $rental['customer_name']; ?></td>
                                <?php if (isAdmin()): ?>
                                <td><?php echo $rental['company_name'] ?? 'N/A'; ?></td>
                                <?php endif; ?>
                                <td><?php echo formatDate($rental['start_date']); ?></td>
                                <td><?php echo formatDate($rental['end_date']); ?></td>
                                <td><?php echo rentalPlanLabel($rental['billing_plan'] ?? 'daily'); ?></td>
                                <td><?php echo formatCurrency($rental['total_amount']); ?></td>
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
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-4">No rentals found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
