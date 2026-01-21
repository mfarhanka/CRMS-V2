<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Edit Car';
$error = '';
$car_id = intval($_GET['id'] ?? 0);

$conn = getDBConnection();

// Get car details
if (isAdmin()) {
    $stmt = $conn->prepare("SELECT * FROM cars WHERE id = ?");
} else {
    $stmt = $conn->prepare("SELECT * FROM cars WHERE id = ? AND user_id = ?");
}

if (isAdmin()) {
    $stmt->bind_param("i", $car_id);
} else {
    $user_id = $_SESSION['user_id'];
    $stmt->bind_param("ii", $car_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: cars.php');
    exit();
}

$car = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $brand = sanitize($_POST['brand']);
    $model = sanitize($_POST['model']);
    $year = intval($_POST['year']);
    $color = sanitize($_POST['color']);
    $plate_number = sanitize($_POST['plate_number']);
    $daily_rate = floatval($_POST['daily_rate']);
    $status = sanitize($_POST['status']);
    $description = sanitize($_POST['description']);
    
    if (empty($brand) || empty($model) || empty($plate_number) || empty($daily_rate)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if plate number exists for other cars
        $check_stmt = $conn->prepare("SELECT id FROM cars WHERE plate_number = ? AND id != ?");
        $check_stmt->bind_param("si", $plate_number, $car_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'A car with this plate number already exists';
        } else {
            $update_stmt = $conn->prepare("UPDATE cars SET brand = ?, model = ?, year = ?, color = ?, plate_number = ?, daily_rate = ?, status = ?, description = ? WHERE id = ?");
            $update_stmt->bind_param("ssissdssi", $brand, $model, $year, $color, $plate_number, $daily_rate, $status, $description, $car_id);
            
            if ($update_stmt->execute()) {
                header('Location: cars.php');
                exit();
            } else {
                $error = 'Something went wrong. Please try again.';
            }
            $update_stmt->close();
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
                <h5 class="mb-0">Edit Car</h5>
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
                            <input type="text" class="form-control" id="brand" name="brand" value="<?php echo $car['brand']; ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="model" name="model" value="<?php echo $car['model']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="year" class="form-label">Year</label>
                            <input type="number" class="form-control" id="year" name="year" min="1900" max="2099" value="<?php echo $car['year']; ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="color" name="color" value="<?php echo $car['color']; ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="plate_number" class="form-label">Plate Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="plate_number" name="plate_number" value="<?php echo $car['plate_number']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="daily_rate" class="form-label">Daily Rate ($) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="daily_rate" name="daily_rate" step="0.01" min="0" value="<?php echo $car['daily_rate']; ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="available" <?php echo $car['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="rented" <?php echo $car['status'] == 'rented' ? 'selected' : ''; ?>>Rented</option>
                                <option value="maintenance" <?php echo $car['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $car['description']; ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-circle me-2"></i>Update Car
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
