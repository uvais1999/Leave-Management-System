# 🧾 Employee Leave Management System

A simple Employee Leave Management System built using **Laravel + Breeze (Vue)**.
This application supports role-based access for Admin, Manager, and Employee with full leave management functionality.

---

## 🚀 Setup Instructions

Follow the steps below to run the project locally:

### 1. Clone Repository

```bash
git clone <your-repo-url>
cd leave-management
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Update your database credentials in `.env` file:

```env
DB_DATABASE=leave_management
DB_USERNAME=root
DB_PASSWORD=
```

---

### 4. Run Migrations & Seeders

```bash
php artisan migrate:fresh --seed
```

---

### 5. Run the Application

```bash
npm run dev
php artisan serve
```

Visit:

```
http://127.0.0.1:8000
```

---

## 🔐 Default Login Credentials

### 👑 Admin

* Email: [admin@test.com](mailto:admin@test.com)
* Password: Admin@123

---

### 🧑‍💼 Manager

* Email: [manager1@test.com](mailto:manager1@test.com)
* Password: password

---

### 👨‍💻 Employee

* Email: [employee1@test.com](mailto:employee1@test.com)
* Password: password

---

## 🎯 Features

### Authentication

* User Registration & Login
* Password hashing (Laravel default)
* Role-based access control

### Roles

* Admin
* Manager
* Employee

---

### Admin Capabilities

* Manage users (assign roles, activate/deactivate)
* Assign employees to managers
* Manage leave types (CRUD)
* View all leave applications

---

### Manager Capabilities

* View team leave requests
* Approve / Reject leave applications
* Add remarks

---

### Employee Capabilities

* Apply for leave
* View own leave history
* Edit/Delete pending leave requests

---

### Leave Management

* Leave Types (Admin)
* Leave Applications
* Status flow: Pending → Approved / Rejected
* File upload (PDF/JPG/PNG)

---

### Filters & Search

* Filter by status
* Filter by date range
* Search by employee name
* Pagination (10 per page)

---

## 🧱 Tech Stack

* Laravel 11/12
* Laravel Breeze (Vue + Inertia)
* MySQL / SQLite
* Tailwind CSS
* Spatie Laravel Permission

---

## ⚠️ Notes / Limitations

* UI design is minimal (focus on functionality as per requirements)
* Email notifications are not implemented
* Leave balance calculation is not included
* Charts/dashboard analytics are not implemented

---

## 📌 Important Command

To reset and run the project completely:

```bash
php artisan migrate:fresh --seed
```

---

## 👨‍💻 Author

Uvais C.K
Software Developer (Laravel + Vue)

---

## ✅ Status

✔ Core features completed
✔ Ready for evaluation
⚡ Bonus features can be added if required

---
