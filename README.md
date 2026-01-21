# Car Rental Management System v2.0

A comprehensive web-based car rental management system built with PHP, MySQL, and Bootstrap.

## Features

### User Management
- **Sign Up/Login System**: Secure authentication for companies and agents
- **Role-Based Access**: Separate access levels for Admin and Agents
- **Profile Management**: Users can update their information and change passwords

### Car Management
- Add, edit, and delete cars from the fleet
- Track car details (brand, model, year, color, plate number)
- Set daily rental rates
- Monitor car status (available, rented, maintenance)

### Customer Management
- Maintain customer database
- Store customer details (name, contact, ID, license number)
- Quick access to customer rental history

### Rental Management
- Create new rental bookings
- Track rental periods and dates
- Calculate rental amounts automatically
- Monitor payment status (pending, partial, paid)
- Update rental status (active, completed, cancelled)
- View detailed rental information

### Admin Panel
- System-wide overview and statistics
- Manage all users (agents/companies)
- View all cars, customers, and rentals
- Activate/deactivate user accounts
- Access comprehensive system reports

## Technology Stack

- **Frontend**: Bootstrap 5.3, HTML5, CSS3, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Icons**: Bootstrap Icons

## Installation

### Prerequisites
- XAMPP, WAMP, or any PHP development environment
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Setup Instructions

1. **Clone/Download the project**
   - Place the `crms-v2` folder in your `htdocs` directory (for XAMPP: `C:\xampp\htdocs\`)

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the `database.sql` file
   - Or copy and execute the SQL commands from `database.sql`

3. **Configure Database Connection**
   - Open `config/database.php`
   - Update database credentials if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'car_rental_db');
     ```

4. **Access the Application**
   - Open your browser and navigate to: `http://localhost/crms-v2/`
   - You will be redirected to the login page

## Default Login Credentials

### Admin Account
- **Username**: admin
- **Password**: admin123

### Test Agent Account
- **Username**: agent1
- **Password**: admin123

## File Structure

```
crms-v2/
├── assets/
│   ├── css/
│   │   └── style.css          # Custom styles
│   └── js/
│       └── main.js            # JavaScript functions
├── config/
│   ├── config.php             # Main configuration
│   └── database.php           # Database connection
├── includes/
│   ├── header.php             # Header template
│   └── footer.php             # Footer template
├── admin.php                  # Admin panel
├── cars.php                   # Car listing
├── car_add.php                # Add new car
├── car_edit.php               # Edit car
├── customers.php              # Customer listing
├── customer_add.php           # Add new customer
├── customer_edit.php          # Edit customer
├── dashboard.php              # Main dashboard
├── database.sql               # Database schema
├── index.php                  # Entry point
├── login.php                  # Login page
├── logout.php                 # Logout handler
├── profile.php                # User profile
├── rentals.php                # Rental listing
├── rental_add.php             # Add new rental
├── rental_edit.php            # Edit rental
├── rental_view.php            # View rental details
├── signup.php                 # Registration page
└── README.md                  # This file
```

## Usage Guide

### For Agents/Companies

1. **Sign Up**: Create an account with your company details
2. **Add Cars**: Build your fleet by adding vehicles
3. **Add Customers**: Register your customers
4. **Create Rentals**: Book cars for customers and track payments
5. **Manage Profile**: Update your information as needed

### For Administrators

1. **Login**: Use admin credentials
2. **Dashboard**: View system-wide statistics
3. **Admin Panel**: Manage all users and view their activities
4. **Access All Data**: View and manage all cars, customers, and rentals
5. **User Control**: Activate/deactivate user accounts

## Features Highlight

### Dashboard Statistics
- Total cars, available cars, and cars under maintenance
- Total customers and active rentals
- Revenue tracking and payment status
- Recent rental activities

### Smart Rental System
- Automatic calculation of rental duration
- Dynamic pricing based on daily rates
- Real-time car availability status
- Payment tracking (pending, partial, paid)

### Security Features
- Password hashing using PHP's password_hash()
- SQL injection prevention with prepared statements
- Session management
- Role-based access control

## Design Theme

The system uses a clean, modern light theme with black and white color scheme:
- **Primary Color**: Black (#000000)
- **Background**: Light gray (#f8f9fa)
- **Cards**: White with subtle shadows
- **Focus**: Professional and easy on the eyes

## Browser Compatibility

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Support

For issues or questions, please check the code comments or database schema.

## License

This project is open-source and available for educational and commercial use.

## Version

**Version 2.0** - January 2026

---

**Built with ❤️ for efficient car rental operations management**
