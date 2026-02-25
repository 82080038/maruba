# API Documentation

## 📋 Overview
API endpoints for the KOPERASI APP system.

## 🔐 Authentication
All API endpoints require authentication via session.

## 📊 Dashboard API

### GET /api/dashboard
Returns dashboard metrics and data.

**Response:**
```json
{
  "metrics": [
    {"label": "Outstanding", "value": 100, "type": "number"},
    {"label": "Anggota Aktif", "value": 50, "type": "number"},
    {"label": "Pinjaman Berjalan", "value": 25, "type": "number"},
    {"label": "NPL", "value": 5.2, "type": "percent"}
  ],
  "overdue_repayments": 0,
  "due_this_week": 0,
  "alerts": {
    "overdue": [],
    "due_week": []
  }
}
```

## 👤 User API

### GET /api/user
Returns current user information.

**Response:**
```json
{
  "id": 1,
  "username": "admin",
  "name": "Admin User",
  "role": "admin",
  "email": "admin@example.com"
}
```

## 📈 Loan API

### GET /api/loans
Returns list of loans.

**Response:**
```json
{
  "loans": [
    {
      "id": 1,
      "loan_number": "PJM001",
      "member_name": "John Doe",
      "amount": 500000,
      "status": "active",
      "created_at": "2026-02-25"
    }
  ],
  "total": 1,
  "page": 1,
  "per_page": 10
}
```

## 📋 Member API

### GET /api/members
Returns list of members.

**Response:**
```json
{
  "members": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "08123456789",
      "status": "active",
      "created_at": "2026-02-25"
    }
  ],
  "total": 1,
  "page": 1,
  "per_page": 10
}
```

## 🔒 Error Responses

### 401 Unauthorized
```json
{
  "error": "Unauthorized",
  "message": "Authentication required"
}
```

### 404 Not Found
```json
{
  "error": "Not Found",
  "message": "Endpoint not found"
}
```

### 500 Internal Server Error
```json
{
  "error": "Internal Server Error",
  "message": "Server error occurred"
}
```

## 📝 Notes
- All timestamps are in ISO 8601 format
- All monetary values are in Indonesian Rupiah
- Pagination starts from page 1
- Default per_page is 10

---

*API documentation is continuously updated.*