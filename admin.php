<?php
require_once 'config/config.php';
requireAdmin();

$page_title = 'Admin Panel';
$conn = getDBConnection();

// Handle user status update
if (isset($_GET['toggle_status'])) {
    $user_id = intval($_GET['toggle_status']);
    $conn->query("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = $user_id AND role = 'agent'");
    header('Location: admin.php');
    exit();
}

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $user_id = intval($_GET['delete_user']);
    $conn->query("DELETE FROM users WHERE id = $user_id AND role = 'agent'");
    header('Location: admin.php');
    exit();
}

// Get all users
$users = $conn->query("SELECT u.*, 
                       (SELECT COUNT(*) FROM cars WHERE user_id = u.id) as total_cars,
                       (SELECT COUNT(*) FROM rentals WHERE user_id = u.id) as total_rentals,
                       (SELECT SUM(amount_paid) FROM rentals WHERE user_id = u.id) as total_revenue
                       FROM users u 
                       WHERE u.role = 'agent' 
                       ORDER BY u.created_at DESC");

// Get system statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'agent'")->fetch_assoc()['count'];
$total_cars = $conn->query("SELECT COUNT(*) as count FROM cars")->fetch_assoc()['count'];
$total_rentals = $conn->query("SELECT COUNT(*) as count FROM rentals")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(amount_paid) as total FROM rentals")->fetch_assoc()['total'] ?? 0;

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2>Admin Panel</h2>
        <p class="text-muted">System overview and user management</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Agents</p>
                        <h3 class="mb-0"><?php echo $total_users; ?></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-people fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
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
    
    <div class="col-md-3">
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
    
    <div class="col-md-3">
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

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Registered Agents/Companies</h5>
    </div>
    <div class="card-body">
        <?php if ($users->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Cars</th>
                        <th>Rentals</th>
                        <th>Revenue</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo $user['username']; ?></strong></td>
                        <td><?php echo $user['full_name']; ?></td>
                        <td><?php echo $user['company_name'] ?? 'Individual'; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['phone'] ?? 'N/A'; ?></td>
                        <td><?php echo $user['total_cars']; ?></td>
                        <td><?php echo $user['total_rentals']; ?></td>
                        <td><?php echo formatCurrency($user['total_revenue'] ?? 0); ?></td>
                        <td>
                            <?php if ($user['status'] == 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDate($user['created_at']); ?></td>
                        <td>
                            <a href="admin.php?toggle_status=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-warning" 
                               onclick="return confirm('Are you sure you want to toggle this user status?');">
                                <i class="bi bi-toggle-on"></i>
                            </a>
                            <a href="admin.php?delete_user=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Are you sure you want to delete this user? All their data will be removed.');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted text-center py-4">No agents registered yet</p>
        <?php endif; ?>
    </div>
</div>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
