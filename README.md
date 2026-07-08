# Smart Hall Allocation & Reservation System (SHARS)

Welcome to **SHARS**, a complete web application built to manage university hall reservations. It uses a modern **React + Vite** frontend, an **Object-Oriented PHP** REST API backend, and a **MySQL** database.

This project is tailored for XAMPP environments, making it simple to run locally on your machine.

---

## 📁 Workspace Folder Structure

The project is structured into three main modules: `frontend`, `backend`, and `database`. Here is a breakdown of how they are organized:

```text
SHARS/
├── database/
│   └── database.sql          # MySQL database schema & seed data
├── backend/
│   ├── config/
│   │   └── Database.php      # Database connection configuration (PDO)
│   ├── controllers/          # Business logic handlers for API requests
│   │   ├── AuthController.php
│   │   ├── FeedbackController.php
│   │   ├── HallController.php
│   │   ├── NotificationController.php
│   │   └── ReservationController.php
│   ├── helpers/
│   │   └── ResponseHelper.php # JSON formatting & CORS headers
│   ├── middleware/
│   │   └── AuthMiddleware.php # Session check and ACL verification
│   ├── models/               # OOP Data models & patterns
│   │   ├── AbstractModel.php
│   │   ├── User.php          # Abstract base class
│   │   ├── StudentRepresentative.php # Extends User
│   │   ├── Lecturer.php      # Extends User
│   │   ├── Admin.php         # Extends User
│   │   ├── Hall.php
│   │   ├── Reservation.php
│   │   ├── Feedback.php
│   │   └── Notification.php
│   ├── routes/
│   │   └── Router.php        # Simple REST API router
│   ├── uploads/              # Storage directory for hall images
│   └── index.php             # Backend entry point & route registration
├── frontend/
│   ├── dist/                 # Compiled, production-ready frontend assets
│   ├── public/               # Static assets (images, icons)
│   ├── src/                  # React source files
│   │   ├── assets/           # Stylings and images
│   │   ├── components/       # Reusable React components (Navbar, ProtectedRoute)
│   │   ├── context/          # Context API state providers (AuthContext, HallContext)
│   │   ├── layouts/          # Page layouts (MainLayout, AdminLayout)
│   │   ├── pages/            # View pages (Login, Dashboard, Admin Control, etc.)
│   │   ├── routes/           # AppRoutes route protection map
│   │   ├── services/         # API integration layer using Axios
│   │   ├── App.jsx           # Root App component
│   │   └── main.jsx          # Entry point
│   ├── package.json          # Dependency configurations
│   └── vite.config.js        # Vite compiler settings
├── index.php                 # Root redirect file (forwards localhost/SHARS/ to frontend/dist/)
└── SRS_DOCUMENTATION.md      # Detailed system specifications and logic module description
```

---

## 🛠️ Step-by-Step Local Setup Instructions

Follow these steps to run the complete website on your computer:

### 1. Database Setup (MySQL)
1. Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Open your web browser and navigate to: `http://localhost/phpmyadmin/`.
3. Click on the **SQL** tab.
4. Open the `database/database.sql` file in this workspace, copy the entire SQL script contents, paste them into the phpMyAdmin SQL command box, and click **Go**.
5. This creates the `smart_hall_db` database and seeds it with default accounts:
   * **Admin User:** `admin@smarthall.com` | Password: `admin123`
   * **Student Rep:** `student@smarthall.com` | Password: `student123`
   * **Lecturer:** `lecturer@smarthall.com` | Password: `lecturer123`

### 2. Backend Verification
* Verify your database credentials in `backend/config/Database.php`. The defaults are set to `localhost`, db `smart_hall_db`, username `root`, and no password (`""`), which matches default XAMPP credentials.
* Open your browser and navigate to `http://localhost/SHARS/backend/halls` to test if the API can fetch the halls list. You should see a JSON output showing the seeded university halls.

### 3. Running the React Frontend
There are two ways you can run the frontend:

#### Option A: Running Compiled Production Build (Recommended)
This workspace already comes with a fully compiled frontend inside `frontend/dist/`. 
1. Open your browser.
2. Go to: `http://localhost/SHARS/`
3. The root `index.php` will automatically redirect you to `http://localhost/SHARS/frontend/dist/`. You can log in, book halls, view recommendations, test dashboards, and approve/reject bookings immediately!

#### Option B: Running React Development Server (For modifying code)
If you want to edit React source files (`src/` folder) and see changes live:
1. Open your terminal in the frontend directory:
   ```bash
   cd frontend
   ```
2. Install the node packages:
   ```bash
   npm install
   ```
3. Start the Vite development server:
   ```bash
   npm run dev
   ```
4. Open the URL displayed in the terminal (usually `http://localhost:5173`) in your web browser. Any changes you make to the files will auto-reload in the browser.

---

## 🔒 Security & Validation Highlights
* **Authentication:** Handled through PHP Session states (`$_SESSION`) that map request context to database records.
* **SQL Injection Protection:** Queries run on the PDO driver using strictly prepared statements with bound parameter arrays.
* **Role Verification:** The router checks session-level roles before dispatching requests to controllers.
* **Form Validations:** The frontend provides reactive checks (email pattern, date checks) while the PHP models run strict server-side validation rules.
