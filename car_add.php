<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Add New Car';
$error = '';
$success = '';
$conn = getDBConnection();
$owner_options = [];
$selected_owner_id = intval($_SESSION['user_id'] ?? 0);

if (isAdmin()) {
    $owners = $conn->query("SELECT id, full_name, company_name, role FROM users WHERE status = 'active' AND role IN ('admin', 'agent') ORDER BY role, full_name");

    while ($owner = $owners->fetch_assoc()) {
        $owner_options[] = $owner;
    }

    if (!empty($owner_options)) {
        $selected_owner_id = intval($owner_options[0]['id']);
    } else {
        $error = 'No active users are available to own this car.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $brand = sanitize($_POST['brand']);
    $model = sanitize($_POST['model']);
    $year = intval($_POST['year']);
    $color = sanitize($_POST['color']);
    $plate_number = sanitize($_POST['plate_number']);
    $daily_rate = floatval($_POST['daily_rate']);
    $status = sanitize($_POST['status']);
    $description = sanitize($_POST['description']);
    $user_id = intval($_SESSION['user_id']);

    if (isAdmin()) {
        $user_id = intval($_POST['owner_user_id'] ?? 0);
        $selected_owner_id = $user_id;
    }
    
    if (empty($brand) || empty($model) || empty($plate_number) || empty($daily_rate)) {
        $error = 'Please fill in all required fields';
    } else {
        $valid_owner = false;

        if (isAdmin()) {
            foreach ($owner_options as $owner) {
                if (intval($owner['id']) === $user_id) {
                    $valid_owner = true;
                    break;
                }
            }
        } else {
            $owner_stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND status = 'active'");
            $owner_stmt->bind_param("i", $user_id);
            $owner_stmt->execute();
            $valid_owner = $owner_stmt->get_result()->num_rows === 1;
            $owner_stmt->close();
        }

        if (!$valid_owner) {
            $error = 'Please select a valid car owner';
        } else {
            // Check if plate number exists
            $check_stmt = $conn->prepare("SELECT id FROM cars WHERE plate_number = ?");
            $check_stmt->bind_param("s", $plate_number);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows > 0) {
                $error = 'A car with this plate number already exists';
            } else {
                $insert_stmt = $conn->prepare("INSERT INTO cars (user_id, brand, model, year, color, plate_number, daily_rate, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("ississdss", $user_id, $brand, $model, $year, $color, $plate_number, $daily_rate, $status, $description);

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
                    <?php if (isAdmin()): ?>
                    <div class="mb-3">
                        <label for="owner_user_id" class="form-label">Owner <span class="text-danger">*</span></label>
                        <select class="form-select" id="owner_user_id" name="owner_user_id" required>
                            <?php foreach ($owner_options as $owner): ?>
                                <?php
                                $owner_label = $owner['company_name'] ?: $owner['full_name'];
                                $owner_label .= ' (' . ucfirst($owner['role']) . ')';
                                ?>
                                <option value="<?php echo intval($owner['id']); ?>" <?php echo intval($owner['id']) === $selected_owner_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($owner_label, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

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
                        <div class="col-md-6 mb-3">
                            <label for="daily_rate" class="form-label">Daily Rate (RM) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="daily_rate" name="daily_rate" step="0.01" min="0" required>
                        </div>
                        
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
