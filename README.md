# Stationery Request & Approval System 🚀

A complete internal stationery inventory management and approval workflow system, built using PHP, MySQL, HTML/CSS, and PHPMailer.

---

## 📌 Project Overview

This system allows employees to request stationery, which then goes through an approval process by their manager. Once approved, the items are recorded in the database and a confirmation is sent to all stakeholders.

---

## ✅ Key Features

### Employee-Side

* Item request form (index.html)
* Real-time inventory dropdown (only shows available items)
* Axis Code verification for restocking
* Automatic manager email with approval/rejection buttons
* No visibility of approval links to the requester (for security)

### Manager Email

* Receives formatted email with item list
* Direct approve/reject links embedded (unique per request)

### Admin Panel

* View all pending, approved, and rejected requests
* Export records (CSV / PDF / Print)
* Separate filters for transactions and inventory
* Prevents duplicate approvals or invalid submissions

### Backend

* Built in pure PHP with MySQL database
* Approval email flow using PHPMailer with SMTP (SSL)
* Accurate IST time zone handling (via `date("Y-m-d H:i:s", time() + 19800)`)
* Automatically logs to `stationery_log` table once approved
* Sends confirmation email to employee with item breakdown

---

## 📂 Folder Structure

```
public_html/
├── adminstat/
    ├── index.html               // Employee request form
    ├── admin.html               // Admin dashboard
    ├── submit.php               // Handles both issuance & restock
    ├── send_approval_email.php  // Sends approval request to manager
    ├── approve.php              // Handles manager approval
    ├── update_approval_status.php // Used by admin to approve/reject
    ├── approval_requests.php    // Admin view of pending approvals
    ├── stationery_log (MySQL)     // Final transaction log
    └── phpmailer/               // PHPMailer library
```

---

## 🛠️ Tech Stack

* PHP 7+
* MySQL
* HTML/CSS
* PHPMailer (SMTP mailing)
* Hosted on cPanel

---

## 💡 Challenges Solved

* Prevented employees from seeing manager-only links in emails
* Resolved timezone inconsistencies (server vs local)
* Avoided duplicate approval using DB checks
* Secured Axis Code validation for restocks
* Clean UI with real-time filtering and exports

---

## 📧 SMTP Configuration

```
Host: mail.prayatnaworld.org
Port: 465 (SSL)
Username: adminstationary@prayatnaworld.org
Password: [secured]
```

---

## 🙋‍♂️ Contributions / Ideas Welcome!

If you're working on workflow automation, admin dashboards, or internal tools like this, feel free to open a discussion, fork the project, or reach out!

---

## 📎 License

This project is built for internal automation purposes and shared for learning and collaboration. Use freely with proper credit.

---

Made with ❤️ by [Sandeep Kumar](https://www.linkedin.com/in/sandeep-kumar-prayatna)
IT Officer | Workflow Automation | Full Stack Enthusiast
