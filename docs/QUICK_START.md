# StaffFlow API - Quick Start Guide

## ✅ What's Been Implemented

### 1. **Filament Admin Panel** 
- **URL:** `http://localhost:8000/admin`
- **Access:** Superadmin only (role='superadmin')
- **Login:** superadmin@staffflow.com / password123
- **Features:**
  - User management
  - Candidate management
  - Client management
  - Booking management
  - Audit log viewing

### 2. **RESTful API (v1)**
- **Base URL:** `http://localhost:8000/api/v1`
- **Authentication:** Laravel Sanctum (Bearer tokens)
- **Total Endpoints:** 50+

## 🚀 Quick Test

### 1. Start the Server
```bash
php artisan serve
```

### 2. Test Login API
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@staffflow.com",
    "password": "password123"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 2,
      "uid": "...",
      "name": "Admin User",
      "email": "admin@staffflow.com",
      "role": "admin",
      "is_active": true
    },
    "token": "1|abcdef123456...",
    "token_type": "Bearer"
  }
}
```

### 3. Test Protected Endpoint
```bash
# Use the token from login response
export TOKEN="1|abcdef123456..."

curl -X GET http://localhost:8000/api/v1/users \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

## 📋 Available Test Users

| Role | Email | Password | Access |
|------|-------|----------|--------|
| Superadmin | superadmin@staffflow.com | password123 | Admin panel only |
| Admin | admin@staffflow.com | password123 | Full API access |
| Recruiter | recruiter@staffflow.com | password123 | Candidates, Clients, Bookings |
| Finance | finance@staffflow.com | password123 | Timesheets, Invoices |
| Compliance | compliance@staffflow.com | password123 | Compliance documents |
| Worker | worker1@staffflow.com | password123 | Own profile, timesheets |

## 📚 API Endpoints

### Authentication
```bash
# Login
POST /api/v1/auth/login
Body: { "email": "...", "password": "..." }

# Logout
POST /api/v1/auth/logout
Headers: Authorization: Bearer {token}

# Get Current User
GET /api/v1/auth/me
Headers: Authorization: Bearer {token}

# Refresh Token
POST /api/v1/auth/refresh
Headers: Authorization: Bearer {token}
```

### Users (Admin only)
```bash
# List users
GET /api/v1/users?page=1&per_page=15&role=admin

# Create user
POST /api/v1/users
Body: {
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "role": "admin",
  "phone": "+44 123 456 7890",
  "is_active": true
}

# Get user
GET /api/v1/users/{id}

# Update user
PUT /api/v1/users/{id}

# Delete user
DELETE /api/v1/users/{id}
```

### Candidates
```bash
# List candidates
GET /api/v1/candidates?status=Active&job_role_id=1&search=john

# Create candidate
POST /api/v1/candidates
Body: {
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "+44 123 456 7890",
  "job_role_id": 1,
  "ni_number": "AB123456C",
  "dob": "1990-01-01",
  "address_line1": "123 Main St",
  "city": "London",
  "county": "Greater London",
  "postcode": "SW1A 1AA",
  "emergency_contact_name": "Jane Doe",
  "emergency_contact_phone": "+44 987 654 3210"
}

# Get candidate compliance documents
GET /api/v1/candidates/{id}/compliance

# Upload compliance document
POST /api/v1/candidates/{candidateId}/compliance/{complianceId}/upload
Headers: Content-Type: multipart/form-data
Body: {
  "file": [binary],
  "expiry_date": "2025-12-31"
}

# Update compliance status
PUT /api/v1/candidates/{candidateId}/compliance/{complianceId}
Body: {
  "status": "Approved",
  "expiry_date": "2025-12-31"
}
```

### Clients
```bash
# List clients
GET /api/v1/clients?is_active=true

# Create client
POST /api/v1/clients
Body: {
  "name": "NHS Trust Hospital",
  "contact_name": "Sarah Johnson",
  "contact_email": "sarah@hospital.nhs.uk",
  "contact_phone": "+44 20 7946 0958",
  "address": "123 Hospital Road, London, SW1A 1AA",
  "invoice_email": "invoices@hospital.nhs.uk",
  "is_active": true
}

# Get client rate cards
GET /api/v1/clients/{id}/rate-cards

# Create rate card
POST /api/v1/clients/{id}/rate-cards
Body: {
  "job_role_id": 1,
  "client_day_rate": 25.00,
  "client_night_rate": 30.00,
  "client_weekend_rate": 35.00,
  "client_bank_holiday_rate": 40.00,
  "worker_day_rate": 15.00,
  "worker_night_rate": 18.00,
  "worker_weekend_rate": 20.00,
  "worker_bank_holiday_rate": 25.00,
  "effective_from": "2024-01-01"
}

# Get applicable rate
GET /api/v1/clients/{id}/rate-cards/applicable?job_role_id=1&date=2024-12-01
```

### Bookings
```bash
# List bookings
GET /api/v1/bookings?status=Open&client_id=1&start_date=2024-12-01&end_date=2024-12-31

# Create booking
POST /api/v1/bookings
Body: {
  "client_id": 1,
  "job_role_id": 1,
  "shift_start_time": "2024-12-01T08:00:00Z",
  "shift_end_time": "2024-12-01T16:00:00Z",
  "location": "London Hospital",
  "candidates_needed": 2,
  "requirements": "Must have current DBS",
  "notes": "Emergency cover needed"
}

# Cancel booking
POST /api/v1/bookings/{id}/cancel
```

### Job Roles
```bash
# List job roles
GET /api/v1/job-roles

# Create job role
POST /api/v1/job-roles
Body: {
  "title": "Healthcare Assistant",
  "description": "Providing care to patients",
  "is_active": true
}
```

## 🔐 Authorization Headers

All protected endpoints require:
```
Authorization: Bearer {your-token-here}
Accept: application/json
Content-Type: application/json
```

## 🎯 Common Query Parameters

### Pagination (all list endpoints)
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15)

### Filters
- Users: `role`, `is_active`, `search`
- Candidates: `status`, `job_role_id`, `search`
- Clients: `is_active`, `search`
- Bookings: `status`, `client_id`, `job_role_id`, `start_date`, `end_date`

## 🛠️ Development Commands

```bash
# Run migrations
php artisan migrate:fresh --seed

# Create admin user for Filament
php artisan tinker
> $user = \App\Models\User::where('email', 'superadmin@staffflow.com')->first();
> echo $user->name;

# List all routes
php artisan route:list --path=api/v1

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Generate API token (manual)
php artisan tinker
> $user = \App\Models\User::find(2); // Admin user
> $token = $user->createToken('api-token')->plainTextToken;
> echo $token;
```

## 📊 Response Format

### Success
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Paginated
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

### Error
```json
{
  "success": false,
  "message": "Error description",
  "errors": { ... }
}
```

## 🔍 Testing Workflow

### 1. Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@staffflow.com","password":"password123"}'
```

### 2. Save Token
```bash
export TOKEN="your-token-from-login-response"
```

### 3. Test Endpoints
```bash
# Get current user
curl http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer $TOKEN"

# List users
curl http://localhost:8000/api/v1/users \
  -H "Authorization: Bearer $TOKEN"

# List candidates
curl http://localhost:8000/api/v1/candidates \
  -H "Authorization: Bearer $TOKEN"

# List clients
curl http://localhost:8000/api/v1/clients \
  -H "Authorization: Bearer $TOKEN"

# List bookings
curl http://localhost:8000/api/v1/bookings \
  -H "Authorization: Bearer $TOKEN"
```

## 🎉 Next Steps

1. **Access Admin Panel**: Visit `http://localhost:8000/admin` and login with superadmin
2. **Test APIs**: Use Postman or cURL to test endpoints
3. **Integrate Frontend**: Update your Next.js app to use these endpoints
4. **Add Missing Features**: Implement Timesheet, Invoice, and Notification controllers
5. **Deploy**: Push to staging/production environment

## 📖 Full Documentation

See `docs/IMPLEMENTATION_SUMMARY.md` for complete details on:
- All implemented features
- Controller details
- Authorization logic
- File structure
- Security features
- Development roadmap

---

**Status:** ✅ Ready for frontend integration
**Version:** 1.0
**Date:** November 6, 2025
