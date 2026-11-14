# 🚗 AutoNexus
### Smart Automotive Marketplace & Management System

AutoNexus is a web-based automotive marketplace designed to simplify vehicle discovery, buying, selling, valuation, and inventory management through a single platform.

## 📌 Project Overview

AutoNexus provides a centralized platform connecting vehicle buyers, sellers, and administrators. It combines an intuitive frontend with backend and database integration to manage automotive services efficiently.

## ✨ Key Features

- **New & Used Vehicles:** Explore available vehicle listings.
- **Smart Buy Advisory:** Get assistance when selecting suitable vehicles.
- **Vehicle Valuation:** Submit vehicle details for estimated valuation.
- **Import On Demand:** Manage vehicle import requests.
- **Accessories Store:** Explore automotive accessories.
- **Sell Your Vehicle:** Submit vehicles for administrative review.
- **Admin Dashboard:** Manage vehicle listings, inventory, submissions, and requests.
- **Database Integration:** Store and manage application data through MySQL.

## 🛠️ Technologies Used

- HTML5 – Website structure
- CSS3 – Styling and responsive design
- JavaScript – Frontend functionality
- PHP – Backend development (if used in the project)
- MySQL – Database management
- XAMPP – Local development environment
- phpMyAdmin – Database administration

## 💻 Software Requirements

Install the following applications before running AutoNexus:

1. **XAMPP:** Apache and MySQL server.
2. **Web Browser:** Google Chrome, Microsoft Edge, or Firefox.
3. **Code Editor:** Visual Studio Code (recommended).
4. **Git:** Optional, for source code management.

## ⚙️ Installation & Setup

Follow these steps to run AutoNexus locally.

### Step 1: Install XAMPP
Download and install XAMPP from:
https://www.apachefriends.org/

### Step 2: Move Project Files
1. Open the XAMPP installation directory.
2. Navigate to the `htdocs` folder.
3. Copy or extract the AutoNexus project folder inside it.

Example:
`C:\xampp\htdocs\AutoNexus`

### Step 3: Start Local Servers
1. Open XAMPP Control Panel.
2. Start **Apache**.
3. Start **MySQL**.

Both services should be running.

### Step 4: Configure the Database
1. Open your browser.
2. Visit `http://localhost/phpmyadmin/`.
3. Create a new database named `autonexus`.
4. Import the provided SQL file (`autonexus.sql`) through the Import tab.
5. Configure the database connection settings in the project's backend configuration file.

Use your actual database name, username, password, and port.

### Step 5: Launch AutoNexus
Open your browser and visit:

`http://localhost/AutoNexus/`

The website should now be accessible locally.

## 🗂️ Project Structure

- `frontend/` – Website interface and frontend assets.
- `backend/` – Server-side logic and application APIs.
- `database/` – SQL database setup files.
- `docs/` – Project documentation and screenshots.
- `README.md` – Project instructions.

*Actual folder names may differ depending on the project structure.*

## 🔧 Database Management

Database operations are handled through MySQL. Administrators can manage supported application records through the website's administration interface, while phpMyAdmin provides direct database administration.

## 🎯 Project Objective

The primary objective of AutoNexus is to demonstrate practical web development, database integration, CRUD operations, and frontend-backend communication through a real-world automotive marketplace application.

## 👨‍💻 Developer

**AutoNexus Development Project**

Developed as an academic minor project.

---

⭐ If you find this project interesting, feel free to explore the repository and its features.

**Note:** This project is intended for local development and academic demonstration. Ensure the database configuration and required services are correctly set up before running it.
