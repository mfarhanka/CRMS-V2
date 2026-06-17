<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Add New Customer';
$error = '';
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
        $error = 'No active users are available to own this customer.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $id_number = sanitize($_POST['id_number']);
    $license_number = sanitize($_POST['license_number']);
    $license_expiry_date = !empty($_POST['license_expiry_date']) ? sanitize($_POST['license_expiry_date']) : NULL;
    $psv_expiry_date = !empty($_POST['psv_expiry_date']) ? sanitize($_POST['psv_expiry_date']) : NULL;
    $user_id = intval($_SESSION['user_id']);

    if (isAdmin()) {
        $user_id = intval($_POST['owner_user_id'] ?? 0);
        $selected_owner_id = $user_id;
    }
    
    if (empty($full_name) || empty($phone)) {
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
            $error = 'Please select a valid customer owner';
        } else {
        // Handle file uploads
        $upload_dir = 'uploads/customers/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $ic_front_photo = '';
        $ic_back_photo = '';
        $license_front_photo = '';
        $license_back_photo = '';
        $psv_front_photo = '';
        $psv_back_photo = '';
        
        $allowed_types = array('jpg', 'jpeg', 'png', 'pdf');
        
        // Upload IC Front
        if (!empty($_FILES['ic_front_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['ic_front_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                $ic_front_photo = 'ic_front_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['ic_front_photo']['tmp_name'], $upload_dir . $ic_front_photo);
            }
        }
        
        // Upload IC Back
        if (!empty($_FILES['ic_back_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['ic_back_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                $ic_back_photo = 'ic_back_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['ic_back_photo']['tmp_name'], $upload_dir . $ic_back_photo);
            }
        }
        
        // Upload License Front
        if (!empty($_FILES['license_front_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['license_front_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                $license_front_photo = 'license_front_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['license_front_photo']['tmp_name'], $upload_dir . $license_front_photo);
            }
        }
        
        // Upload License Back
        if (!empty($_FILES['license_back_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['license_back_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                $license_back_photo = 'license_back_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['license_back_photo']['tmp_name'], $upload_dir . $license_back_photo);
            }
        }
        
        // Upload PSV Front
        if (!empty($_FILES['psv_front_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['psv_front_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                $psv_front_photo = 'psv_front_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['psv_front_photo']['tmp_name'], $upload_dir . $psv_front_photo);
            }
        }
        
        // Upload PSV Back
        if (!empty($_FILES['psv_back_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['psv_back_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                $psv_back_photo = 'psv_back_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['psv_back_photo']['tmp_name'], $upload_dir . $psv_back_photo);
            }
        }
        
            $stmt = $conn->prepare("INSERT INTO customers (user_id, full_name, email, phone, address, id_number, license_number, license_expiry_date, psv_expiry_date, ic_front_photo, ic_back_photo, license_front_photo, license_back_photo, psv_front_photo, psv_back_photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssssssss", $user_id, $full_name, $email, $phone, $address, $id_number, $license_number, $license_expiry_date, $psv_expiry_date, $ic_front_photo, $ic_back_photo, $license_front_photo, $license_back_photo, $psv_front_photo, $psv_back_photo);

            if ($stmt->execute()) {
                header('Location: customers.php');
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
                <h5 class="mb-0">Add New Customer</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
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

                    <h6 class="text-muted mb-3">Basic Information</h6>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    
                    <hr class="my-4">
                    <h6 class="text-muted mb-3">Document Information</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="id_number" class="form-label">IC Number</label>
                            <input type="text" class="form-control" id="id_number" name="id_number">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="license_number" class="form-label">Driving License Number</label>
                            <input type="text" class="form-control" id="license_number" name="license_number">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="license_expiry_date" class="form-label">Driving License Expiry Date</label>
                            <input type="date" class="form-control" id="license_expiry_date" name="license_expiry_date">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="psv_expiry_date" class="form-label">PSV Expiry Date</label>
                            <input type="date" class="form-control" id="psv_expiry_date" name="psv_expiry_date">
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    <h6 class="text-muted mb-3">Document Photos <small class="text-muted">(JPG, PNG, PDF - Max 5MB)</small></h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ic_front_photo" class="form-label">IC Front Photo</label>
                            <input type="file" class="form-control" id="ic_front_photo" name="ic_front_photo" accept="image/*,.pdf">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="ic_back_photo" class="form-label">IC Back Photo</label>
                            <input type="file" class="form-control" id="ic_back_photo" name="ic_back_photo" accept="image/*,.pdf">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="license_front_photo" class="form-label">Driving License Front Photo</label>
                            <input type="file" class="form-control" id="license_front_photo" name="license_front_photo" accept="image/*,.pdf">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="license_back_photo" class="form-label">Driving License Back Photo</label>
                            <input type="file" class="form-control" id="license_back_photo" name="license_back_photo" accept="image/*,.pdf">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="psv_front_photo" class="form-label">PSV Front Photo</label>
                            <input type="file" class="form-control" id="psv_front_photo" name="psv_front_photo" accept="image/*,.pdf">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="psv_back_photo" class="form-label">PSV Back Photo</label>
                            <input type="file" class="form-control" id="psv_back_photo" name="psv_back_photo" accept="image/*,.pdf">
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-plus-circle me-2"></i>Add Customer
                        </button>
                        <a href="customers.php" class="btn btn-outline-secondary">Cancel</a>
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
