<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Customers Management';
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Handle delete
if (isset($_GET['delete'])) {
    $customer_id = intval($_GET['delete']);
    $delete_query = isAdmin() ? "DELETE FROM customers WHERE id = $customer_id" : "DELETE FROM customers WHERE id = $customer_id AND user_id = $user_id";
    $conn->query($delete_query);
    header('Location: customers.php');
    exit();
}

// Get customers
if (isAdmin()) {
    $customers = $conn->query("SELECT c.*, u.company_name, u.full_name as agent_name 
                               FROM customers c 
                               JOIN users u ON c.user_id = u.id 
                               ORDER BY c.created_at DESC");
} else {
    $customers = $conn->query("SELECT * FROM customers WHERE user_id = $user_id ORDER BY created_at DESC");
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Customers Management</h2>
        <p class="text-muted">Manage your customer database</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="customer_add.php" class="btn btn-dark">
            <i class="bi bi-plus-circle me-2"></i>Add New Customer
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if ($customers->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>License Number</th>
                        <?php if (isAdmin()): ?>
                        <th>Added By</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($customer = $customers->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo $customer['full_name']; ?></strong></td>
                        <td><?php echo $customer['email'] ?? 'N/A'; ?></td>
                        <td><?php echo $customer['phone']; ?></td>
                        <td><?php echo $customer['license_number'] ?? 'N/A'; ?></td>
                        <?php if (isAdmin()): ?>
                        <td><?php echo $customer['company_name'] ?? $customer['agent_name']; ?></td>
                        <?php endif; ?>
                        <td>
                            <a href="customer_edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-outline-dark">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="customers.php?delete=<?php echo $customer['id']; ?>" class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Are you sure you want to delete this customer?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-people fs-1 text-muted"></i>
            <p class="text-muted mt-3">No customers found. Add your first customer to get started!</p>
            <a href="customer_add.php" class="btn btn-dark mt-2">
                <i class="bi bi-plus-circle me-2"></i>Add New Customer
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
