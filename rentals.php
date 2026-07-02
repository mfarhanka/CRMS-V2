<?php
require_once 'config/config.php';
requireLogin();

$page_title = 'Rentals Management';
$conn = getDBConnection();
$user_id = intval($_SESSION['user_id']);
$form_error = '';
$open_modal = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rental_action = sanitize($_POST['rental_action'] ?? '');

    if ($rental_action == 'create') {
        $car_id = intval($_POST['car_id']);
        $customer_id = intval($_POST['customer_id']);
        $start_date = sanitize($_POST['start_date']);
        $end_date = sanitize($_POST['end_date']);
        $payment_status = sanitize($_POST['payment_status']);
        $amount_paid = floatval($_POST['amount_paid']);
        $notes = sanitize($_POST['notes']);

        if (empty($car_id) || empty($customer_id) || empty($start_date) || empty($end_date)) {
            $form_error = 'Please fill in all required fields';
            $open_modal = 'addRentalModal';
        } elseif (strtotime($end_date) < strtotime($start_date)) {
            $form_error = 'End date must be after start date';
            $open_modal = 'addRentalModal';
        } else {
            if (isAdmin()) {
                $car_stmt = $conn->prepare("SELECT user_id, daily_rate FROM cars WHERE id = ? AND status = 'available'");
                $car_stmt->bind_param("i", $car_id);
                $customer_stmt = $conn->prepare("SELECT user_id FROM customers WHERE id = ?");
                $customer_stmt->bind_param("i", $customer_id);
            } else {
                $car_stmt = $conn->prepare("SELECT user_id, daily_rate FROM cars WHERE id = ? AND user_id = ? AND status = 'available'");
                $car_stmt->bind_param("ii", $car_id, $user_id);
                $customer_stmt = $conn->prepare("SELECT user_id FROM customers WHERE id = ? AND user_id = ?");
                $customer_stmt->bind_param("ii", $customer_id, $user_id);
            }

            $car_stmt->execute();
            $car_result = $car_stmt->get_result();
            $customer_stmt->execute();
            $customer_result = $customer_stmt->get_result();

            if ($car_result->num_rows == 0) {
                $form_error = 'Selected car not found';
                $open_modal = 'addRentalModal';
            } elseif ($customer_result->num_rows == 0) {
                $form_error = 'Selected customer not found';
                $open_modal = 'addRentalModal';
            } else {
                $car = $car_result->fetch_assoc();
                $customer = $customer_result->fetch_assoc();
                $rental_user_id = intval($car['user_id']);

                if ($rental_user_id !== intval($customer['user_id'])) {
                    $form_error = 'Selected car and customer must belong to the same account';
                    $open_modal = 'addRentalModal';
                }

                $daily_rate = $car['daily_rate'];
            }

            $car_stmt->close();
            $customer_stmt->close();

            if (!$form_error) {
                $start = new DateTime($start_date);
                $end = new DateTime($end_date);
                $total_days = $end->diff($start)->days + 1;
                $total_amount = $total_days * $daily_rate;

                $stmt = $conn->prepare("INSERT INTO rentals (user_id, car_id, customer_id, start_date, end_date, total_days, daily_rate, total_amount, payment_status, amount_paid, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiissiidsds", $rental_user_id, $car_id, $customer_id, $start_date, $end_date, $total_days, $daily_rate, $total_amount, $payment_status, $amount_paid, $notes);

                if ($stmt->execute()) {
                    $status_stmt = $conn->prepare("UPDATE cars SET status = 'rented' WHERE id = ?");
                    $status_stmt->bind_param("i", $car_id);
                    $status_stmt->execute();
                    $status_stmt->close();
                    header('Location: rentals.php');
                    exit();
                }

                $form_error = 'Something went wrong. Please try again.';
                $open_modal = 'addRentalModal';
                $stmt->close();
            }
        }
    } elseif ($rental_action == 'update') {
        $rental_id = intval($_POST['rental_id']);
        $start_date = sanitize($_POST['start_date']);
        $end_date = sanitize($_POST['end_date']);
        $status = sanitize($_POST['status']);
        $notes = sanitize($_POST['notes']);

        if (empty($rental_id) || empty($start_date) || empty($end_date)) {
            $form_error = 'Please fill in all required fields';
            $open_modal = 'editRentalModal';
        } elseif (strtotime($end_date) < strtotime($start_date)) {
            $form_error = 'End date must be after start date';
            $open_modal = 'editRentalModal';
        } else {
            if (isAdmin()) {
                $rental_stmt = $conn->prepare("SELECT car_id, daily_rate FROM rentals WHERE id = ?");
                $rental_stmt->bind_param("i", $rental_id);
            } else {
                $rental_stmt = $conn->prepare("SELECT car_id, daily_rate FROM rentals WHERE id = ? AND user_id = ?");
                $rental_stmt->bind_param("ii", $rental_id, $user_id);
            }

            $rental_stmt->execute();
            $rental_result = $rental_stmt->get_result();

            if ($rental_result->num_rows == 0) {
                $form_error = 'Selected rental not found';
                $open_modal = 'editRentalModal';
            } else {
                $rental = $rental_result->fetch_assoc();
                $start = new DateTime($start_date);
                $end = new DateTime($end_date);
                $total_days = $end->diff($start)->days + 1;
                $total_amount = $total_days * $rental['daily_rate'];

                $update_stmt = $conn->prepare("UPDATE rentals SET start_date = ?, end_date = ?, total_days = ?, total_amount = ?, status = ?, notes = ? WHERE id = ?");
                $update_stmt->bind_param("ssidssi", $start_date, $end_date, $total_days, $total_amount, $status, $notes, $rental_id);

                if ($update_stmt->execute()) {
                    if ($status == 'completed' || $status == 'cancelled') {
                        $status_stmt = $conn->prepare("UPDATE cars SET status = 'available' WHERE id = ?");
                        $status_stmt->bind_param("i", $rental['car_id']);
                        $status_stmt->execute();
                        $status_stmt->close();
                    }
                    header('Location: rentals.php');
                    exit();
                }

                $form_error = 'Something went wrong. Please try again.';
                $open_modal = 'editRentalModal';
                $update_stmt->close();
            }

            $rental_stmt->close();
        }
    }
}

if (isset($_GET['duplicate'])) {
    $rental_id = intval($_GET['duplicate']);

    if (isAdmin()) {
        $dup_result = $conn->query("SELECT * FROM rentals WHERE id = $rental_id");
    } else {
        $dup_result = $conn->query("SELECT * FROM rentals WHERE id = $rental_id AND user_id = $user_id");
    }

    if ($dup_result->num_rows > 0) {
        $dup_rental = $dup_result->fetch_assoc();

        $stmt = $conn->prepare("INSERT INTO rentals (user_id, car_id, customer_id, start_date, end_date, total_days, daily_rate, total_amount, payment_status, amount_paid, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, 'active', ?)");
        $stmt->bind_param("iiissiids", $dup_rental['user_id'], $dup_rental['car_id'], $dup_rental['customer_id'], $dup_rental['start_date'], $dup_rental['end_date'], $dup_rental['total_days'], $dup_rental['daily_rate'], $dup_rental['total_amount'], $dup_rental['notes']);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: rentals.php');
    exit();
}

if (isset($_GET['delete'])) {
    $rental_id = intval($_GET['delete']);

    if (isAdmin()) {
        $car_result = $conn->query("SELECT car_id FROM rentals WHERE id = $rental_id");
    } else {
        $car_result = $conn->query("SELECT car_id FROM rentals WHERE id = $rental_id AND user_id = $user_id");
    }

    if ($car_result->num_rows > 0) {
        $car_id = $car_result->fetch_assoc()['car_id'];
        $delete_query = isAdmin() ? "DELETE FROM rentals WHERE id = $rental_id" : "DELETE FROM rentals WHERE id = $rental_id AND user_id = $user_id";

        if ($conn->query($delete_query)) {
            $conn->query("UPDATE cars SET status = 'available' WHERE id = $car_id");
        }
    }

    header('Location: rentals.php');
    exit();
}

$available_cars = isAdmin()
    ? $conn->query("SELECT c.*, u.company_name FROM cars c JOIN users u ON c.user_id = u.id WHERE c.status = 'available' ORDER BY c.brand, c.model")
    : $conn->query("SELECT * FROM cars WHERE user_id = $user_id AND status = 'available' ORDER BY brand, model");

$customers = isAdmin()
    ? $conn->query("SELECT c.*, u.company_name FROM customers c JOIN users u ON c.user_id = u.id ORDER BY c.full_name")
    : $conn->query("SELECT * FROM customers WHERE user_id = $user_id ORDER BY full_name");

if (isAdmin()) {
    $rentals = $conn->query("SELECT r.*, c.brand, c.model, c.plate_number, cu.full_name as customer_name, u.company_name, u.full_name as agent_name
                             FROM rentals r
                             JOIN cars c ON r.car_id = c.id
                             JOIN customers cu ON r.customer_id = cu.id
                             JOIN users u ON r.user_id = u.id
                             ORDER BY r.start_date DESC");
} else {
    $rentals = $conn->query("SELECT r.*, c.brand, c.model, c.plate_number, cu.full_name as customer_name
                             FROM rentals r
                             JOIN cars c ON r.car_id = c.id
                             JOIN customers cu ON r.customer_id = cu.id
                             WHERE r.user_id = $user_id
                             ORDER BY r.start_date DESC");
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Rentals Management</h2>
        <p class="text-muted">Manage all rental transactions</p>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addRentalModal">
            <i class="bi bi-plus-circle me-2"></i>New Rental
        </button>
    </div>
</div>

<?php if ($form_error): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $form_error; ?>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if ($rentals->num_rows > 0): ?>
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
                        <th>Total Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($rental = $rentals->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo $rental['brand'] . ' ' . $rental['model']; ?></strong><br>
                            <small class="text-muted"><?php echo $rental['plate_number']; ?></small>
                        </td>
                        <td><?php echo $rental['customer_name']; ?></td>
                        <?php if (isAdmin()): ?>
                        <td><?php echo $rental['company_name'] ?? $rental['agent_name']; ?></td>
                        <?php endif; ?>
                        <td><?php echo formatDate($rental['start_date']); ?></td>
                        <td><?php echo formatDate($rental['end_date']); ?></td>
                        <td><?php echo formatCurrency($rental['total_amount']); ?></td>
                        <td><?php echo formatCurrency($rental['amount_paid']); ?></td>
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
                        <td>
                            <a href="rental_view.php?id=<?php echo $rental['id']; ?>" class="btn btn-sm btn-outline-info" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-outline-dark edit-rental-btn"
                                    title="Edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editRentalModal"
                                    data-rental-id="<?php echo $rental['id']; ?>"
                                    data-start-date="<?php echo $rental['start_date']; ?>"
                                    data-end-date="<?php echo $rental['end_date']; ?>"
                                    data-daily-rate="<?php echo $rental['daily_rate']; ?>"
                                    data-total-days="<?php echo $rental['total_days']; ?>"
                                    data-total-amount="<?php echo $rental['total_amount']; ?>"
                                    data-status="<?php echo $rental['status']; ?>"
                                    data-notes="<?php echo htmlspecialchars($rental['notes'] ?? '', ENT_QUOTES); ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="rentals.php?duplicate=<?php echo $rental['id']; ?>" class="btn btn-sm btn-outline-success" title="Duplicate"
                               onclick="return confirm('Create a duplicate of this rental?');">
                                <i class="bi bi-files"></i>
                            </a>
                            <?php if ($rental['status'] != 'completed'): ?>
                            <a href="rentals.php?delete=<?php echo $rental['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete"
                               onclick="return confirm('Are you sure you want to delete this rental?');">
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
            <i class="bi bi-clipboard-check fs-1 text-muted"></i>
            <p class="text-muted mt-3">No rentals found. Create your first rental to get started!</p>
            <button type="button" class="btn btn-dark mt-2" data-bs-toggle="modal" data-bs-target="#addRentalModal">
                <i class="bi bi-plus-circle me-2"></i>New Rental
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="addRentalModal" tabindex="-1" aria-labelledby="addRentalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST" action="rentals.php" class="modal-content rental-form" data-mode="add">
            <input type="hidden" name="rental_action" value="create">
            <div class="modal-header">
                <h5 class="modal-title" id="addRentalModalLabel">New Rental</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($available_cars->num_rows == 0): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>No available cars. Please add cars or wait for existing rentals to complete.
                </div>
                <?php elseif ($customers->num_rows == 0): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>No customers found. Please add customers first.
                </div>
                <?php else: ?>
                <div class="mb-3">
                    <label for="add_car_id" class="form-label">Select Car <span class="text-danger">*</span></label>
                    <select class="form-select rental-car-select" id="add_car_id" name="car_id" required>
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
                    <label for="add_customer_id" class="form-label">Select Customer <span class="text-danger">*</span></label>
                    <select class="form-select" id="add_customer_id" name="customer_id" required>
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
                    <div class="col-md-4 mb-3">
                        <label for="add_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control rental-start-date" id="add_start_date" name="start_date" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="add_rental_period" class="form-label">Rental Period</label>
                        <select class="form-select rental-period" id="add_rental_period" name="rental_period">
                            <option value="">Custom</option>
                            <option value="day">1 Day</option>
                            <option value="week">1 Week</option>
                            <option value="month">1 Month (30 Days)</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="add_end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control rental-end-date" id="add_end_date" name="end_date" required>
                    </div>
                </div>

                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <p class="mb-1 text-muted">Daily Rate</p>
                                <h5 class="rental-rate-display">RM 0.00</h5>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1 text-muted">Total Days</p>
                                <h5 class="rental-days-display">0</h5>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1 text-muted">Total Amount</p>
                                <h5 class="rental-amount-display">RM 0.00</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="add_payment_status" class="form-label">Payment Status</label>
                        <select class="form-select" id="add_payment_status" name="payment_status">
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="add_amount_paid" class="form-label">Amount Paid (RM)</label>
                        <input type="number" class="form-control" id="add_amount_paid" name="amount_paid" step="0.01" min="0" value="0">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="add_notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="add_notes" name="notes" rows="3"></textarea>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <?php if ($available_cars->num_rows > 0 && $customers->num_rows > 0): ?>
                <button type="submit" class="btn btn-dark">
                    <i class="bi bi-plus-circle me-2"></i>Create Rental
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editRentalModal" tabindex="-1" aria-labelledby="editRentalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST" action="rentals.php" class="modal-content rental-form" data-mode="edit">
            <input type="hidden" name="rental_action" value="update">
            <input type="hidden" name="rental_id" id="edit_rental_id">
            <div class="modal-header">
                <h5 class="modal-title" id="editRentalModalLabel">Edit Rental</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="edit_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control rental-start-date" id="edit_start_date" name="start_date" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="edit_rental_period" class="form-label">Rental Period</label>
                        <select class="form-select rental-period" id="edit_rental_period" name="rental_period">
                            <option value="">Custom</option>
                            <option value="day">1 Day</option>
                            <option value="week">1 Week</option>
                            <option value="month">1 Month (30 Days)</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="edit_end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control rental-end-date" id="edit_end_date" name="end_date" required>
                    </div>
                </div>

                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <p class="mb-1 text-muted">Daily Rate</p>
                                <h6 class="rental-rate-display">RM 0.00</h6>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1 text-muted">Total Days</p>
                                <h6 class="rental-days-display">0 days</h6>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1 text-muted">Total Amount</p>
                                <h6 class="rental-amount-display">RM 0.00</h6>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="mb-3">
                    <label for="edit_status" class="form-label">Rental Status</label>
                    <select class="form-select" id="edit_status" name="status">
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Payment tracking:</strong> Use the "View Details" page to add and manage payments with receipt proof.
                </div>

                <div class="mb-3">
                    <label for="edit_notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark">
                    <i class="bi bi-check-circle me-2"></i>Update Rental
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function parseDateInput(value) {
        const parts = value.split('-').map(Number);
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function formatDateInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatCurrency(amount) {
        return 'RM ' + Number(amount || 0).toFixed(2);
    }

    function getDailyRate(form) {
        const carSelect = form.querySelector('.rental-car-select');
        if (carSelect) {
            const selectedCar = carSelect.options[carSelect.selectedIndex];
            return parseFloat(selectedCar?.dataset.rate) || 0;
        }

        return parseFloat(form.dataset.dailyRate) || 0;
    }

    function applyRentalPeriod(form) {
        const startDate = form.querySelector('.rental-start-date');
        const endDate = form.querySelector('.rental-end-date');
        const rentalPeriod = form.querySelector('.rental-period');

        if (!startDate.value || !rentalPeriod.value) {
            return;
        }

        const end = parseDateInput(startDate.value);

        if (rentalPeriod.value === 'week') {
            end.setDate(end.getDate() + 6);
        } else if (rentalPeriod.value === 'month') {
            end.setDate(end.getDate() + 29);
        }

        endDate.value = formatDateInput(end);
        endDate.min = startDate.value;
    }

    function calculateTotal(form) {
        const startDate = form.querySelector('.rental-start-date');
        const endDate = form.querySelector('.rental-end-date');
        const dailyRate = getDailyRate(form);

        if (!startDate.value || !endDate.value) {
            return;
        }

        const start = parseDateInput(startDate.value);
        const end = parseDateInput(endDate.value);
        const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;

        if (diffDays > 0) {
            form.querySelector('.rental-rate-display').textContent = formatCurrency(dailyRate);
            form.querySelector('.rental-days-display').textContent = form.dataset.mode === 'edit' ? diffDays + ' days' : diffDays;
            form.querySelector('.rental-amount-display').textContent = formatCurrency(diffDays * dailyRate);
        }
    }

    document.querySelectorAll('.rental-form').forEach(function(form) {
        const carSelect = form.querySelector('.rental-car-select');
        const startDate = form.querySelector('.rental-start-date');
        const endDate = form.querySelector('.rental-end-date');
        const rentalPeriod = form.querySelector('.rental-period');

        if (carSelect) {
            carSelect.addEventListener('change', function() {
                calculateTotal(form);
            });
        }

        startDate.addEventListener('change', function() {
            endDate.min = this.value;
            applyRentalPeriod(form);
            calculateTotal(form);
        });

        rentalPeriod.addEventListener('change', function() {
            applyRentalPeriod(form);
            calculateTotal(form);
        });

        endDate.addEventListener('change', function() {
            rentalPeriod.value = '';
            calculateTotal(form);
        });
    });

    document.querySelectorAll('.edit-rental-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const form = document.querySelector('#editRentalModal .rental-form');
            form.dataset.dailyRate = this.dataset.dailyRate;

            document.getElementById('edit_rental_id').value = this.dataset.rentalId;
            document.getElementById('edit_start_date').value = this.dataset.startDate;
            document.getElementById('edit_end_date').value = this.dataset.endDate;
            document.getElementById('edit_end_date').min = this.dataset.startDate;
            document.getElementById('edit_rental_period').value = '';
            document.getElementById('edit_status').value = this.dataset.status;
            document.getElementById('edit_notes').value = this.dataset.notes || '';

            form.querySelector('.rental-rate-display').textContent = formatCurrency(this.dataset.dailyRate);
            form.querySelector('.rental-days-display').textContent = this.dataset.totalDays + ' days';
            form.querySelector('.rental-amount-display').textContent = formatCurrency(this.dataset.totalAmount);
        });
    });

    const editRentalId = new URLSearchParams(window.location.search).get('edit');
    if (editRentalId) {
        document.querySelector(`.edit-rental-btn[data-rental-id="${editRentalId}"]`)?.click();
    }

    <?php if ($open_modal): ?>
    new bootstrap.Modal(document.getElementById('<?php echo $open_modal; ?>')).show();
    <?php endif; ?>
});
</script>

<?php
closeDBConnection($conn);
include 'includes/footer.php';
?>
