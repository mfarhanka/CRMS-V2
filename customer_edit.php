<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Edit Customer';
$error = '';
$customer_id = intval($_GET['id'] ?? 0);

$conn = getDBConnection();

// Get customer details
if (isAdmin()) {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
} else {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ? AND user_id = ?");
    $user_id = $_SESSION['user_id'];
    $stmt->bind_param("ii", $customer_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: customers.php');
    exit();
}

$customer = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $id_number = sanitize($_POST['id_number']);
    $license_number = sanitize($_POST['license_number']);
    $license_expiry_date = !empty($_POST['license_expiry_date']) ? sanitize($_POST['license_expiry_date']) : NULL;
    $psv_expiry_date = !empty($_POST['psv_expiry_date']) ? sanitize($_POST['psv_expiry_date']) : NULL;
    
    if (empty($full_name) || empty($phone)) {
        $error = 'Please fill in all required fields';
    } else {
        // Handle file uploads
        $upload_dir = 'uploads/customers/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = array('jpg', 'jpeg', 'png', 'pdf');
        
        // Keep existing photos or upload new ones
        $ic_front_photo = $customer['ic_front_photo'];
        $ic_back_photo = $customer['ic_back_photo'];
        $license_front_photo = $customer['license_front_photo'];
        $license_back_photo = $customer['license_back_photo'];
        $psv_front_photo = $customer['psv_front_photo'];
        $psv_back_photo = $customer['psv_back_photo'];
        
        // Upload IC Front
        if (!empty($_FILES['ic_front_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['ic_front_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                if ($ic_front_photo && file_exists($upload_dir . $ic_front_photo)) {
                    unlink($upload_dir . $ic_front_photo);
                }
                $ic_front_photo = 'ic_front_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['ic_front_photo']['tmp_name'], $upload_dir . $ic_front_photo);
            }
        }
        
        // Upload IC Back
        if (!empty($_FILES['ic_back_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['ic_back_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                if ($ic_back_photo && file_exists($upload_dir . $ic_back_photo)) {
                    unlink($upload_dir . $ic_back_photo);
                }
                $ic_back_photo = 'ic_back_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['ic_back_photo']['tmp_name'], $upload_dir . $ic_back_photo);
            }
        }
        
        // Upload License Front
        if (!empty($_FILES['license_front_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['license_front_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                if ($license_front_photo && file_exists($upload_dir . $license_front_photo)) {
                    unlink($upload_dir . $license_front_photo);
                }
                $license_front_photo = 'license_front_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['license_front_photo']['tmp_name'], $upload_dir . $license_front_photo);
            }
        }
        
        // Upload License Back
        if (!empty($_FILES['license_back_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['license_back_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                if ($license_back_photo && file_exists($upload_dir . $license_back_photo)) {
                    unlink($upload_dir . $license_back_photo);
                }
                $license_back_photo = 'license_back_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['license_back_photo']['tmp_name'], $upload_dir . $license_back_photo);
            }
        }
        
        // Upload PSV Front
        if (!empty($_FILES['psv_front_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['psv_front_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                if ($psv_front_photo && file_exists($upload_dir . $psv_front_photo)) {
                    unlink($upload_dir . $psv_front_photo);
                }
                $psv_front_photo = 'psv_front_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['psv_front_photo']['tmp_name'], $upload_dir . $psv_front_photo);
            }
        }
        
        // Upload PSV Back
        if (!empty($_FILES['psv_back_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['psv_back_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_types)) {
                if ($psv_back_photo && file_exists($upload_dir . $psv_back_photo)) {
                    unlink($upload_dir . $psv_back_photo);
                }
                $psv_back_photo = 'psv_back_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['psv_back_photo']['tmp_name'], $upload_dir . $psv_back_photo);
            }
        }
        
        $update_stmt = $conn->prepare("UPDATE customers SET full_name = ?, email = ?, phone = ?, address = ?, id_number = ?, license_number = ?, license_expiry_date = ?, psv_expiry_date = ?, ic_front_photo = ?, ic_back_photo = ?, license_front_photo = ?, license_back_photo = ?, psv_front_photo = ?, psv_back_photo = ? WHERE id = ?");
        $update_stmt->bind_param("ssssssssssssssi", $full_name, $email, $phone, $address, $id_number, $license_number, $license_expiry_date, $psv_expiry_date, $ic_front_photo, $ic_back_photo, $license_front_photo, $license_back_photo, $psv_front_photo, $psv_back_photo, $customer_id);
        
        if ($update_stmt->execute()) {
            header('Location: customers.php');
            exit();
        } else {
            $error = 'Something went wrong. Please try again.';
        }
        $update_stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Edit Customer</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <h6 class="text-muted mb-3">Basic Information</h6>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $customer['full_name']; ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $customer['email']; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $customer['phone']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo $customer['address']; ?></textarea>
                    </div>
                    
                    <hr class="my-4">
                    <h6 class="text-muted mb-3">Document Information</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="id_number" class="form-label">IC Number</label>
                            <input type="text" class="form-control" id="id_number" name="id_number" value="<?php echo $customer['id_number']; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="license_number" class="form-label">Driving License Number</label>
                            <input type="text" class="form-control" id="license_number" name="license_number" value="<?php echo $customer['license_number']; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="license_expiry_date" class="form-label">Driving License Expiry Date</label>
                            <input type="date" class="form-control" id="license_expiry_date" name="license_expiry_date" value="<?php echo $customer['license_expiry_date']; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="psv_expiry_date" class="form-label">PSV Expiry Date</label>
                            <input type="date" class="form-control" id="psv_expiry_date" name="psv_expiry_date" value="<?php echo $customer['psv_expiry_date']; ?>">
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    <h6 class="text-muted mb-3">Document Photos <small class="text-muted">(JPG, PNG, PDF - Max 5MB)</small></h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ic_front_photo" class="form-label">IC Front Photo</label>
                            <?php if ($customer['ic_front_photo']): ?>
                                <div class="mb-2">
                                    <a href="uploads/customers/<?php echo $customer['ic_front_photo']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-file-earmark-image"></i> View Current
                                    </a>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="ic_front_photo" name="ic_front_photo" accept="image/*,.pdf">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="ic_back_photo" class="form-label">IC Back Photo</label>
                            <?php if ($customer['ic_back_photo']): ?>
                                <div class="mb-2">
                                    <a href="uploads/customers/<?php echo $customer['ic_back_photo']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-file-earmark-image"></i> View Current
                                    </a>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="ic_back_photo" name="ic_back_photo" accept="image/*,.pdf">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="license_front_photo" class="form-label">Driving License Front Photo</label>
                            <?php if ($customer['license_front_photo']): ?>
                                <div class="mb-2">
                                    <a href="uploads/customers/<?php echo $customer['license_front_photo']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-file-earmark-image"></i> View Current
                                    </a>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="license_front_photo" name="license_front_photo" accept="image/*,.pdf">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="license_back_photo" class="form-label">Driving License Back Photo</label>
                            <?php if ($customer['license_back_photo']): ?>
                                <div class="mb-2">
                                    <a href="uploads/customers/<?php echo $customer['license_back_photo']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-file-earmark-image"></i> View Current
                                    </a>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="license_back_photo" name="license_back_photo" accept="image/*,.pdf">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="psv_front_photo" class="form-label">PSV Front Photo</label>
                            <?php if ($customer['psv_front_photo']): ?>
                                <div class="mb-2">
                                    <a href="uploads/customers/<?php echo $customer['psv_front_photo']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-file-earmark-image"></i> View Current
                                    </a>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="psv_front_photo" name="psv_front_photo" accept="image/*,.pdf">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="psv_back_photo" class="form-label">PSV Back Photo</label>
                            <?php if ($customer['psv_back_photo']): ?>
                                <div class="mb-2">
                                    <a href="uploads/customers/<?php echo $customer['psv_back_photo']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-file-earmark-image"></i> View Current
                                    </a>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="psv_back_photo" name="psv_back_photo" accept="image/*,.pdf">
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-circle me-2"></i>Update Customer
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
