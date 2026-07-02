<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Add New Car';
$error = '';
$success = '';
$conn = getDBConnection();
ensurePricingSchema($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $brand = sanitize($_POST['brand']);
    $model = sanitize($_POST['model']);
    $year = intval($_POST['year']);
    $color = sanitize($_POST['color']);
    $plate_number = sanitize($_POST['plate_number']);
    $daily_rate = floatval($_POST['daily_rate']);
    $weekly_rate = ($_POST['weekly_rate'] ?? '') !== '' ? floatval($_POST['weekly_rate']) : null;
    $monthly_rate = ($_POST['monthly_rate'] ?? '') !== '' ? floatval($_POST['monthly_rate']) : null;
    $status = sanitize($_POST['status']);
    $description = sanitize($_POST['description']);
    $user_id = intval($_SESSION['user_id']);

    if ($user_id <= 0) {
        $owner_result = $conn->query("SELECT id FROM users WHERE status = 'active' ORDER BY role = 'admin' DESC, role = 'agent' DESC, full_name LIMIT 1");
        if ($owner_result && $owner_result->num_rows === 1) {
            $user_id = intval($owner_result->fetch_assoc()['id']);
        } else {
            $error = 'No active account is available to own this car.';
        }
    }
    
    if (!$error && (empty($brand) || empty($model) || empty($plate_number) || empty($daily_rate))) {
        $error = 'Please fill in all required fields';
    }

    if (!$error) {
        // Check if plate number exists
        $check_stmt = $conn->prepare("SELECT id FROM cars WHERE plate_number = ?");
        $check_stmt->bind_param("s", $plate_number);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'A car with this plate number already exists';
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO cars (user_id, brand, model, year, color, plate_number, daily_rate, weekly_rate, monthly_rate, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ississdddss", $user_id, $brand, $model, $year, $color, $plate_number, $daily_rate, $weekly_rate, $monthly_rate, $status, $description);

            if ($insert_stmt->execute()) {
                header('Location: cars.php');
                exit();
            } else {
                $error = 'Something went wrong. Please try again.';
            }

            $insert_stmt->close();
        }

        $check_stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Add New Car</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="brand" class="form-label">Brand <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="brand" name="brand" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="model" name="model" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="year" class="form-label">Year</label>
                            <input type="number" class="form-control" id="year" name="year" min="1900" max="2099" value="<?php echo date('Y'); ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="color" name="color">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="plate_number" class="form-label">Plate Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="plate_number" name="plate_number" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="daily_rate" class="form-label">Daily Rate (RM) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="daily_rate" name="daily_rate" step="0.01" min="0" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="weekly_rate" class="form-label">Weekly Rate (RM)</label>
                            <input type="number" class="form-control" id="weekly_rate" name="weekly_rate" step="0.01" min="0">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="monthly_rate" class="form-label">Monthly Rate (RM)</label>
                            <input type="number" class="form-control" id="monthly_rate" name="monthly_rate" step="0.01" min="0">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="available">Available</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-plus-circle me-2"></i>Add Car
                        </button>
                        <a href="cars.php" class="btn btn-outline-secondary">Cancel</a>
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
