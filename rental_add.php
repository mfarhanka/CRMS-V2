<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'New Rental';
$error = '';
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get available cars
$available_cars = isAdmin() 
    ? $conn->query("SELECT c.*, u.company_name FROM cars c JOIN users u ON c.user_id = u.id WHERE c.status = 'available' ORDER BY c.brand, c.model")
    : $conn->query("SELECT * FROM cars WHERE user_id = $user_id AND status = 'available' ORDER BY brand, model");

// Get customers
$customers = isAdmin()
    ? $conn->query("SELECT c.*, u.company_name FROM customers c JOIN users u ON c.user_id = u.id ORDER BY c.full_name")
    : $conn->query("SELECT * FROM customers WHERE user_id = $user_id ORDER BY full_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $car_id = intval($_POST['car_id']);
    $customer_id = intval($_POST['customer_id']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $payment_status = sanitize($_POST['payment_status']);
    $amount_paid = floatval($_POST['amount_paid']);
    $notes = sanitize($_POST['notes']);
    
    if (empty($car_id) || empty($customer_id) || empty($start_date) || empty($end_date)) {
        $error = 'Please fill in all required fields';
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error = 'End date must be after start date';
    } else {
        // Get car daily rate
        $car_result = $conn->query("SELECT daily_rate FROM cars WHERE id = $car_id");
        if ($car_result->num_rows == 0) {
            $error = 'Selected car not found';
        } else {
            $car = $car_result->fetch_assoc();
            $daily_rate = $car['daily_rate'];
            
            // Calculate total days and amount
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $total_days = $end->diff($start)->days + 1;
            $total_amount = $total_days * $daily_rate;
            
            // Insert rental
            $stmt = $conn->prepare("INSERT INTO rentals (user_id, car_id, customer_id, start_date, end_date, total_days, daily_rate, total_amount, payment_status, amount_paid, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiissiidsds", $user_id, $car_id, $customer_id, $start_date, $end_date, $total_days, $daily_rate, $total_amount, $payment_status, $amount_paid, $notes);
            
            if ($stmt->execute()) {
                // Update car status
                $conn->query("UPDATE cars SET status = 'rented' WHERE id = $car_id");
                header('Location: rentals.php');
                exit();
            } else {
                $error = 'Something went wrong. Please try again.';
            }
            $stmt->close();
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">New Rental</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($available_cars->num_rows == 0): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>No available cars. Please add cars or wait for existing rentals to complete.
                    </div>
                <?php elseif ($customers->num_rows == 0): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>No customers found. Please add customers first.
                    </div>
                <?php else: ?>
                
                <form method="POST" action="" id="rentalForm">
                    <div class="mb-3">
                        <label for="car_id" class="form-label">Select Car <span class="text-danger">*</span></label>
                        <select class="form-select" id="car_id" name="car_id" required>
                            <option value="">Choose a car...</option>
                            <?php while ($car = $available_cars->fetch_assoc()): ?>
                            <option value="<?php echo $car['id']; ?>" data-rate="<?php echo $car['daily_rate']; ?>">
                                <?php echo $car['brand'] . ' ' . $car['model'] . ' - ' . $car['plate_number']; ?>
                                (<?php echo formatCurrency($car['daily_rate']); ?>/day)
                                <?php if (isAdmin()): ?>
                                - <?php echo $car['company_name'] ?? 'Individual'; ?>
                                <?php endif; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Select Customer <span class="text-danger">*</span></label>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">Choose a customer...</option>
                            <?php while ($customer = $customers->fetch_assoc()): ?>
                            <option value="<?php echo $customer['id']; ?>">
                                <?php echo $customer['full_name'] . ' - ' . $customer['phone']; ?>
                                <?php if (isAdmin()): ?>
                                (<?php echo $customer['company_name'] ?? 'Individual'; ?>)
                                <?php endif; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted">Daily Rate</p>
                                    <h5 id="daily_rate_display">$0.00</h5>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted">Total Days</p>
                                    <h5 id="total_days_display">0</h5>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted">Total Amount</p>
                                    <h5 id="total_amount_display">$0.00</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select class="form-select" id="payment_status" name="payment_status">
                                <option value="pending">Pending</option>
                                <option value="partial">Partial</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="amount_paid" class="form-label">Amount Paid ($)</label>
                            <input type="number" class="form-control" id="amount_paid" name="amount_paid" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-plus-circle me-2"></i>Create Rental
                        </button>
                        <a href="rentals.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const carSelect = document.getElementById('car_id');
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    startDate.min = today;
    endDate.min = today;
    
    function calculateTotal() {
        const selectedCar = carSelect.options[carSelect.selectedIndex];
        const dailyRate = parseFloat(selectedCar.dataset.rate) || 0;
        
        if (startDate.value && endDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            const diffTime = end - start;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            if (diffDays > 0) {
                const totalAmount = diffDays * dailyRate;
                document.getElementById('daily_rate_display').textContent = '$' + dailyRate.toFixed(2);
                document.getElementById('total_days_display').textContent = diffDays;
                document.getElementById('total_amount_display').textContent = '$' + totalAmount.toFixed(2);
            }
        }
    }
    
    carSelect.addEventListener('change', calculateTotal);
    startDate.addEventListener('change', function() {
        endDate.min = this.value;
        calculateTotal();
    });
    endDate.addEventListener('change', calculateTotal);
});
</script>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
