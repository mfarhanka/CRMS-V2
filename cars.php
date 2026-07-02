<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Cars Management';
$conn = getDBConnection();
ensurePricingSchema($conn);
$user_id = $_SESSION['user_id'];

// Handle delete
if (isset($_GET['delete'])) {
    $car_id = intval($_GET['delete']);
    $delete_query = isAdmin() ? "DELETE FROM cars WHERE id = $car_id" : "DELETE FROM cars WHERE id = $car_id AND user_id = $user_id";
    $conn->query($delete_query);
    header('Location: cars.php');
    exit();
}

// Get cars
if (isAdmin()) {
    $cars = $conn->query("SELECT c.*, u.company_name, u.full_name
                          FROM cars c 
                          JOIN users u ON c.user_id = u.id 
                          ORDER BY c.created_at DESC");
} else {
    $cars = $conn->query("SELECT * FROM cars WHERE user_id = $user_id ORDER BY created_at DESC");
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Cars Management</h2>
        <p class="text-muted">Manage your fleet of vehicles</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="car_add.php" class="btn btn-dark">
            <i class="bi bi-plus-circle me-2"></i>Add New Car
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if ($cars->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Plate Number</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Color</th>
                        <th>Rates</th>
                        <th>Status</th>
                        <?php if (isAdmin()): ?>
                        <th>Owner</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($car = $cars->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo $car['plate_number']; ?></strong></td>
                        <td><?php echo $car['brand']; ?></td>
                        <td><?php echo $car['model']; ?></td>
                        <td><?php echo $car['year']; ?></td>
                        <td><?php echo $car['color']; ?></td>
                        <td>
                            <strong><?php echo formatCurrency($car['daily_rate']); ?>/day</strong><br>
                            <small class="text-muted">
                                <?php echo $car['weekly_rate'] ? formatCurrency($car['weekly_rate']) . '/week' : 'No weekly rate'; ?><br>
                                <?php echo $car['monthly_rate'] ? formatCurrency($car['monthly_rate']) . '/month' : 'No monthly rate'; ?>
                            </small>
                        </td>
                        <td>
                            <?php
                            $status_class = '';
                            switch($car['status']) {
                                case 'available': $status_class = 'bg-success'; break;
                                case 'rented': $status_class = 'bg-warning'; break;
                                case 'maintenance': $status_class = 'bg-danger'; break;
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($car['status']); ?></span>
                        </td>
                        <?php if (isAdmin()): ?>
                        <td><?php echo $car['company_name'] ?? $car['full_name']; ?></td>
                        <?php endif; ?>
                        <td>
                            <a href="car_edit.php?id=<?php echo $car['id']; ?>" class="btn btn-sm btn-outline-dark">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if (!isAdmin() || $car['status'] == 'available'): ?>
                            <a href="cars.php?delete=<?php echo $car['id']; ?>" class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Are you sure you want to delete this car?');">
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
            <i class="bi bi-car-front fs-1 text-muted"></i>
            <p class="text-muted mt-3">No cars found. Add your first car to get started!</p>
            <a href="car_add.php" class="btn btn-dark mt-2">
                <i class="bi bi-plus-circle me-2"></i>Add New Car
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
