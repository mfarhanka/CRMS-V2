<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Brand <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="brand" value="<?php echo $car_form['brand']; ?>" required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Model <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="model" value="<?php echo $car_form['model']; ?>" required>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Year</label>
        <input type="number" class="form-control" name="year" min="1900" max="2099" value="<?php echo $car_form['year']; ?>">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Color</label>
        <input type="text" class="form-control" name="color" value="<?php echo $car_form['color']; ?>">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Plate Number <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="plate_number" value="<?php echo $car_form['plate_number']; ?>" required>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Daily Rate (RM) <span class="text-danger">*</span></label>
        <input type="number" class="form-control" name="daily_rate" step="0.01" min="0" value="<?php echo $car_form['daily_rate']; ?>" required>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Weekly Rate (RM)</label>
        <input type="number" class="form-control" name="weekly_rate" step="0.01" min="0" value="<?php echo $car_form['weekly_rate']; ?>">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Monthly Rate (RM)</label>
        <input type="number" class="form-control" name="monthly_rate" step="0.01" min="0" value="<?php echo $car_form['monthly_rate']; ?>">
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
            <option value="available" <?php echo $car_form['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
            <option value="rented" <?php echo $car_form['status'] == 'rented' ? 'selected' : ''; ?>>Rented</option>
            <option value="maintenance" <?php echo $car_form['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
        </select>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea class="form-control" name="description" rows="3"><?php echo $car_form['description']; ?></textarea>
</div>
