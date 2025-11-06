# API Endpoints Quick Reference

## Authentication

| Method | Endpoint                              | Description            | Auth Required |
| ------ | ------------------------------------- | ---------------------- | ------------- |
| POST   | `/api/v1/auth/login`                  | Login user             | No            |
| POST   | `/api/v1/auth/logout`                 | Logout user            | Yes           |
| POST   | `/api/v1/auth/refresh`                | Refresh token          | Yes           |
| GET    | `/api/v1/auth/me`                     | Get current user       | Yes           |
| POST   | `/api/v1/auth/password/reset-request` | Request password reset | No            |
| POST   | `/api/v1/auth/password/reset`         | Reset password         | No            |

## Users

| Method | Endpoint             | Description     | Permission |
| ------ | -------------------- | --------------- | ---------- |
| GET    | `/api/v1/users`      | List all users  | Admin      |
| GET    | `/api/v1/users/{id}` | Get single user | Admin      |
| POST   | `/api/v1/users`      | Create user     | Admin      |
| PUT    | `/api/v1/users/{id}` | Update user     | Admin      |
| DELETE | `/api/v1/users/{id}` | Delete user     | Admin      |

## Job Roles

| Method | Endpoint                 | Description         | Permission        |
| ------ | ------------------------ | ------------------- | ----------------- |
| GET    | `/api/v1/job-roles`      | List all job roles  | All               |
| GET    | `/api/v1/job-roles/{id}` | Get single job role | All               |
| POST   | `/api/v1/job-roles`      | Create job role     | Admin, Compliance |
| PUT    | `/api/v1/job-roles/{id}` | Update job role     | Admin, Compliance |
| DELETE | `/api/v1/job-roles/{id}` | Delete job role     | Admin             |

## Candidates

| Method | Endpoint                                                   | Description          | Permission             |
| ------ | ---------------------------------------------------------- | -------------------- | ---------------------- |
| GET    | `/api/v1/candidates`                                       | List candidates      | All (filtered by role) |
| GET    | `/api/v1/candidates/{id}`                                  | Get single candidate | All                    |
| POST   | `/api/v1/candidates`                                       | Create candidate     | Admin, Recruiter       |
| PUT    | `/api/v1/candidates/{id}`                                  | Update candidate     | Admin, Recruiter       |
| DELETE | `/api/v1/candidates/{id}`                                  | Delete candidate     | Admin                  |
| GET    | `/api/v1/candidates/{id}/compliance`                       | Get compliance docs  | All                    |
| POST   | `/api/v1/candidates/{id}/compliance/{complianceId}/upload` | Upload document      | Admin, Compliance      |
| PUT    | `/api/v1/candidates/{id}/compliance/{complianceId}`        | Update compliance    | Admin, Compliance      |

## Clients

| Method | Endpoint                                     | Description         | Permission                |
| ------ | -------------------------------------------- | ------------------- | ------------------------- |
| GET    | `/api/v1/clients`                            | List clients        | All                       |
| GET    | `/api/v1/clients/{id}`                       | Get single client   | All                       |
| POST   | `/api/v1/clients`                            | Create client       | Admin, Recruiter          |
| PUT    | `/api/v1/clients/{id}`                       | Update client       | Admin, Recruiter          |
| DELETE | `/api/v1/clients/{id}`                       | Delete client       | Admin                     |
| GET    | `/api/v1/clients/{id}/rate-cards`            | Get rate cards      | Admin, Finance, Recruiter |
| POST   | `/api/v1/clients/{id}/rate-cards`            | Create rate card    | Admin, Finance            |
| GET    | `/api/v1/clients/{id}/rate-cards/applicable` | Get applicable rate | All                       |

## Bookings

| Method | Endpoint                       | Description        | Permission             |
| ------ | ------------------------------ | ------------------ | ---------------------- |
| GET    | `/api/v1/bookings`             | List bookings      | All (filtered by role) |
| GET    | `/api/v1/bookings/{id}`        | Get single booking | All                    |
| POST   | `/api/v1/bookings`             | Create booking     | Admin, Recruiter       |
| PUT    | `/api/v1/bookings/{id}`        | Update booking     | Admin, Recruiter       |
| POST   | `/api/v1/bookings/{id}/cancel` | Cancel booking     | Admin, Recruiter       |

## Assignments

| Method | Endpoint                   | Description           | Permission       |
| ------ | -------------------------- | --------------------- | ---------------- |
| GET    | `/api/v1/assignments`      | List assignments      | All              |
| GET    | `/api/v1/assignments/{id}` | Get single assignment | All              |
| POST   | `/api/v1/assignments`      | Create assignment     | Admin, Recruiter |
| PUT    | `/api/v1/assignments/{id}` | Update assignment     | Admin, Recruiter |
| DELETE | `/api/v1/assignments/{id}` | Delete assignment     | Admin            |

## Timesheets

| Method | Endpoint                          | Description          | Permission                   |
| ------ | --------------------------------- | -------------------- | ---------------------------- |
| GET    | `/api/v1/timesheets`              | List timesheets      | All (filtered by role)       |
| GET    | `/api/v1/timesheets/{id}`         | Get single timesheet | All                          |
| PUT    | `/api/v1/timesheets/{id}`         | Update timesheet     | Worker (own), Admin, Finance |
| POST   | `/api/v1/timesheets/{id}/submit`  | Submit timesheet     | Worker (own)                 |
| POST   | `/api/v1/timesheets/{id}/approve` | Approve timesheet    | Admin, Finance               |
| POST   | `/api/v1/timesheets/{id}/reject`  | Reject timesheet     | Admin, Finance               |

## Interviews

| Method | Endpoint                  | Description          | Permission       |
| ------ | ------------------------- | -------------------- | ---------------- |
| GET    | `/api/v1/interviews`      | List interviews      | All              |
| GET    | `/api/v1/interviews/{id}` | Get single interview | All              |
| POST   | `/api/v1/interviews`      | Create interview     | Admin, Recruiter |
| PUT    | `/api/v1/interviews/{id}` | Update interview     | Admin, Recruiter |
| DELETE | `/api/v1/interviews/{id}` | Delete interview     | Admin            |

## Invoices

| Method | Endpoint                       | Description        | Permission     |
| ------ | ------------------------------ | ------------------ | -------------- |
| GET    | `/api/v1/invoices`             | List invoices      | Admin, Finance |
| GET    | `/api/v1/invoices/{id}`        | Get single invoice | Admin, Finance |
| POST   | `/api/v1/invoices`             | Create invoice     | Admin, Finance |
| PUT    | `/api/v1/invoices/{id}`        | Update invoice     | Admin, Finance |
| POST   | `/api/v1/invoices/{id}/status` | Update status      | Admin, Finance |
| GET    | `/api/v1/invoices/{id}/pdf`    | Download PDF       | Admin, Finance |

## Notifications

| Method | Endpoint                          | Description            | Permission |
| ------ | --------------------------------- | ---------------------- | ---------- |
| GET    | `/api/v1/notifications`           | Get user notifications | Own user   |
| PUT    | `/api/v1/notifications/{id}/read` | Mark as read           | Own user   |
| POST   | `/api/v1/notifications/read-all`  | Mark all as read       | Own user   |

## Audit Logs

| Method | Endpoint             | Description     | Permission |
| ------ | -------------------- | --------------- | ---------- |
| GET    | `/api/v1/audit-logs` | List audit logs | Admin      |

## Company Profile

| Method | Endpoint                  | Description         | Permission |
| ------ | ------------------------- | ------------------- | ---------- |
| GET    | `/api/v1/company-profile` | Get company profile | All        |
| PUT    | `/api/v1/company-profile` | Update profile      | Admin      |

## Reports

| Method | Endpoint                     | Description       | Permission        |
| ------ | ---------------------------- | ----------------- | ----------------- |
| GET    | `/api/v1/reports/bookings`   | Booking report    | Admin, Recruiter  |
| GET    | `/api/v1/reports/financial`  | Financial report  | Admin, Finance    |
| GET    | `/api/v1/reports/compliance` | Compliance report | Admin, Compliance |

## File Uploads

| Method | Endpoint               | Description | Permission |
| ------ | ---------------------- | ----------- | ---------- |
| POST   | `/api/v1/files/upload` | Upload file | All        |

## Admin Panel

| Method | Endpoint                | Description            | Permission       |
| ------ | ----------------------- | ---------------------- | ---------------- |
| POST   | `/admin/api/login`      | Admin login            | Super Admin only |
| GET    | `/admin/api/users`      | List all users         | Super Admin      |
| POST   | `/admin/api/users`      | Create user (any role) | Super Admin      |
| PUT    | `/admin/api/users/{id}` | Update user            | Super Admin      |
| DELETE | `/admin/api/users/{id}` | Delete user            | Super Admin      |

---

## Query Parameters Reference

### Pagination (All List Endpoints)

```
?page=1&per_page=15
```

### Candidates

```
?status=Active
&job_role_id=1
&search=john
&page=1
&per_page=15
```

### Bookings

```
?status=Open
&client_id=1
&job_role_id=1
&start_date=2024-12-01
&end_date=2024-12-31
&page=1
```

### Timesheets

```
?status=Submitted
&candidate_id=5
&client_id=1
&start_date=2024-12-01
&end_date=2024-12-31
```

### Invoices

```
?status=Paid
&client_id=1
&start_date=2024-12-01
&end_date=2024-12-31
```

### Rate Cards

```
?job_role_id=1
```

### Applicable Rate Card

```
?job_role_id=1&date=2024-12-01T08:00:00.000Z
```

---

## Common Response Structures

### Success Response

```json
{
  "success": true,
  "data": {
    /* response data */
  }
}
```

### Paginated Response

```json
{
  "success": true,
  "data": {
    "data": [
      /* array of items */
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

### Error Response

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Error message",
    "details": {
      /* optional error details */
    }
  }
}
```

### Validation Error

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

## Authentication Header

All authenticated requests must include:

```
Authorization: Bearer {token}
```

---

## HTTP Status Codes

| Code | Meaning                              |
| ---- | ------------------------------------ |
| 200  | Success                              |
| 201  | Created                              |
| 204  | No Content (successful deletion)     |
| 400  | Bad Request                          |
| 401  | Unauthorized (invalid/expired token) |
| 403  | Forbidden (insufficient permissions) |
| 404  | Not Found                            |
| 422  | Validation Error                     |
| 500  | Server Error                         |

---

## Rate Limiting

- **Default:** 60 requests per minute per user
- **Authentication endpoints:** 5 requests per minute per IP

Rate limit headers:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1638360000
```

---

## File Upload Limits

- **Max file size:** 10MB
- **Allowed types:** PDF, JPG, PNG, DOCX
- **Compliance documents:** PDF preferred

---

## Date Format

All dates should be in ISO 8601 format:

```
2024-12-01T08:00:00.000Z
```

---

## Work Types

Available work types for rate cards:

- `Day` - Monday-Friday, 6 AM - 6 PM
- `Night` - Monday-Friday, 6 PM - 6 AM
- `Weekend` - Saturday-Sunday, any time
- `Bank Holiday` - UK bank holidays

---

## User Roles

| Role        | Code         |
| ----------- | ------------ |
| Super Admin | `superadmin` |
| Admin       | `admin`      |
| Recruiter   | `recruiter`  |
| Finance     | `finance`    |
| Compliance  | `compliance` |
| Worker      | `worker`     |

---

## Status Enums

### Candidate Status

- `New` - Just created
- `Screened` - Initial screening done
- `Interviewed` - Interview completed
- `Compliant` - All documents approved
- `Active` - Available for bookings
- `Inactive` - Not available

### Booking Status

- `Open` - Available for assignment
- `Booked` - Candidates assigned
- `Completed` - Shift completed
- `Cancelled` - Cancelled

### Timesheet Status

- `Draft` - Not yet submitted
- `Submitted` - Awaiting approval
- `Approved` - Approved by finance
- `Rejected` - Rejected
- `Locked` - Cannot be edited

### Invoice Status

- `Draft` - Being created
- `Sent` - Sent to client
- `Part-Paid` - Partially paid
- `Paid` - Fully paid
- `Overdue` - Payment overdue
- `Cancelled` - Cancelled

### Compliance Status

- `Pending` - Not yet submitted
- `Submitted` - Awaiting verification
- `Approved` - Verified and approved
- `Rejected` - Rejected
- `Expired` - Past expiry date

### Interview Outcome

- `Scheduled` - Scheduled, not done
- `Completed` - Interview completed
- `Offer` - Offer extended
- `Reject` - Candidate rejected

### Assignment Status

- `Confirmed` - Assignment confirmed
- `Cancelled` - Assignment cancelled
- `Completed` - Shift completed

---

## Useful cURL Examples

### Login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123"}'
```

### Get Candidates

```bash
curl -X GET http://localhost:8000/api/v1/candidates \
  -H "Authorization: Bearer {token}"
```

### Create Booking

```bash
curl -X POST http://localhost:8000/api/v1/bookings \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": 1,
    "job_role_id": 1,
    "shift_start_time": "2024-12-01T08:00:00.000Z",
    "shift_end_time": "2024-12-01T16:00:00.000Z",
    "location": "Ward A",
    "candidates_needed": 2
  }'
```

### Upload Compliance Document

```bash
curl -X POST http://localhost:8000/api/v1/candidates/1/compliance/1/upload \
  -H "Authorization: Bearer {token}" \
  -F "file=@/path/to/document.pdf" \
  -F "expiry_date=2025-12-31"
```

---

## Environment-Specific URLs

### Development

```
Base URL: http://localhost:8000/api/v1
Admin URL: http://localhost:8000/admin/api
```

### Staging

```
Base URL: https://staging-api.staffflow.com/api/v1
Admin URL: https://staging-api.staffflow.com/admin/api
```

### Production

```
Base URL: https://api.staffflow.com/api/v1
Admin URL: https://admin-api.staffflow.com/admin/api
```

---

## Testing Credentials

### Super Admin

```
Email: superadmin@staffflow.test
Password: SuperAdmin123!
```

### Admin

```
Email: admin@staffflow.test
Password: Admin123!
```

### Recruiter

```
Email: recruiter@staffflow.test
Password: Recruiter123!
```

### Finance

```
Email: finance@staffflow.test
Password: Finance123!
```

### Compliance

```
Email: compliance@staffflow.test
Password: Compliance123!
```

### Worker

```
Email: worker@staffflow.test
Password: Worker123!
```

---

## Postman Collection

Import this JSON to get started with Postman:

**File:** `StaffFlow_API.postman_collection.json`

Available in: `/docs/postman/StaffFlow_API.postman_collection.json`

---

## Support & Documentation

- **Full Documentation:** [Your documentation URL]
- **API Swagger:** http://localhost:8000/api/documentation
- **Support Email:** support@staffflow.com
- **GitHub Issues:** [Your GitHub repo URL]
