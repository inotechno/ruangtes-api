# RuangTes API Documentation

**Version:** 1.0.0  
**Base URL:** `http://localhost:8000/api`  
**Authentication:** Laravel Sanctum (Bearer Token)

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [API Response Format](#api-response-format)
4. [Error Handling](#error-handling)
5. [User Roles & Permissions](#user-roles--permissions)
6. [API Endpoints](#api-endpoints)
   - [Authentication](#1-authentication)
   - [Public User Features](#2-public-user-features)
   - [Participant Flow (No Login)](#3-participant-flow-no-login)
   - [Test Session Management](#4-test-session-management)
   - [Anti-Cheat System](#5-anti-cheat-system)
   - [Photo Capture System](#6-photo-capture-system)
   - [SuperAdmin Features](#7-superadmin-features)
   - [TenantAdmin Features](#8-tenantadmin-features)
7. [Workflows](#workflows)
8. [Best Practices](#best-practices)

---

## Overview

RuangTes adalah SaaS aplikasi untuk psikotes online dengan arsitektur multi-tenant. API ini mendukung 3 jenis user yang authenticated (SuperAdmin, TenantAdmin, PublicUser) dan 1 jenis user yang tidak authenticated (Participant).

### Base URL

```
Production: https://api.ruangtes.com/api
Development: http://localhost:8000/api
```

### Headers

Semua request harus menyertakan:
- `Accept: application/json`
- `Content-Type: application/json` (untuk POST/PUT/PATCH)
- `Authorization: Bearer {token}` (untuk authenticated endpoints)

### Rate Limiting

API menggunakan rate limiting untuk mencegah abuse. Default: 60 requests per minute per IP.

---

## Authentication

### Authentication Methods

#### 1. Bearer Token (Authenticated Users)
Untuk SuperAdmin, TenantAdmin, dan PublicUser:
- Login → Dapatkan token → Gunakan di header `Authorization: Bearer {token}`
- Token berlaku hingga logout atau expire (default: unlimited)

#### 2. Unique Token (Participant - No Login)
Untuk Participant:
- Dapatkan unique token dari email assignment
- Gunakan token di query parameter `?token={assignment_token}` atau route parameter
- Tidak perlu authentication header

### Registration Flow

#### Public User Registration
```http
POST /api/auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Registration successful. Please verify your email.",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "email_verified_at": null,
            "roles": ["public_user"]
        }
    },
    "status_code": 201
}
```

#### Company & Admin Registration
```http
POST /api/auth/register-company
Content-Type: application/json

{
    "company_name": "PT Contoh Perusahaan",
    "company_email": "company@example.com",
    "company_phone": "081234567890",
    "company_address": "Jl. Contoh No. 123",
    "name": "Admin Name",
    "email": "admin@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### Login Flow

```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "1|abcdefghijklmnopqrstuvwxyz...",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "roles": ["public_user"],
            "permissions": ["view_dashboard", "view_tests", ...]
        }
    },
    "status_code": 200
}
```

**⚠️ Important:** Simpan token dari response ini untuk digunakan di semua authenticated requests.

### Email Verification

Setelah registrasi, user harus verify email:
```http
POST /api/auth/verify-email
Content-Type: application/json

{
    "token": "verification_token_from_email"
}
```

---

## API Response Format

### Success Response

Semua success response menggunakan format standar:

```json
{
    "success": true,
    "message": "Success message",
    "data": {
        // Response data
    },
    "status_code": 200
}
```

### Error Response

Semua error response menggunakan format standar:

```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field_name": ["Validation error message"]
    },
    "status_code": 400
}
```

### Paginated Response

Untuk endpoints dengan pagination:

```json
{
    "success": true,
    "message": "Data retrieved successfully",
    "data": {
        "data": [
            // Array of items
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7,
        "from": 1,
        "to": 15
    },
    "status_code": 200
}
```

---

## Error Handling

### HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request (validation error)
- `401` - Unauthorized (missing/invalid token)
- `403` - Forbidden (no permission)
- `404` - Not Found
- `422` - Unprocessable Entity (validation error)
- `500` - Server Error

### Common Error Messages

| Status | Message | Description |
|--------|---------|-------------|
| 401 | `Unauthenticated` | Token missing or invalid |
| 403 | `Unauthorized` | User doesn't have permission |
| 403 | `No active subscription found` | TenantAdmin needs subscription |
| 403 | `Test assignment period has expired` | Assignment time window invalid |
| 403 | `You have been banned from taking tests` | Participant/User is banned |
| 404 | `Resource not found` | Resource doesn't exist |
| 400 | `Validation failed` | Request validation error |

### Validation Errors

Format validation error:
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": [
            "The password must be at least 8 characters.",
            "The password confirmation does not match."
        ]
    },
    "status_code": 422
}
```

---

## User Roles & Permissions

### Roles

1. **super_admin** - Full access to all features
2. **tenant_admin** - Company-specific access (requires subscription)
3. **public_user** - Individual test purchase & taking

### Permissions

Setiap role memiliki permissions spesifik. Check user permissions via `/api/auth/me` endpoint.

**Common Permissions:**
- `view_dashboard` - View dashboard
- `view_tests` - View tests
- `take_tests` - Take tests (PublicUser)
- `purchase_tests` - Purchase tests (PublicUser)
- `view_participants` - View participants
- `create_participants` - Create participants
- `assign_tests` - Assign tests to participants
- `manage_subscription_plans` - Manage subscription plans (SuperAdmin)
- `purchase_subscription` - Purchase subscription (TenantAdmin)

---

## API Endpoints

### 1. Authentication

#### 1.1 Public User Registration
```http
POST /api/auth/register
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response:**
- `201` - Registration successful (email verification required)
- `422` - Validation error

---

#### 1.2 Company & Admin Registration
```http
POST /api/auth/register-company
```

**Request Body:**
```json
{
    "company_name": "PT Contoh",
    "company_email": "company@example.com",
    "company_phone": "081234567890",
    "company_address": "Jl. Contoh No. 123",
    "name": "Admin Name",
    "email": "admin@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

---

#### 1.3 Login
```http
POST /api/auth/login
```

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "1|token...",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "roles": ["public_user"],
            "permissions": ["view_dashboard", "view_tests", "take_tests"]
        }
    }
}
```

**💡 Frontend Action:** Simpan `data.token` ke localStorage/sessionStorage dan gunakan di semua authenticated requests.

---

#### 1.4 Get Current User
```http
GET /api/auth/me
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "email_verified_at": "2024-01-01T00:00:00Z",
        "roles": ["public_user"],
        "permissions": ["view_dashboard", "view_tests", "take_tests"],
        "userable": {
            "id": 1,
            "phone": "081234567890",
            "date_of_birth": "1990-01-01",
            "address": "Jl. Contoh No. 123"
        }
    }
}
```

---

#### 1.5 Logout
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

---

#### 1.6 Email Verification
```http
POST /api/auth/verify-email
```

**Request Body:**
```json
{
    "token": "verification_token_from_email"
}
```

---

#### 1.7 Resend Verification Email
```http
POST /api/auth/resend-verification
Authorization: Bearer {token}
```

---

#### 1.8 Forgot Password
```http
POST /api/auth/forgot-password
```

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

---

#### 1.9 Reset Password
```http
POST /api/auth/reset-password
```

**Request Body:**
```json
{
    "token": "reset_token_from_email",
    "email": "user@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

---

### 2. Public User Features

#### 2.1 Profile Management

##### Get Profile
```http
GET /api/profile
Authorization: Bearer {public_user_token}
```

##### Get Profile Completion Status
```http
GET /api/profile/completion-status
Authorization: Bearer {public_user_token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "is_complete": true,
        "completion_percentage": 100,
        "missing_fields": []
    }
}
```

##### Complete Profile
```http
POST /api/profile/complete
Authorization: Bearer {public_user_token}
```

**Request Body:**
```json
{
    "phone": "081234567890",
    "date_of_birth": "1990-01-01",
    "address": "Jl. Contoh No. 123",
    "biodata": {
        "additional_info": "Some info"
    }
}
```

##### Update Profile
```http
PUT /api/profile/update
Authorization: Bearer {public_user_token}
```

**Request Body:** (all fields optional)
```json
{
    "name": "New Name",
    "email": "newemail@example.com",
    "phone": "081234567890",
    "date_of_birth": "1990-01-01",
    "address": "New Address",
    "biodata": {}
}
```

##### Change Password
```http
POST /api/profile/change-password
Authorization: Bearer {public_user_token}
```

**Request Body:**
```json
{
    "current_password": "oldpassword123",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

---

#### 2.2 Test Catalog (Public - No Auth Required)

##### Browse Tests
```http
GET /api/tests/catalog?category=1&search=test&per_page=15
```

**Query Parameters:**
- `category` (optional) - Filter by category ID
- `search` (optional) - Search test name/description
- `per_page` (optional, default: 15) - Items per page

##### Get Test Details
```http
GET /api/tests/catalog/{testId}
Authorization: Bearer {public_user_token} (optional - untuk check is_purchased)
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Test Name",
        "code": "TEST-001",
        "description": "Test description",
        "price": 50000.00,
        "duration_minutes": 60,
        "question_count": 50,
        "category": {
            "id": 1,
            "name": "Category Name"
        },
        "is_purchased": false
    }
}
```

##### Get Categories
```http
GET /api/tests/catalog/categories
```

---

#### 2.3 Shopping Cart

##### Get Cart
```http
GET /api/cart
Authorization: Bearer {public_user_token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "items": [
            {
                "id": 1,
                "test": {
                    "id": 1,
                    "name": "Test Name",
                    "price": 50000.00
                }
            }
        ],
        "summary": {
            "item_count": 1,
            "total": 50000.00
        }
    }
}
```

##### Add Test to Cart
```http
POST /api/cart/add
Authorization: Bearer {public_user_token}
```

**Request Body:**
```json
{
    "test_id": 1
}
```

##### Remove Test from Cart
```http
DELETE /api/cart/{testId}
Authorization: Bearer {public_user_token}
```

##### Clear Cart
```http
DELETE /api/cart/clear
Authorization: Bearer {public_user_token}
```

---

#### 2.4 Checkout

##### Verify Cart
```http
GET /api/checkout/verify
Authorization: Bearer {public_user_token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "valid": true,
        "item_count": 2,
        "total": 100000.00,
        "items": [...]
    }
}
```

##### Checkout
```http
POST /api/checkout
Authorization: Bearer {public_user_token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "transaction_number": "TRX-ABC123XYZ",
        "total_amount": 100000.00,
        "status": "pending",
        "items": [...],
        "payment": {
            "id": 1,
            "payment_number": "PAY-20240101-ABC123",
            "amount": 100000.00,
            "status": "pending"
        }
    }
}
```

**💡 Frontend Action:** Simpan `data.id` sebagai `transaction_id` dan `data.payment.id` sebagai `payment_id` untuk upload payment proof.

---

#### 2.5 Transactions

##### Get Transaction History
```http
GET /api/transactions?status=completed&per_page=15
Authorization: Bearer {public_user_token}
```

**Query Parameters:**
- `status` (optional) - Filter: `pending`, `processing`, `completed`, `failed`, `cancelled`
- `per_page` (optional, default: 15)

##### Get Transaction Details
```http
GET /api/transactions/{transactionId}
Authorization: Bearer {public_user_token}
```

##### Get Purchased Tests
```http
GET /api/transactions/purchased-tests
Authorization: Bearer {public_user_token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "data": [
            {
                "test": {
                    "id": 1,
                    "name": "Test Name",
                    "code": "TEST-001"
                },
                "purchased_at": "2024-01-01T00:00:00Z",
                "transaction_number": "TRX-ABC123XYZ"
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 10
    }
}
```

---

#### 2.6 Payment

##### Get Payment for Transaction
```http
GET /api/transactions/{transactionId}/payment
Authorization: Bearer {public_user_token}
```

##### Upload Payment Proof
```http
POST /api/transactions/{transactionId}/payment/upload-proof
Authorization: Bearer {public_user_token}
Content-Type: multipart/form-data
```

**Request Body (Form Data):**
- `transaction_id` (integer) - Transaction ID
- `proof_file` (file) - Payment proof image (jpg, jpeg, png, pdf, max 5MB)
- `notes` (optional, string) - Payment notes

**Response:**
```json
{
    "success": true,
    "message": "Payment proof uploaded successfully. Waiting for verification.",
    "data": {
        "id": 1,
        "payment_number": "PAY-20240101-ABC123",
        "amount": 100000.00,
        "status": "pending",
        "proof_file": "http://localhost:8000/storage/payments/proofs/abc123.jpg",
        "notes": "Payment notes"
    }
}
```

---

#### 2.7 Public User Test Flow

##### Get Available Tests (Purchased & Payment Verified)
```http
GET /api/my-tests
Authorization: Bearer {public_user_token}
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Test Name",
            "code": "TEST-001",
            "description": "Test description",
            "duration_minutes": 60,
            "question_count": 50,
            "category": "Category Name",
            "has_active_session": false,
            "is_completed": false
        }
    ]
}
```

##### Get Test Instructions
```http
GET /api/my-tests/{testId}/instructions
Authorization: Bearer {public_user_token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "test": {
            "id": 1,
            "name": "Test Name",
            "code": "TEST-001",
            "description": "Test description",
            "duration_minutes": 60,
            "question_count": 50,
            "category": "Category Name"
        },
        "user": {
            "name": "John Doe",
            "email": "john@example.com"
        },
        "is_completed": false
    }
}
```

##### Start Test Session
```http
POST /api/my-tests/{testId}/start
Authorization: Bearer {public_user_token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "session": {
            "session_token": "SESSION-ABC123XYZ456",
            "test": {
                "id": 1,
                "name": "Test Name",
                "duration_minutes": 60
            },
            "started_at": "2024-01-01T00:00:00Z"
        }
    }
}
```

**💡 Frontend Action:** Simpan `data.session.session_token` untuk digunakan di semua test session endpoints.

---

### 3. Participant Flow (No Login)

**⚠️ Important:** Participant flow tidak memerlukan authentication. Semua endpoint menggunakan unique token dari email assignment.

#### 3.1 Get Assignment by Token
```http
GET /api/participant/assignment/{token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "assignment": {
            "id": 1,
            "token": "ASSIGNMENT-TOKEN-123",
            "start_date": "2024-01-01T00:00:00Z",
            "end_date": "2024-01-31T23:59:59Z",
            "is_completed": false,
            "time_remaining": 2592000
        },
        "test": {
            "id": 1,
            "name": "Test Name",
            "code": "TEST-001",
            "description": "Test description",
            "duration_minutes": 60,
            "question_count": 50,
            "type": "public"
        },
        "participant": {
            "id": 1,
            "name": "Participant Name",
            "email": "participant@example.com",
            "biodata": {}
        }
    }
}
```

---

#### 3.2 Get All Assignments (Multi-Test Flow)
```http
GET /api/participant/assignments?token={assignment_token}
```

**Response:** Array of assignments untuk participant yang sama.

---

#### 3.3 Check Biodata Status
```http
GET /api/participant/biodata/status?token={assignment_token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "is_complete": false,
        "biodata": {
            "name": "Participant Name",
            "email": "participant@example.com"
        }
    }
}
```

---

#### 3.4 Complete Biodata
```http
POST /api/participant/biodata/complete?token={assignment_token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "phone": "081234567890",
    "date_of_birth": "1990-01-01",
    "address": "Jl. Example No. 123",
    "additional_info": "Additional information"
}
```

---

#### 3.5 Get Test Instructions
```http
GET /api/participant/instructions?token={assignment_token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "test": {
            "id": 1,
            "name": "Test Name",
            "code": "TEST-001",
            "description": "Test description",
            "duration_minutes": 60,
            "question_count": 50,
            "category": "Category Name"
        },
        "assignment": {
            "id": 1,
            "token": "ASSIGNMENT-TOKEN-123",
            "start_date": "2024-01-01T00:00:00Z",
            "end_date": "2024-01-31T23:59:59Z",
            "time_remaining": 2592000
        },
        "participant": {
            "name": "Participant Name",
            "email": "participant@example.com"
        }
    }
}
```

---

#### 3.6 Start Test Session
```http
POST /api/participant/session/start?token={assignment_token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "session": {
            "session_token": "SESSION-ABC123XYZ456",
            "test": {
                "id": 1,
                "name": "Test Name",
                "duration_minutes": 60
            },
            "started_at": "2024-01-01T00:00:00Z"
        }
    }
}
```

**💡 Frontend Action:** Simpan `data.session.session_token` untuk test session management.

---

### 4. Test Session Management

**⚠️ Important:** Endpoints ini digunakan oleh BOTH Participant dan Public User. Menggunakan `session_token` yang didapat dari start session endpoint.

#### 4.1 Get Session Details
```http
GET /api/test-session/{sessionToken}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "session_token": "SESSION-ABC123XYZ456",
        "status": "in_progress",
        "started_at": "2024-01-01T00:00:00Z",
        "completed_at": null,
        "time_spent_seconds": 300,
        "test": {
            "id": 1,
            "name": "Test Name",
            "code": "TEST-001",
            "duration_minutes": 60,
            "question_count": 50
        },
        "assignment": {
            "id": 1,
            "token": "ASSIGNMENT-TOKEN-123",
            "start_date": "2024-01-01T00:00:00Z",
            "end_date": "2024-01-31T23:59:59Z"
        },
        "answers": [
            {
                "question_id": "question_1",
                "answer": "answer_value",
                "is_correct": null,
                "points": 0,
                "updated_at": "2024-01-01T00:05:00Z"
            }
        ]
    }
}
```

---

#### 4.2 Auto-Save Answers
```http
POST /api/test-session/{sessionToken}/save-answers
Content-Type: application/json
```

**Request Body:**
```json
{
    "answers": {
        "question_1": "answer_1",
        "question_2": ["option_a", "option_b"],
        "question_3": 5,
        "question_4": {
            "text": "Long answer text",
            "option": "selected_option"
        }
    }
}
```

**💡 Frontend Implementation:**
- Panggil endpoint ini setiap 30 detik atau saat user mengubah jawaban
- Format answer bisa: string, array, number, atau object (tergantung tipe soal)

**Response:**
```json
{
    "success": true,
    "message": "Answers saved successfully",
    "data": {
        "session_token": "SESSION-ABC123XYZ456",
        "time_spent_seconds": 310,
        "answers": [...]
    }
}
```

---

#### 4.3 Submit Test
```http
POST /api/test-session/{sessionToken}/submit
```

**Response:**
```json
{
    "success": true,
    "message": "Test submitted successfully",
    "data": {
        "id": 1,
        "session_token": "SESSION-ABC123XYZ456",
        "status": "completed",
        "completed_at": "2024-01-01T00:30:00Z",
        "time_spent_seconds": 1800
    }
}
```

**💡 Frontend Action:** Setelah submit, redirect ke completion page atau results page (jika sudah tersedia).

---

#### 4.4 Update Time Spent
```http
POST /api/test-session/{sessionToken}/update-time
```

**💡 Frontend Implementation:**
- Panggil endpoint ini setiap 10-15 detik untuk update time spent
- Berguna untuk time tracking dan anti-cheat detection

**Response:**
```json
{
    "success": true,
    "data": {
        "time_spent_seconds": 600
    }
}
```

---

### 5. Anti-Cheat System

#### 5.1 Log Cheat Event
```http
POST /api/test-session/{sessionToken}/cheat/log
Content-Type: application/json
```

**Request Body:**
```json
{
    "detection_type": "tab_switch",
    "detection_data": {
        "timestamp": "2024-01-01T00:10:00Z",
        "details": "User switched tabs",
        "target_url": "https://google.com"
    },
    "severity": 2
}
```

**Detection Types:**
- `tab_switch` - User switched browser tabs
- `window_blur` - Window lost focus
- `keyboard_shortcut` - Keyboard shortcut detected (Alt+Tab, Ctrl+W, etc.)
- `right_click` - Right click detected
- `copy_paste` - Copy/paste detected
- `time_anomaly` - Time anomaly (too fast/too slow)
- `multiple_devices` - Multiple devices detected

**Severity:** 1-5 (5 = auto-ban)

**Response:**
```json
{
    "success": true,
    "data": {
        "detection": {
            "id": 1,
            "type": "tab_switch",
            "severity": 2,
            "created_at": "2024-01-01T00:10:00Z"
        },
        "session_status": "in_progress"
    }
}
```

**⚠️ Auto-Ban Rules:**
- Severity 5 → Auto-ban
- 3+ detections same type → Auto-ban
- 10+ total detections → Auto-ban

---

#### 5.2 Get Cheat Detections
```http
GET /api/test-session/{sessionToken}/cheat/detections
```

**Response:** Array of cheat detections untuk session.

---

### 6. Photo Capture System

#### 6.1 Capture Photo
```http
POST /api/test-session/{sessionToken}/photo/capture
Content-Type: multipart/form-data
```

**Request Body (Form Data):**
- `photo` (file) - Photo image (jpg, jpeg, png, max 2MB)

**💡 Frontend Implementation:**
- Capture photo menggunakan `getUserMedia()` API
- Panggil endpoint ini setiap 30-60 detik secara random
- Konversi Blob ke File sebelum upload

**Response:**
```json
{
    "success": true,
    "data": {
        "photo": {
            "id": 1,
            "url": "http://localhost:8000/storage/test-sessions/photos/abc123.jpg",
            "captured_at": "2024-01-01T00:15:00Z"
        }
    }
}
```

---

#### 6.2 Get Photos
```http
GET /api/test-session/{sessionToken}/photos
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "url": "http://localhost:8000/storage/test-sessions/photos/abc123.jpg",
            "captured_at": "2024-01-01T00:15:00Z"
        }
    ]
}
```

---

### 7. SuperAdmin Features

#### 7.1 Dashboard
```http
GET /api/dashboard
Authorization: Bearer {super_admin_token}
```

**Response:** SuperAdmin dashboard statistics (companies, subscriptions, tests, users, sessions, dll).

---

#### 7.2 Test Management

##### List Tests
```http
GET /api/tests?category=1&search=test&per_page=15
Authorization: Bearer {super_admin_token}
```

##### Create Test
```http
POST /api/tests
Authorization: Bearer {super_admin_token}
```

**Request Body:**
```json
{
    "category_id": 1,
    "name": "Test Name",
    "code": "TEST-001",
    "description": "Test description",
    "price": 50000.00,
    "question_count": 50,
    "duration_minutes": 60,
    "type": "public",
    "instruction_route": "/tests/test-001/instruction",
    "test_route": "/tests/test-001/take",
    "metadata": {},
    "is_active": true,
    "sort_order": 1
}
```

##### Update Test
```http
PUT /api/tests/{test}
Authorization: Bearer {super_admin_token}
```

##### Delete Test
```http
DELETE /api/tests/{test}
Authorization: Bearer {super_admin_token}
```

---

#### 7.3 Test Category Management

##### List Categories
```http
GET /api/test-categories?per_page=15
Authorization: Bearer {super_admin_token}
```

##### Create Category
```http
POST /api/test-categories
Authorization: Bearer {super_admin_token}
```

**Request Body:**
```json
{
    "name": "Category Name",
    "slug": "category-slug",
    "description": "Category description",
    "is_active": true,
    "sort_order": 1
}
```

---

#### 7.4 Subscription Management

##### List Subscription Plans
```http
GET /api/subscription-plans
Authorization: Bearer {super_admin_token}
```

##### Create Subscription Plan
```http
POST /api/subscription-plans
Authorization: Bearer {super_admin_token}
```

**Request Body:**
```json
{
    "name": "3 Bulan",
    "duration_months": 3,
    "description": "Subscription 3 bulan",
    "is_active": true
}
```

##### Manage Subscription Prices
```http
POST /api/subscription-prices
Authorization: Bearer {super_admin_token}
```

**Request Body:**
```json
{
    "subscription_plan_id": 1,
    "user_quota": 10,
    "price": 1000000.00,
    "price_per_additional_user": 100000.00
}
```

---

#### 7.5 Company Management

##### List Companies
```http
GET /api/companies?search=company&per_page=15
Authorization: Bearer {super_admin_token}
```

##### Create Company
```http
POST /api/companies
Authorization: Bearer {super_admin_token}
```

##### Get Company Details
```http
GET /api/companies/{company}
Authorization: Bearer {super_admin_token}
```

##### Manage Company Admins
```http
GET /api/companies/{company}/admins
POST /api/companies/{company}/admins
GET /api/company-admins/{admin}
PUT /api/company-admins/{admin}
DELETE /api/company-admins/{admin}
POST /api/company-admins/{admin}/set-primary
```

##### View Participants (SuperAdmin)
```http
GET /api/participants?company_id=1&per_page=15
Authorization: Bearer {super_admin_token}
```

**Query Parameters:**
- `company_id` (optional) - Filter by company
- `status` (optional) - Filter: `active`, `banned`
- `search` (optional) - Search name/email
- `per_page` (optional, default: 15)

---

#### 7.6 Payment Verification

##### Get All Payments
```http
GET /api/payments/all?status=pending&method=manual&per_page=15
Authorization: Bearer {super_admin_token}
```

##### Get Pending Payments
```http
GET /api/payments/pending
Authorization: Bearer {super_admin_token}
```

##### Verify Payment
```http
POST /api/payments/verify
Authorization: Bearer {super_admin_token}
```

**Request Body:**
```json
{
    "payment_id": 1,
    "approved": true,
    "notes": "Payment verified"
}
```

**Response:**
- Jika `approved: true` → Payment status: `paid`, Transaction/Invoice status: `completed`
- Jika `approved: false` → Payment status: `failed`, Transaction/Invoice status: `failed`

---

### 8. TenantAdmin Features

#### 8.1 Dashboard
```http
GET /api/dashboard
Authorization: Bearer {tenant_admin_token}
```

**Response:** TenantAdmin dashboard dengan company-specific statistics.

---

#### 8.2 Subscription Management

##### Get Available Plans
```http
GET /api/subscriptions/available-plans
Authorization: Bearer {tenant_admin_token}
```

##### Purchase Subscription
```http
POST /api/subscriptions/purchase
Authorization: Bearer {tenant_admin_token}
```

**Request Body:**
```json
{
    "subscription_plan_id": 1,
    "subscription_price_id": 1,
    "payment_type": "pre_paid",
    "notes": "Purchase notes"
}
```

**Payment Types:**
- `pre_paid` - Bayar dulu, aktif setelah payment verified
- `post_paid` - Aktif dulu, invoice di akhir periode

**Response:**
```json
{
    "success": true,
    "data": {
        "subscription": {
            "id": 1,
            "status": "pending",
            "expires_at": "2024-04-01T00:00:00Z"
        },
        "transaction": {
            "id": 1,
            "amount": 1000000.00,
            "status": "pending"
        },
        "payment": {
            "id": 1,
            "payment_number": "PAY-20240101-ABC123",
            "amount": 1000000.00,
            "status": "pending"
        }
    }
}
```

---

##### Get Active Subscription
```http
GET /api/subscriptions/active
Authorization: Bearer {tenant_admin_token}
```

##### Get Subscription History
```http
GET /api/subscriptions/history
Authorization: Bearer {tenant_admin_token}
```

##### Purchase User Quota
```http
POST /api/subscriptions/{subscription}/purchase-quota
Authorization: Bearer {tenant_admin_token}
```

**Request Body:**
```json
{
    "additional_users": 5
}
```

##### Extend Subscription
```http
POST /api/subscriptions/{subscription}/extend
Authorization: Bearer {tenant_admin_token}
```

**Request Body:**
```json
{
    "extension_months": 3
}
```

##### Cancel Subscription
```http
POST /api/subscriptions/{subscription}/cancel
Authorization: Bearer {tenant_admin_token}
```

---

#### 8.3 Payment Management

##### List Payments
```http
GET /api/payments
Authorization: Bearer {tenant_admin_token}
```

##### Upload Payment Proof
```http
POST /api/payments/upload-proof
Authorization: Bearer {tenant_admin_token}
Content-Type: multipart/form-data
```

**Request Body (Form Data):**
- `payment_id` (integer) - Payment ID
- `proof_file` (file) - Payment proof (jpg, jpeg, png, pdf, max 5MB)
- `notes` (optional, string)

---

#### 8.4 Invoice Management

##### List Invoices
```http
GET /api/invoices
Authorization: Bearer {tenant_admin_token}
```

##### Get Invoice Details
```http
GET /api/invoices/{invoice}
Authorization: Bearer {tenant_admin_token}
```

---

#### 8.5 Participant Management

##### List Participants
```http
GET /api/participants?status=active&search=name&per_page=15
Authorization: Bearer {tenant_admin_token}
```

**Query Parameters:**
- `status` (optional) - Filter: `active`, `banned`
- `search` (optional) - Search name/email
- `per_page` (optional, default: 15)

##### Create Participant
```http
POST /api/participants
Authorization: Bearer {tenant_admin_token}
```

**Request Body:**
```json
{
    "name": "Participant Name",
    "email": "participant@example.com",
    "schedule_start_date": "2024-01-01T00:00:00Z",
    "schedule_end_date": "2024-01-31T23:59:59Z",
    "test_ids": [1, 2, 3]
}
```

**💡 Important:** Endpoint ini akan:
1. Create participant
2. Assign tests (create test assignments)
3. Generate unique tokens
4. Send assignment emails (queued)

---

##### Get Participant Details
```http
GET /api/participants/{participant}
Authorization: Bearer {tenant_admin_token}
```

##### Update Participant
```http
PUT /api/participants/{participant}
Authorization: Bearer {tenant_admin_token}
```

**Request Body:**
```json
{
    "name": "New Name",
    "email": "newemail@example.com"
}
```

##### Delete Participant
```http
DELETE /api/participants/{participant}
Authorization: Bearer {tenant_admin_token}
```

##### Ban Participant
```http
POST /api/participants/{participant}/ban
Authorization: Bearer {tenant_admin_token}
```

**Request Body:**
```json
{
    "reason": "Cheating detected"
}
```

##### Unban Participant
```http
POST /api/participants/{participant}/unban
Authorization: Bearer {tenant_admin_token}
```

---

#### 8.6 Import Participants

##### Preview Import
```http
POST /api/participants/import-preview
Authorization: Bearer {tenant_admin_token}
Content-Type: multipart/form-data
```

**Request Body (Form Data):**
- `file` (file) - Excel file (.xlsx, .xls)
- `schedule_start_date` (string) - Start date for all participants
- `schedule_end_date` (string) - End date for all participants
- `test_ids` (array) - Array of test IDs to assign

**Response:**
```json
{
    "success": true,
    "data": {
        "preview": [
            {
                "row": 2,
                "name": "Participant 1",
                "email": "participant1@example.com",
                "valid": true
            },
            {
                "row": 3,
                "name": "Participant 2",
                "email": "invalid-email",
                "valid": false,
                "errors": ["Invalid email format"]
            }
        ],
        "total_rows": 100,
        "valid_rows": 98,
        "invalid_rows": 2
    }
}
```

##### Submit Import
```http
POST /api/participants/import
Authorization: Bearer {tenant_admin_token}
Content-Type: multipart/form-data
```

**Request Body:** Same as preview

**Response:**
```json
{
    "success": true,
    "data": {
        "created": 98,
        "failed": 2,
        "failed_rows": [3, 5],
        "participants": [...]
    }
}
```

---

#### 8.7 Test Assignment Email

##### Resend Assignment Email
```http
POST /api/test-assignments/{assignment}/resend-email
Authorization: Bearer {tenant_admin_token}
```

##### Resend All Emails for Participant
```http
POST /api/participants/{participant}/resend-all-emails
Authorization: Bearer {tenant_admin_token}
```

---

## Workflows

### Workflow 1: Public User - Purchase & Take Test

```
1. Register → POST /api/auth/register
2. Verify Email → POST /api/auth/verify-email
3. Login → POST /api/auth/login (save token)
4. Browse Tests → GET /api/tests/catalog
5. Add to Cart → POST /api/cart/add
6. Checkout → POST /api/checkout (save transaction_id, payment_id)
7. Upload Payment Proof → POST /api/transactions/{transactionId}/payment/upload-proof
8. Wait for Payment Verification (SuperAdmin)
9. Get Available Tests → GET /api/my-tests
10. Get Instructions → GET /api/my-tests/{testId}/instructions
11. Start Test → POST /api/my-tests/{testId}/start (save session_token)
12. Take Test:
    - Auto-save answers → POST /api/test-session/{sessionToken}/save-answers (periodic)
    - Capture photos → POST /api/test-session/{sessionToken}/photo/capture (periodic)
    - Log cheat events → POST /api/test-session/{sessionToken}/cheat/log (if detected)
    - Update time → POST /api/test-session/{sessionToken}/update-time (periodic)
13. Submit Test → POST /api/test-session/{sessionToken}/submit
```

---

### Workflow 2: Participant - Take Assigned Test (No Login)

```
1. Receive Email → Click unique link: /participant/assignment/{token}
2. Get Assignment → GET /api/participant/assignment/{token} (save assignment_token)
3. Check Biodata → GET /api/participant/biodata/status?token={token}
4. Complete Biodata → POST /api/participant/biodata/complete?token={token} (if not complete)
5. Get Instructions → GET /api/participant/instructions?token={token}
6. Start Test → POST /api/participant/session/start?token={token} (save session_token)
7. Take Test:
    - Auto-save answers → POST /api/test-session/{sessionToken}/save-answers (periodic)
    - Capture photos → POST /api/test-session/{sessionToken}/photo/capture (periodic)
    - Log cheat events → POST /api/test-session/{sessionToken}/cheat/log (if detected)
    - Update time → POST /api/test-session/{sessionToken}/update-time (periodic)
8. Submit Test → POST /api/test-session/{sessionToken}/submit
9. Multi-Test Flow (if multiple assignments):
    - Get All Assignments → GET /api/participant/assignments?token={token}
    - Repeat steps 5-8 for next test
```

---

### Workflow 3: TenantAdmin - Manage Participants & Assign Tests

```
1. Login → POST /api/auth/login (save tenant_admin_token)
2. Create Participant:
   - Single → POST /api/participants (with test_ids, schedule dates)
   - Bulk → POST /api/participants/import-preview → POST /api/participants/import
3. View Participants → GET /api/participants
4. View Participant Details → GET /api/participants/{participant}
5. Resend Assignment Email:
   - Single → POST /api/test-assignments/{assignment}/resend-email
   - All → POST /api/participants/{participant}/resend-all-emails
6. Monitor Test Sessions → (Future: Real-time monitoring dashboard)
7. View Results → (Future: Test results endpoint)
```

---

### Workflow 4: SuperAdmin - Payment Verification

```
1. Login → POST /api/auth/login (save super_admin_token)
2. Get Pending Payments → GET /api/payments/pending
3. Get Payment Details → GET /api/payments/{payment}/verify
4. Verify Payment → POST /api/payments/verify
   {
       "payment_id": 1,
       "approved": true,
       "notes": "Payment verified"
   }
5. System automatically:
   - Update payment status → paid
   - Update transaction/invoice status → completed
   - Activate subscription (if applicable)
   - Send email notification to user
```

---

## Best Practices

### 1. Token Management

**For Authenticated Users:**
```javascript
// Save token after login
localStorage.setItem('auth_token', response.data.token);

// Use token in all requests
const token = localStorage.getItem('auth_token');
fetch('/api/profile', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

**For Participant (No Login):**
```javascript
// Get token from URL query parameter or email link
const urlParams = new URLSearchParams(window.location.search);
const assignmentToken = urlParams.get('token') || getTokenFromUrl();

// Use token in all participant requests
fetch(`/api/participant/biodata/status?token=${assignmentToken}`, {
    headers: {
        'Accept': 'application/json'
    }
});
```

---

### 2. Error Handling

**Always check response status:**
```javascript
const response = await fetch('/api/profile', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});

const data = await response.json();

if (!data.success) {
    // Handle error
    if (response.status === 401) {
        // Token expired - redirect to login
        localStorage.removeItem('auth_token');
        window.location.href = '/login';
    } else if (response.status === 403) {
        // No permission - show error message
        showError(data.message);
    } else if (response.status === 422) {
        // Validation error - show field errors
        showValidationErrors(data.errors);
    } else {
        // Generic error
        showError(data.message);
    }
    return;
}

// Success - use data.data
const profile = data.data;
```

---

### 3. Auto-Save Answers (Periodic)

**Implement auto-save every 30 seconds:**
```javascript
let autoSaveInterval;
let sessionToken = 'SESSION-ABC123';

function startAutoSave(answers) {
    autoSaveInterval = setInterval(async () => {
        try {
            const response = await fetch(
                `/api/test-session/${sessionToken}/save-answers`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ answers })
                }
            );
            
            const data = await response.json();
            if (data.success) {
                console.log('Answers auto-saved');
            }
        } catch (error) {
            console.error('Auto-save failed:', error);
        }
    }, 30000); // 30 seconds
}

// Stop auto-save on submit
function stopAutoSave() {
    if (autoSaveInterval) {
        clearInterval(autoSaveInterval);
    }
}
```

---

### 4. Time Tracking (Periodic)

**Update time spent every 10-15 seconds:**
```javascript
let timeUpdateInterval;

function startTimeTracking(sessionToken) {
    timeUpdateInterval = setInterval(async () => {
        try {
            await fetch(`/api/test-session/${sessionToken}/update-time`, {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
        } catch (error) {
            console.error('Time update failed:', error);
        }
    }, 15000); // 15 seconds
}
```

---

### 5. Photo Capture (Random Interval)

**Capture photo every 30-60 seconds randomly:**
```javascript
function startPhotoCapture(sessionToken) {
    const capturePhoto = async () => {
        try {
            // Capture photo using getUserMedia
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            const video = document.createElement('video');
            video.srcObject = stream;
            video.play();
            
            setTimeout(async () => {
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                
                canvas.toBlob(async (blob) => {
                    const file = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
                    const formData = new FormData();
                    formData.append('photo', file);
                    
                    await fetch(`/api/test-session/${sessionToken}/photo/capture`, {
                        method: 'POST',
                        body: formData
                    });
                    
                    stream.getTracks().forEach(track => track.stop());
                }, 'image/jpeg');
            }, 1000);
        } catch (error) {
            console.error('Photo capture failed:', error);
        }
    };
    
    // Capture immediately
    capturePhoto();
    
    // Then capture at random intervals (30-60 seconds)
    const scheduleNextCapture = () => {
        const delay = 30000 + Math.random() * 30000; // 30-60 seconds
        setTimeout(() => {
            capturePhoto();
            scheduleNextCapture();
        }, delay);
    };
    
    scheduleNextCapture();
}
```

---

### 6. Cheat Detection (Frontend)

**Implement cheat detection hooks:**
```javascript
function setupCheatDetection(sessionToken) {
    // Tab switch detection
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            logCheatEvent(sessionToken, 'tab_switch', {
                timestamp: new Date().toISOString(),
                details: 'Tab switched'
            }, 2);
        }
    });
    
    // Window blur detection
    window.addEventListener('blur', () => {
        logCheatEvent(sessionToken, 'window_blur', {
            timestamp: new Date().toISOString(),
            details: 'Window lost focus'
        }, 2);
    });
    
    // Keyboard shortcut detection
    document.addEventListener('keydown', (e) => {
        if ((e.altKey && e.key === 'Tab') || 
            (e.ctrlKey && (e.key === 'w' || e.key === 'W')) ||
            (e.ctrlKey && e.key === 't')) {
            logCheatEvent(sessionToken, 'keyboard_shortcut', {
                timestamp: new Date().toISOString(),
                key: e.key,
                ctrlKey: e.ctrlKey,
                altKey: e.altKey
            }, 3);
        }
    });
    
    // Right click prevention
    document.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        logCheatEvent(sessionToken, 'right_click', {
            timestamp: new Date().toISOString()
        }, 2);
    });
    
    // Copy/paste prevention
    document.addEventListener('copy', (e) => {
        e.preventDefault();
        logCheatEvent(sessionToken, 'copy_paste', {
            timestamp: new Date().toISOString(),
            type: 'copy'
        }, 3);
    });
    
    document.addEventListener('paste', (e) => {
        e.preventDefault();
        logCheatEvent(sessionToken, 'copy_paste', {
            timestamp: new Date().toISOString(),
            type: 'paste'
        }, 3);
    });
}

function logCheatEvent(sessionToken, type, data, severity) {
    fetch(`/api/test-session/${sessionToken}/cheat/log`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            detection_type: type,
            detection_data: data,
            severity: severity
        })
    }).then(response => {
        if (response.ok) {
            const data = await response.json();
            if (data.data.session_status === 'banned') {
                // Session banned - redirect to banned page
                window.location.href = '/test/banned';
            }
        }
    });
}
```

---

### 7. Session Token Management

**Save and use session token:**
```javascript
// After start session
const startResponse = await fetch(`/api/my-tests/${testId}/start`, {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${authToken}`,
        'Accept': 'application/json'
    }
});

const startData = await startResponse.json();
const sessionToken = startData.data.session.session_token;

// Save to localStorage/sessionStorage
sessionStorage.setItem('session_token', sessionToken);

// Use in all test session requests
const sessionToken = sessionStorage.getItem('session_token');
```

---

### 8. Handling Pagination

**Implement pagination navigation:**
```javascript
async function loadParticipants(page = 1) {
    const response = await fetch(
        `/api/participants?page=${page}&per_page=15`,
        {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        }
    );
    
    const data = await response.json();
    
    if (data.success) {
        const pagination = data.data;
        
        // Render items
        renderItems(pagination.data);
        
        // Render pagination controls
        renderPagination({
            currentPage: pagination.current_page,
            lastPage: pagination.last_page,
            total: pagination.total,
            perPage: pagination.per_page
        });
    }
}
```

---

### 9. File Upload (Payment Proof, Photo Capture)

**Handle file upload properly:**
```javascript
// Payment proof upload
const fileInput = document.querySelector('input[type="file"]');
const file = fileInput.files[0];

if (!file) {
    alert('Please select a file');
    return;
}

// Validate file type
const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
if (!allowedTypes.includes(file.type)) {
    alert('Invalid file type. Allowed: JPG, PNG, PDF');
    return;
}

// Validate file size (5MB for payment, 2MB for photo)
const maxSize = 5 * 1024 * 1024; // 5MB
if (file.size > maxSize) {
    alert('File size exceeds 5MB');
    return;
}

// Upload
const formData = new FormData();
formData.append('proof_file', file);
formData.append('transaction_id', transactionId);
formData.append('notes', 'Payment proof');

const response = await fetch(
    `/api/transactions/${transactionId}/payment/upload-proof`,
    {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        },
        body: formData
    }
);
```

---

### 10. Multi-Test Flow (Participant)

**Handle multiple test assignments:**
```javascript
// Get all assignments
const response = await fetch(
    `/api/participant/assignments?token=${assignmentToken}`,
    {
        headers: { 'Accept': 'application/json' }
    }
);

const data = await response.json();
const assignments = data.data;

// Show test selection UI
assignments.forEach((assignment, index) => {
    const testCard = createTestCard(assignment);
    testCard.onclick = () => startTest(assignment.token);
    testList.appendChild(testCard);
});

// After completing one test, show next test
function onTestCompleted() {
    const currentIndex = assignments.findIndex(
        a => a.token === currentAssignmentToken
    );
    
    if (currentIndex < assignments.length - 1) {
        // Show next test
        const nextAssignment = assignments[currentIndex + 1];
        showTestInstructions(nextAssignment);
    } else {
        // All tests completed
        showCompletionPage();
    }
}
```

---

## Important Notes

### 1. Token Expiry
- Authenticated tokens: Unlimited (until logout)
- Assignment tokens: Valid selama `start_date` ≤ now ≤ `end_date`
- Session tokens: Valid selama session status = `in_progress`

### 2. Subscription Requirement
- TenantAdmin: Memerlukan active subscription untuk akses fitur
- PublicUser: Tidak perlu subscription (buy tests individually)

### 3. Payment Verification
- Public User: Upload payment proof → SuperAdmin verify → Test available
- TenantAdmin: Upload payment proof → SuperAdmin verify → Subscription active

### 4. Test Session Status
- `in_progress` - Test sedang dikerjakan
- `completed` - Test selesai
- `abandoned` - Test ditinggalkan (future feature)
- `banned` - Test dibanned karena cheating

### 5. Auto-Ban Rules
- Severity 5 → Auto-ban immediately
- 3+ detections same type → Auto-ban
- 10+ total detections → Auto-ban
- Time anomaly (too fast/too slow) → Warning/ban based on severity

### 6. Photo Capture Requirements
- Photo format: JPG, JPEG, PNG
- Max size: 2MB
- Capture interval: 30-60 seconds (random, frontend implementation)
- Photos disimpan di `storage/test-sessions/photos/`

### 7. File Upload Limits
- Payment proof: Max 5MB (JPG, PNG, PDF)
- Photo capture: Max 2MB (JPG, PNG)

### 8. Time Tracking
- Frontend harus track time spent secara client-side
- Backend validate time spent untuk anomaly detection
- Update time ke backend setiap 10-15 detik

---

## Environment Variables

Gunakan environment variables untuk configuration:

```javascript
// .env.local
VITE_API_BASE_URL=http://localhost:8000/api
VITE_FRONTEND_URL=http://localhost:3000
```

```javascript
// Use in frontend
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;
const FRONTEND_URL = import.meta.env.VITE_FRONTEND_URL;
```

---

## Support & Contact

Untuk pertanyaan atau issues, hubungi tim development.

**Last Updated:** January 2024
