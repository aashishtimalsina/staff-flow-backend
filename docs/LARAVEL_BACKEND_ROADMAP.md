# StaffFlow Laravel Backend - Complete Implementation Roadmap

## Table of Contents

1. [System Overview](#system-overview)
2. [Authentication & Authorization](#authentication--authorization)
3. [Database Schema](#database-schema)
4. [API Endpoints](#api-endpoints)
5. [Admin Panel Requirements](#admin-panel-requirements)
6. [Implementation Steps](#implementation-steps)

---

## System Overview

StaffFlow is a comprehensive staffing agency management platform that requires a robust backend to handle:

- Multi-role authentication (Super Admin, Admin, Recruiter, Finance, Compliance, Worker)
- Client and candidate management
- Booking and assignment workflows
- Compliance document tracking
- Financial operations (timesheets, invoices)
- Rate card management
- Audit logging
- Real-time notifications

---

## Authentication & Authorization

### User Roles Hierarchy

```
Super Admin (role='superadmin')
├── Can access Admin Panel
├── Can create Admin users
└── Full system access

Admin (role='admin')
├── Created by Super Admin
├── Can login to main application
├── Can manage all operations
└── Cannot access Admin Panel

Recruiter (role='recruiter')
├── Manage candidates, clients, bookings
└── Limited access

Finance (role='finance')
├── Manage timesheets and invoices
└── Financial operations only

Compliance (role='compliance')
├── Manage compliance documents
└── Verification tasks

Worker (role='worker')
├── View own profile and schedule
└── Submit timesheets
```

### Authentication Flow

1. **Admin Panel Login** (Separate from Main App)

   - URL: `/admin/login`
   - Only accessible by users with `role='superadmin'`
   - Super Admin can create users with `role='admin'`

2. **Main Application Login**

   - URL: `/api/auth/login`
   - Accessible by all roles EXCEPT `superadmin`
   - Returns JWT token for frontend

3. **Token Management**
   - Use Laravel Sanctum or JWT for API authentication
   - Token expiration: 24 hours (configurable)
   - Refresh token mechanism

---

## Database Schema

### 1. Users Table

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uid VARCHAR(255) UNIQUE NOT NULL, -- Unique identifier (like Firebase UID)
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar_url VARCHAR(500) NULL,
    role ENUM('superadmin', 'admin', 'recruiter', 'finance', 'compliance', 'worker') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_role (role),
    INDEX idx_email (email),
    INDEX idx_uid (uid)
);
```

### 2. Company Profile Table

```sql
CREATE TABLE company_profile (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    logo_url VARCHAR(500) NULL,
    bank_name VARCHAR(255) NULL,
    account_number VARCHAR(100) NULL,
    sort_code VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 3. Job Roles Table

```sql
CREATE TABLE job_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_title (title)
);
```

### 4. Compliance Documents Table

```sql
CREATE TABLE compliance_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_role_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (job_role_id) REFERENCES job_roles(id) ON DELETE CASCADE,
    INDEX idx_job_role (job_role_id)
);
```

### 5. Candidates Table

```sql
CREATE TABLE candidates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL, -- References users table
    job_role_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    dob DATE NULL,
    location VARCHAR(255) NULL,
    home_location VARCHAR(255) NULL,
    skills JSON NULL, -- Array of skills
    availability JSON NULL, -- Array of ISO date strings
    status ENUM('New', 'Screened', 'Interviewed', 'Compliant', 'Active', 'Inactive') DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_role_id) REFERENCES job_roles(id) ON DELETE RESTRICT,
    INDEX idx_user_id (user_id),
    INDEX idx_job_role_id (job_role_id),
    INDEX idx_status (status)
);
```

### 6. Candidate Compliance Table

```sql
CREATE TABLE candidate_compliance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id BIGINT UNSIGNED NOT NULL,
    compliance_document_id BIGINT UNSIGNED NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    status ENUM('Pending', 'Submitted', 'Approved', 'Rejected', 'Expired') DEFAULT 'Pending',
    file_url VARCHAR(500) NULL,
    expiry_date DATE NULL,
    verified_by BIGINT UNSIGNED NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (compliance_document_id) REFERENCES compliance_documents(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_candidate_id (candidate_id),
    INDEX idx_status (status),
    INDEX idx_expiry_date (expiry_date)
);
```

### 7. Clients Table

```sql
CREATE TABLE clients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT NULL,
    primary_contact VARCHAR(255) NULL,
    account_manager_contact VARCHAR(255) NULL,
    finance_contact VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_name (name)
);
```

### 8. Rate Cards Table

```sql
CREATE TABLE rate_cards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    job_role_id BIGINT UNSIGNED NOT NULL,
    effective_date DATETIME NOT NULL,

    -- Day rates
    day_pay_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    day_bill_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,

    -- Night rates
    night_pay_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    night_bill_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,

    -- Weekend rates
    weekend_pay_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    weekend_bill_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,

    -- Bank Holiday rates
    bank_holiday_pay_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    bank_holiday_bill_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (job_role_id) REFERENCES job_roles(id) ON DELETE CASCADE,
    INDEX idx_client_job_role (client_id, job_role_id),
    INDEX idx_effective_date (effective_date)
);
```

### 9. Booking Requests Table

```sql
CREATE TABLE booking_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    job_role_id BIGINT UNSIGNED NOT NULL,
    shift_start_time DATETIME NOT NULL,
    shift_end_time DATETIME NOT NULL,
    location VARCHAR(255) NULL,
    candidates_needed INT DEFAULT 1,
    status ENUM('Open', 'Booked', 'Completed', 'Cancelled') DEFAULT 'Open',
    pay_rate DECIMAL(10, 2) NOT NULL,
    bill_rate DECIMAL(10, 2) NOT NULL,
    notes TEXT NULL,
    cancellation_reason TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (job_role_id) REFERENCES job_roles(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_client_id (client_id),
    INDEX idx_status (status),
    INDEX idx_shift_dates (shift_start_time, shift_end_time)
);
```

### 10. Assignments Table

```sql
CREATE TABLE assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_request_id BIGINT UNSIGNED NOT NULL,
    candidate_id BIGINT UNSIGNED NOT NULL,
    status ENUM('Confirmed', 'Cancelled', 'Completed') DEFAULT 'Confirmed',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (booking_request_id) REFERENCES booking_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_booking_request_id (booking_request_id),
    INDEX idx_candidate_id (candidate_id),
    INDEX idx_status (status),

    UNIQUE KEY unique_booking_candidate (booking_request_id, candidate_id)
);
```

### 11. Timesheets Table

```sql
CREATE TABLE timesheets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timesheet_number VARCHAR(50) UNIQUE NOT NULL, -- e.g., TS-12345
    assignment_id BIGINT UNSIGNED NOT NULL,
    hours_standard DECIMAL(10, 2) DEFAULT 0.00,
    hours_overtime DECIMAL(10, 2) DEFAULT 0.00,
    breaks DECIMAL(10, 2) DEFAULT 0.00,
    expenses TEXT NULL,
    status ENUM('Draft', 'Submitted', 'Approved', 'Rejected', 'Locked') DEFAULT 'Draft',
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_assignment_id (assignment_id),
    INDEX idx_status (status),
    INDEX idx_timesheet_number (timesheet_number)
);
```

### 12. Interviews Table

```sql
CREATE TABLE interviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    interview_time DATETIME NOT NULL,
    interview_type VARCHAR(100) NULL, -- e.g., 'Phone', 'In-Person', 'Video'
    outcome ENUM('Scheduled', 'Completed', 'Offer', 'Reject') DEFAULT 'Scheduled',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_candidate_id (candidate_id),
    INDEX idx_client_id (client_id),
    INDEX idx_interview_time (interview_time)
);
```

### 13. Invoices Table

```sql
CREATE TABLE invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL, -- e.g., INV-2024-001
    client_id BIGINT UNSIGNED NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    status ENUM('Draft', 'Sent', 'Part-Paid', 'Paid', 'Overdue', 'Cancelled') DEFAULT 'Draft',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_client_id (client_id),
    INDEX idx_status (status),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_period (period_start, period_end)
);
```

### 14. Invoice Line Items Table

```sql
CREATE TABLE invoice_line_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    INDEX idx_invoice_id (invoice_id)
);
```

### 15. Notifications Table

```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(500) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);
```

### 16. Audit Logs Table

```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    user_name VARCHAR(255) NULL,
    action ENUM('create', 'update', 'delete', 'cancel', 'approve', 'archive', 'assign', 'send', 'login', 'logout') NOT NULL,
    entity VARCHAR(100) NOT NULL, -- e.g., 'user', 'booking', 'timesheet'
    entity_id VARCHAR(100) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_entity (entity, entity_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);
```

---

## API Endpoints

### Base URL: `/api/v1`

### Authentication Endpoints

#### 1. Login (Main Application)

```
POST /api/v1/auth/login
```

**Request Body:**

```json
{
  "email": "admin@example.com",
  "password": "password123"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "uid": "unique-uid-123",
      "name": "John Doe",
      "email": "admin@example.com",
      "avatar_url": "https://example.com/avatar.jpg",
      "role": "admin"
    }
  }
}
```

**Notes:**

- Reject login if user role is 'superadmin'
- Return 403 for superadmin attempting main app login

---

#### 2. Logout

```
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

**Response:**

```json
{
  "success": true,
  "message": "Successfully logged out"
}
```

---

#### 3. Refresh Token

```
POST /api/v1/auth/refresh
Authorization: Bearer {token}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "token": "new-token",
    "expires_in": 86400
  }
}
```

---

#### 4. Get Current User

```
GET /api/v1/auth/me
Authorization: Bearer {token}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "uid": "unique-uid-123",
    "name": "John Doe",
    "email": "admin@example.com",
    "avatar_url": "https://example.com/avatar.jpg",
    "role": "admin",
    "created_at": "2024-01-01T00:00:00.000Z"
  }
}
```

---

#### 5. Password Reset Request

```
POST /api/v1/auth/password/reset-request
```

**Request Body:**

```json
{
  "email": "user@example.com"
}
```

---

#### 6. Password Reset Confirm

```
POST /api/v1/auth/password/reset
```

**Request Body:**

```json
{
  "email": "user@example.com",
  "token": "reset-token",
  "password": "newPassword123",
  "password_confirmation": "newPassword123"
}
```

---

### User Management Endpoints

#### 7. Get All Users

```
GET /api/v1/users
Authorization: Bearer {token}
```

**Query Parameters:**

- `role` (optional): Filter by role
- `is_active` (optional): Filter by active status
- `page` (optional): Page number
- `per_page` (optional): Items per page (default: 15)

**Response:**

```json
{
  "success": true,
  "data": {
    "users": [
      {
        "id": 1,
        "uid": "unique-uid-123",
        "name": "John Doe",
        "email": "john@example.com",
        "role": "admin",
        "avatar_url": null,
        "is_active": true,
        "created_at": "2024-01-01T00:00:00.000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 50,
      "last_page": 4
    }
  }
}
```

---

#### 8. Create User

```
POST /api/v1/users
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "password": "password123",
  "role": "recruiter",
  "avatar_url": "https://example.com/avatar.jpg"
}
```

**Permissions:** Admin, Super Admin

---

#### 9. Update User

```
PUT /api/v1/users/{id}
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "name": "Jane Smith Updated",
  "email": "jane.updated@example.com",
  "role": "finance",
  "is_active": true
}
```

---

#### 10. Delete User

```
DELETE /api/v1/users/{id}
Authorization: Bearer {token}
```

**Permissions:** Admin, Super Admin

---

### Job Roles Endpoints

#### 11. Get All Job Roles

```
GET /api/v1/job-roles
Authorization: Bearer {token}
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Healthcare Assistant",
      "description": "Provides care and support to patients",
      "documents": [
        {
          "id": 1,
          "name": "DBS Check",
          "job_role_id": 1
        },
        {
          "id": 2,
          "name": "NVQ Level 2",
          "job_role_id": 1
        }
      ],
      "created_at": "2024-01-01T00:00:00.000Z"
    }
  ]
}
```

---

#### 12. Create Job Role

```
POST /api/v1/job-roles
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "title": "Healthcare Assistant",
  "description": "Provides care and support to patients",
  "documents": [{ "name": "DBS Check" }, { "name": "NVQ Level 2" }]
}
```

**Permissions:** Admin, Compliance

---

#### 13. Update Job Role

```
PUT /api/v1/job-roles/{id}
Authorization: Bearer {token}
```

---

#### 14. Delete Job Role

```
DELETE /api/v1/job-roles/{id}
Authorization: Bearer {token}
```

---

### Candidates Endpoints

#### 15. Get All Candidates

```
GET /api/v1/candidates
Authorization: Bearer {token}
```

**Query Parameters:**

- `status` (optional): Filter by status
- `job_role_id` (optional): Filter by job role
- `search` (optional): Search by name, email, location
- `page` (optional)
- `per_page` (optional)

**Response:**

```json
{
  "success": true,
  "data": {
    "candidates": [
      {
        "id": 1,
        "user_id": 5,
        "job_role_id": 1,
        "first_name": "Alice",
        "last_name": "Johnson",
        "email": "alice@example.com",
        "phone": "+44123456789",
        "dob": "1990-05-15",
        "location": "London",
        "home_location": "Manchester",
        "skills": ["Patient Care", "First Aid"],
        "availability": ["2024-12-01", "2024-12-02"],
        "status": "Active",
        "job_role": {
          "id": 1,
          "title": "Healthcare Assistant"
        },
        "compliance_docs": [
          {
            "id": 1,
            "document_name": "DBS Check",
            "status": "Approved",
            "expiry_date": "2025-12-31",
            "file_url": "https://example.com/dbs.pdf"
          }
        ],
        "created_at": "2024-01-01T00:00:00.000Z"
      }
    ],
    "pagination": {...}
  }
}
```

---

#### 16. Get Single Candidate

```
GET /api/v1/candidates/{id}
Authorization: Bearer {token}
```

---

#### 17. Create Candidate

```
POST /api/v1/candidates
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "first_name": "Alice",
  "last_name": "Johnson",
  "email": "alice@example.com",
  "password": "temporaryPassword123",
  "phone": "+44123456789",
  "dob": "1990-05-15",
  "location": "London",
  "home_location": "Manchester",
  "job_role_id": 1,
  "skills": ["Patient Care", "First Aid"],
  "availability": ["2024-12-01", "2024-12-02"]
}
```

**Backend Logic:**

1. Create a user account with role='worker'
2. Create candidate record linked to user
3. Auto-create pending compliance documents based on job role
4. Send welcome email with credentials

---

#### 18. Update Candidate

```
PUT /api/v1/candidates/{id}
Authorization: Bearer {token}
```

---

#### 19. Delete Candidate

```
DELETE /api/v1/candidates/{id}
Authorization: Bearer {token}
```

---

### Candidate Compliance Endpoints

#### 20. Get Candidate Compliance Documents

```
GET /api/v1/candidates/{candidateId}/compliance
Authorization: Bearer {token}
```

---

#### 21. Upload Compliance Document

```
POST /api/v1/candidates/{candidateId}/compliance/{complianceId}/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body:**

```
file: [binary]
expiry_date: 2025-12-31
```

---

#### 22. Update Compliance Status

```
PUT /api/v1/candidates/{candidateId}/compliance/{complianceId}
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "status": "Approved",
  "expiry_date": "2025-12-31"
}
```

**Permissions:** Compliance, Admin

---

### Clients Endpoints

#### 23. Get All Clients

```
GET /api/v1/clients
Authorization: Bearer {token}
```

---

#### 24. Get Single Client

```
GET /api/v1/clients/{id}
Authorization: Bearer {token}
```

---

#### 25. Create Client

```
POST /api/v1/clients
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "name": "NHS Trust Hospital",
  "address": "123 Healthcare St, London",
  "primary_contact": "John Manager",
  "account_manager_contact": "sarah@example.com",
  "finance_contact": "finance@example.com"
}
```

---

#### 26. Update Client

```
PUT /api/v1/clients/{id}
Authorization: Bearer {token}
```

---

#### 27. Delete Client

```
DELETE /api/v1/clients/{id}
Authorization: Bearer {token}
```

---

### Rate Cards Endpoints

#### 28. Get Client Rate Cards

```
GET /api/v1/clients/{clientId}/rate-cards
Authorization: Bearer {token}
```

**Query Parameters:**

- `job_role_id` (optional): Filter by job role

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "client_id": 1,
      "job_role_id": 1,
      "effective_date": "2024-01-01T00:00:00.000Z",
      "rates": {
        "Day": {
          "pay_rate": 12.5,
          "bill_rate": 18.0
        },
        "Night": {
          "pay_rate": 15.0,
          "bill_rate": 22.0
        },
        "Weekend": {
          "pay_rate": 14.0,
          "bill_rate": 20.0
        },
        "Bank Holiday": {
          "pay_rate": 18.0,
          "bill_rate": 26.0
        }
      },
      "created_at": "2024-01-01T00:00:00.000Z"
    }
  ]
}
```

---

#### 29. Create Rate Card

```
POST /api/v1/clients/{clientId}/rate-cards
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "job_role_id": 1,
  "effective_date": "2024-12-01T00:00:00.000Z",
  "day_pay_rate": 12.5,
  "day_bill_rate": 18.0,
  "night_pay_rate": 15.0,
  "night_bill_rate": 22.0,
  "weekend_pay_rate": 14.0,
  "weekend_bill_rate": 20.0,
  "bank_holiday_pay_rate": 18.0,
  "bank_holiday_bill_rate": 26.0
}
```

---

#### 30. Get Applicable Rate Card

```
GET /api/v1/clients/{clientId}/rate-cards/applicable
Authorization: Bearer {token}
```

**Query Parameters:**

- `job_role_id` (required)
- `date` (required): ISO date string

**Logic:**

- Find the rate card for the client and job role
- Where effective_date <= provided date
- Order by effective_date DESC
- Return the first (most recent) match

---

### Booking Requests Endpoints

#### 31. Get All Bookings

```
GET /api/v1/bookings
Authorization: Bearer {token}
```

**Query Parameters:**

- `status` (optional)
- `client_id` (optional)
- `job_role_id` (optional)
- `start_date` (optional)
- `end_date` (optional)
- `page`, `per_page`

**Response:**

```json
{
  "success": true,
  "data": {
    "bookings": [
      {
        "id": 1,
        "client_id": 1,
        "job_role_id": 1,
        "shift_start_time": "2024-12-01T08:00:00.000Z",
        "shift_end_time": "2024-12-01T16:00:00.000Z",
        "location": "Ward A",
        "candidates_needed": 2,
        "status": "Open",
        "pay_rate": 12.50,
        "bill_rate": 18.00,
        "notes": "Urgent requirement",
        "client": {
          "id": 1,
          "name": "NHS Trust Hospital"
        },
        "job_role": {
          "id": 1,
          "title": "Healthcare Assistant"
        },
        "assignments": [],
        "created_at": "2024-11-01T00:00:00.000Z"
      }
    ],
    "pagination": {...}
  }
}
```

---

#### 32. Get Single Booking

```
GET /api/v1/bookings/{id}
Authorization: Bearer {token}
```

---

#### 33. Create Booking

```
POST /api/v1/bookings
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "client_id": 1,
  "job_role_id": 1,
  "shift_start_time": "2024-12-01T08:00:00.000Z",
  "shift_end_time": "2024-12-01T16:00:00.000Z",
  "location": "Ward A",
  "candidates_needed": 2,
  "notes": "Urgent requirement",
  "candidate_id": null
}
```

**Backend Logic:**

1. Fetch applicable rate card for client, job role, and shift date
2. Calculate work type (Day/Night/Weekend/Bank Holiday)
3. Set pay_rate and bill_rate from rate card
4. If candidate_id provided:
   - Check candidate availability
   - Create assignment
   - Set booking status to 'Booked'
   - Auto-create timesheet (status: Draft)
5. If no candidate_id:
   - Set booking status to 'Open'

---

#### 34. Update Booking

```
PUT /api/v1/bookings/{id}
Authorization: Bearer {token}
```

---

#### 35. Cancel Booking

```
POST /api/v1/bookings/{id}/cancel
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "cancellation_reason": "Client request"
}
```

---

### Assignments Endpoints

#### 36. Get All Assignments

```
GET /api/v1/assignments
Authorization: Bearer {token}
```

---

#### 37. Create Assignment

```
POST /api/v1/assignments
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "booking_request_id": 1,
  "candidate_id": 5
}
```

**Backend Logic:**

1. Validate candidate availability
2. Check candidate compliance status
3. Create assignment
4. Update booking status to 'Booked'
5. Auto-create timesheet (status: Draft)
6. Send notification to candidate

---

#### 38. Update Assignment Status

```
PUT /api/v1/assignments/{id}
Authorization: Bearer {token}
```

---

#### 39. Delete Assignment

```
DELETE /api/v1/assignments/{id}
Authorization: Bearer {token}
```

---

### Timesheets Endpoints

#### 40. Get All Timesheets

```
GET /api/v1/timesheets
Authorization: Bearer {token}
```

**Query Parameters:**

- `status` (optional)
- `candidate_id` (optional)
- `client_id` (optional)
- `start_date`, `end_date` (optional)

**Response:**

```json
{
  "success": true,
  "data": {
    "timesheets": [
      {
        "id": 1,
        "timesheet_number": "TS-00001",
        "assignment_id": 1,
        "hours_standard": 8.0,
        "hours_overtime": 2.0,
        "breaks": 0.5,
        "expenses": "Travel: £20",
        "status": "Submitted",
        "approved_by": null,
        "approved_at": null,
        "assignment": {
          "id": 1,
          "booking_request": {
            "id": 1,
            "shift_start_time": "2024-12-01T08:00:00.000Z",
            "client": {"id": 1, "name": "NHS Trust"}
          },
          "candidate": {
            "id": 5,
            "first_name": "Alice",
            "last_name": "Johnson"
          }
        },
        "created_at": "2024-12-01T16:00:00.000Z"
      }
    ],
    "pagination": {...}
  }
}
```

---

#### 41. Get Single Timesheet

```
GET /api/v1/timesheets/{id}
Authorization: Bearer {token}
```

---

#### 42. Update Timesheet

```
PUT /api/v1/timesheets/{id}
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "hours_standard": 8.0,
  "hours_overtime": 2.0,
  "breaks": 0.5,
  "expenses": "Travel: £20"
}
```

**Permissions:**

- Worker (own timesheets, status: Draft)
- Finance, Admin (all timesheets)

---

#### 43. Submit Timesheet

```
POST /api/v1/timesheets/{id}/submit
Authorization: Bearer {token}
```

**Logic:**

- Change status from 'Draft' to 'Submitted'
- Send notification to Finance team

---

#### 44. Approve Timesheet

```
POST /api/v1/timesheets/{id}/approve
Authorization: Bearer {token}
```

**Logic:**

- Change status to 'Approved'
- Set approved_by and approved_at
- Send notification to worker
  **Permissions:** Finance, Admin

---

#### 45. Reject Timesheet

```
POST /api/v1/timesheets/{id}/reject
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "rejection_reason": "Hours don't match shift time"
}
```

**Permissions:** Finance, Admin

---

### Interviews Endpoints

#### 46. Get All Interviews

```
GET /api/v1/interviews
Authorization: Bearer {token}
```

---

#### 47. Create Interview

```
POST /api/v1/interviews
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "candidate_id": 5,
  "client_id": 1,
  "interview_time": "2024-12-05T10:00:00.000Z",
  "interview_type": "Video Call",
  "notes": "Technical assessment required"
}
```

---

#### 48. Update Interview

```
PUT /api/v1/interviews/{id}
Authorization: Bearer {token}
```

---

#### 49. Delete Interview

```
DELETE /api/v1/interviews/{id}
Authorization: Bearer {token}
```

---

### Invoices Endpoints

#### 50. Get All Invoices

```
GET /api/v1/invoices
Authorization: Bearer {token}
```

**Query Parameters:**

- `status` (optional)
- `client_id` (optional)
- `start_date`, `end_date` (optional)

---

#### 51. Get Single Invoice

```
GET /api/v1/invoices/{id}
Authorization: Bearer {token}
```

---

#### 52. Create Invoice

```
POST /api/v1/invoices
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "client_id": 1,
  "period_start": "2024-11-01",
  "period_end": "2024-11-30",
  "notes": "Monthly invoice",
  "line_items": [
    {
      "description": "Healthcare Assistant - 40 hours",
      "quantity": 40,
      "unit_price": 18.0,
      "total": 720.0
    }
  ],
  "tax_rate": 20
}
```

**Backend Logic:**

1. Calculate subtotal from line items
2. Calculate tax
3. Calculate total
4. Generate unique invoice_number (e.g., INV-2024-001)

---

#### 53. Update Invoice

```
PUT /api/v1/invoices/{id}
Authorization: Bearer {token}
```

---

#### 54. Update Invoice Status

```
POST /api/v1/invoices/{id}/status
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "status": "Sent"
}
```

---

#### 55. Generate Invoice PDF

```
GET /api/v1/invoices/{id}/pdf
Authorization: Bearer {token}
```

**Response:** PDF file download

---

### Notifications Endpoints

#### 56. Get User Notifications

```
GET /api/v1/notifications
Authorization: Bearer {token}
```

**Query Parameters:**

- `is_read` (optional): true/false
- `page`, `per_page`

---

#### 57. Mark Notification as Read

```
PUT /api/v1/notifications/{id}/read
Authorization: Bearer {token}
```

---

#### 58. Mark All Notifications as Read

```
POST /api/v1/notifications/read-all
Authorization: Bearer {token}
```

---

### Audit Logs Endpoints

#### 59. Get Audit Logs

```
GET /api/v1/audit-logs
Authorization: Bearer {token}
```

**Query Parameters:**

- `user_id` (optional)
- `action` (optional)
- `entity` (optional)
- `start_date`, `end_date` (optional)
- `page`, `per_page`

**Permissions:** Admin, Super Admin

---

### Company Profile Endpoints

#### 60. Get Company Profile

```
GET /api/v1/company-profile
Authorization: Bearer {token}
```

---

#### 61. Update Company Profile

```
PUT /api/v1/company-profile
Authorization: Bearer {token}
```

**Request Body:**

```json
{
  "name": "StaffFlow Agency Ltd",
  "address": "123 Business St, London",
  "phone": "+442012345678",
  "email": "info@staffflow.com",
  "website": "https://staffflow.com",
  "logo_url": "https://example.com/logo.png",
  "bank_name": "Barclays",
  "account_number": "12345678",
  "sort_code": "20-00-00"
}
```

**Permissions:** Admin, Super Admin

---

### Reports Endpoints

#### 62. Generate Booking Report

```
GET /api/v1/reports/bookings
Authorization: Bearer {token}
```

**Query Parameters:**

- `start_date` (required)
- `end_date` (required)
- `client_id` (optional)
- `format` (optional): 'json' or 'pdf'

---

#### 63. Generate Financial Report

```
GET /api/v1/reports/financial
Authorization: Bearer {token}
```

---

#### 64. Generate Compliance Report

```
GET /api/v1/reports/compliance
Authorization: Bearer {token}
```

---

### File Upload Endpoints

#### 65. Upload File

```
POST /api/v1/files/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body:**

```
file: [binary]
type: 'compliance' | 'avatar' | 'logo'
```

**Response:**

```json
{
  "success": true,
  "data": {
    "url": "https://storage.example.com/files/abc123.pdf",
    "filename": "dbs-certificate.pdf",
    "size": 245678,
    "mime_type": "application/pdf"
  }
}
```

---

## Admin Panel Requirements

### Overview

The Admin Panel is a **separate interface** from the main application, accessible only to Super Admin users.

### URL Structure

- Admin Panel: `/admin`
- Admin Login: `/admin/login`
- Main App: `/` (separate from admin)

### Admin Panel Features

#### 1. Admin Login Page

- Route: `/admin/login`
- Only accepts users with `role='superadmin'`
- Separate authentication session from main app
- Redirect to `/admin/dashboard` on success

#### 2. Admin Dashboard (`/admin/dashboard`)

- System statistics
- User overview
- Recent activity logs
- Quick actions

#### 3. User Management (`/admin/users`)

- List all users (paginated table)
- Filter by role
- Create new users with any role (including 'admin')
- Edit user details
- Activate/Deactivate users
- Delete users
- Reset user passwords

#### 4. Admin User Creation Form

```
Fields:
- Name (required)
- Email (required)
- Password (required, min 8 characters)
- Role (dropdown: admin, recruiter, finance, compliance, worker)
- Avatar URL (optional)
- Is Active (checkbox, default: true)

Validations:
- Email must be unique
- Password must meet security requirements
- Send welcome email with credentials
```

#### 5. System Settings (`/admin/settings`)

- Company profile configuration
- Email settings
- System configurations
- Backup management

#### 6. Audit Logs Viewer (`/admin/audit-logs`)

- Comprehensive log viewer
- Advanced filtering
- Export capabilities

### Admin Panel API Endpoints

#### Admin Authentication

```
POST /admin/api/login
```

**Request Body:**

```json
{
  "email": "superadmin@example.com",
  "password": "securePassword"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "token": "admin-jwt-token",
    "user": {
      "id": 1,
      "name": "Super Admin",
      "email": "superadmin@example.com",
      "role": "superadmin"
    }
  }
}
```

#### Admin User Management

```
GET /admin/api/users
POST /admin/api/users
PUT /admin/api/users/{id}
DELETE /admin/api/users/{id}
```

---

## Implementation Steps

### Phase 1: Project Setup (Week 1)

1. **Laravel Installation**

   ```bash
   composer create-project laravel/laravel staffflow-backend
   cd staffflow-backend
   ```

2. **Install Dependencies**

   ```bash
   composer require laravel/sanctum
   composer require intervention/image
   composer require barryvdh/laravel-dompdf
   ```

3. **Environment Configuration**

   - Configure `.env` file
   - Set up database connection
   - Configure mail settings
   - Set up storage (S3 or local)

4. **Database Migration Setup**
   - Create all migration files
   - Run migrations
   - Create seeders for initial data

### Phase 2: Authentication & Authorization (Week 1-2)

1. **Sanctum Setup**

   - Publish Sanctum configuration
   - Add middleware
   - Configure CORS

2. **Authentication Implementation**

   - User registration/login
   - Password reset functionality
   - JWT token management
   - Role-based middleware

3. **Admin Panel Authentication**
   - Separate admin guard
   - Admin-specific middleware
   - Super admin verification

### Phase 3: Core Models & Controllers (Week 2-3)

1. **Create Models**

   - User, Candidate, Client
   - JobRole, ComplianceDocument
   - BookingRequest, Assignment
   - Timesheet, Invoice
   - Notification, AuditLog

2. **Create Controllers**

   - AuthController
   - UserController
   - CandidateController
   - ClientController
   - BookingController
   - TimesheetController
   - InvoiceController

3. **Define Relationships**
   - Eloquent relationships
   - Eager loading strategies

### Phase 4: Business Logic Implementation (Week 3-4)

1. **Rate Card System**

   - Calculate applicable rates
   - Work type determination
   - Version management

2. **Booking & Assignment Workflow**

   - Availability checking
   - Compliance validation
   - Auto-timesheet creation
   - Notification triggers

3. **Timesheet Management**

   - Draft creation
   - Submission workflow
   - Approval process
   - Lock mechanism

4. **Invoice Generation**
   - Line item calculation
   - Tax calculation
   - PDF generation
   - Number generation

### Phase 5: File Management (Week 4)

1. **Storage Configuration**

   - Local/S3 setup
   - Public/private buckets
   - File validation

2. **Upload Endpoints**
   - Compliance documents
   - Avatar images
   - Company logos

### Phase 6: Notifications & Audit Logs (Week 4-5)

1. **Notification System**

   - In-app notifications
   - Email notifications
   - Event listeners

2. **Audit Logging**
   - Automatic logging middleware
   - Event-based logging
   - Log viewer

### Phase 7: Admin Panel (Week 5-6)

1. **Admin Routes & Middleware**

   - Separate route group
   - Admin guard
   - Permission checks

2. **Admin UI (Laravel Blade or separate frontend)**
   - Dashboard
   - User management
   - System settings
   - Audit log viewer

### Phase 8: API Documentation & Testing (Week 6-7)

1. **API Documentation**

   - Generate Postman collection
   - Create Swagger/OpenAPI docs
   - Write integration guide

2. **Testing**

   - Unit tests
   - Feature tests
   - API endpoint tests
   - Role-based access tests

3. **Frontend Integration Guide**
   - Update frontend to use new APIs
   - Migration strategy from Firebase
   - Environment configuration

### Phase 9: Deployment & DevOps (Week 7-8)

1. **Server Setup**

   - Production server configuration
   - SSL certificate
   - Database optimization

2. **Deployment**

   - CI/CD pipeline
   - Automated testing
   - Deployment scripts

3. **Monitoring**
   - Error tracking (Sentry)
   - Performance monitoring
   - Log management

---

## API Response Standard

### Success Response

```json
{
  "success": true,
  "data": {
    // Response data
  },
  "message": "Optional success message"
}
```

### Error Response

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "details": {
      // Additional error details
    }
  }
}
```

### Validation Error Response

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid",
    "details": {
      "email": ["The email field is required"],
      "password": ["The password must be at least 8 characters"]
    }
  }
}
```

---

## Security Considerations

1. **Input Validation**

   - Use Laravel's validation rules
   - Sanitize all inputs
   - Validate file uploads

2. **SQL Injection Prevention**

   - Use Eloquent ORM
   - Parameterized queries

3. **XSS Prevention**

   - Escape output
   - Content Security Policy headers

4. **CSRF Protection**

   - Sanctum CSRF tokens
   - Verify on state-changing operations

5. **Rate Limiting**

   - API rate limiting
   - Login attempt throttling

6. **Data Encryption**

   - Encrypt sensitive data at rest
   - Use HTTPS for all communications

7. **Access Control**
   - Role-based permissions
   - Resource ownership verification
   - Audit trail for sensitive operations

---

## Performance Optimization

1. **Database Optimization**

   - Proper indexing
   - Query optimization
   - Eager loading relationships

2. **Caching Strategy**

   - Redis for session storage
   - Cache frequently accessed data
   - Query result caching

3. **API Optimization**

   - Pagination for list endpoints
   - Response compression
   - API versioning

4. **File Storage**
   - Use CDN for static assets
   - Optimize image uploads
   - Implement lazy loading

---

## Migration Strategy from Firebase

### Step 1: Data Export from Firebase

1. Export all collections to JSON
2. Transform data structure to match Laravel schema
3. Handle Firebase-specific data types (Timestamp, GeoPoint)

### Step 2: Data Import Script

Create Laravel command to import data:

```bash
php artisan import:firebase-data {collection} {file}
```

### Step 3: Frontend Migration

1. Create API service layer in frontend
2. Replace Firebase calls with API calls
3. Update authentication flow
4. Test incrementally

### Step 4: Parallel Running

- Run both systems in parallel
- Sync data bidirectionally (temporary)
- Monitor for issues

### Step 5: Complete Migration

- Switch frontend to Laravel backend only
- Decommission Firebase
- Update DNS/environment variables

---

## Conclusion

This roadmap provides a complete blueprint for building a Laravel backend for your StaffFlow application. The implementation includes:

✅ Comprehensive database schema  
✅ RESTful API endpoints  
✅ Role-based authentication & authorization  
✅ Admin panel for Super Admin  
✅ Business logic for rate cards, bookings, timesheets, invoices  
✅ File upload management  
✅ Notification system  
✅ Audit logging  
✅ Security best practices  
✅ Performance optimization  
✅ Migration strategy from Firebase

**Estimated Timeline:** 7-8 weeks for full implementation

**Next Steps:**

1. Review this document with your team
2. Set up development environment
3. Start with Phase 1 (Project Setup)
4. Follow the phases sequentially
5. Test thoroughly at each phase
6. Deploy to production

For any questions or clarifications, refer to the specific sections in this document.
