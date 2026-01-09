# RuangTes API Postman Collections

Koleksi Postman untuk testing RuangTes API. Setiap fitur memiliki collection terpisah untuk memudahkan import dan testing.

## 📁 Struktur File

```
postman/
├── RuangTes_Environment.json    # Environment variables
├── Auth_Collection.json          # Authentication endpoints
├── Dashboard_Collection.json    # Dashboard endpoints (SuperAdmin & TenantAdmin)
├── Test_Management_Collection.json  # Test Categories & Tests management
├── Subscription_Management_Collection.json  # Subscription Plans & Prices management
├── Subscription_Payment_Collection.json  # Subscription & Payment management (TenantAdmin)
├── Payment_Verification_Collection.json  # Payment verification (SuperAdmin)
├── Company_Management_Collection.json  # Company, Admin & Participant management (SuperAdmin)
├── Participant_Management_Collection.json  # Participant management (TenantAdmin)
└── README.md                     # Dokumentasi ini
```

## 🚀 Quick Start

### 1. Import Environment

1. Buka Postman
2. Klik **Import** di kiri atas
3. Pilih file `RuangTes_Environment.json`
4. Pilih environment yang diimport
5. Set sebagai environment aktif

### 2. Import Collections

Import collection sesuai kebutuhan:

- **Auth_Collection.json** - Untuk testing authentication endpoints
- **Dashboard_Collection.json** - Untuk testing dashboard endpoints (SuperAdmin & TenantAdmin)
- **Test_Management_Collection.json** - Untuk testing test categories & tests management (SuperAdmin)
- **Subscription_Management_Collection.json** - Untuk testing subscription plans & prices management (SuperAdmin)
- **Subscription_Payment_Collection.json** - Untuk testing subscription & payment management (TenantAdmin)
- **Payment_Verification_Collection.json** - Untuk testing payment verification (SuperAdmin)
- **Company_Management_Collection.json** - Untuk testing company, admin & participant management (SuperAdmin)
- **Participant_Management_Collection.json** - Untuk testing participant management (TenantAdmin)

### 3. Setup Environment Variables

Setelah import environment, pastikan variable berikut sudah diisi:

- `base_url` - Base URL API (default: http://localhost:8000)
- `login_email` - Email untuk login (default: superadmin@ruangtes.com)
- `login_password` - Password untuk login (default: password)

Token akan otomatis tersimpan setelah login berhasil:
- `auth_token` - Token umum (auto-saved)
- `super_admin_token` - Token untuk super admin (auto-saved)
- `tenant_admin_token` - Token untuk tenant admin (auto-saved)
- `public_user_token` - Token untuk public user (auto-saved)

## 🔐 Authentication

### Auto-Save Token

Collection **Auth_Collection.json** sudah dilengkapi dengan script untuk auto-save token ke environment setelah login berhasil.

**Login endpoint** akan otomatis:
1. Menyimpan token ke `auth_token`
2. Mendeteksi role user
3. Menyimpan token ke variable sesuai role:
   - `super_admin_token` untuk super admin
   - `tenant_admin_token` untuk tenant admin
   - `public_user_token` untuk public user

### Manual Token Setup

Jika ingin set token secara manual:

1. Login melalui endpoint Login
2. Copy token dari response
3. Set di environment variable sesuai kebutuhan

## 📋 Available Collections

### 1. Authentication (`Auth_Collection.json`)

Endpoints untuk authentication:
- Register Public User
- Register Company & Admin
- Login (dengan auto-save token)
- Logout
- Get Current User
- Verify Email
- Resend Verification Email
- Forgot Password
- Reset Password

### 2. Dashboard (`Dashboard_Collection.json`)

Endpoints untuk dashboard statistics:
- **SuperAdmin Dashboard:**
  - Overview stats (companies, subscriptions, tests, users, test sessions)
  - Company statistics
  - Subscription statistics
  - Test statistics
  - User statistics
  - Test session statistics
  - Recent activities
  - Growth statistics

- **TenantAdmin Dashboard:**
  - Company information
  - Subscription status & quota usage
  - Overview statistics (participants, assignments, sessions)
  - Participant statistics (total, active, banned, new this month)
  - Test assignment statistics (completed, pending, active, expired)
  - Test session statistics (completed, in_progress, abandoned, banned)
  - Recent activities (participants, assignments, completed sessions)
  - Growth statistics (participants, assignments, sessions with growth percentage)

### 3. Test Management (`Test_Management_Collection.json`)

Endpoints untuk test categories & tests management (SuperAdmin only):
- **Test Categories:**
  - List Test Categories (dengan filtering & pagination)
  - Get Test Category
  - Create Test Category
  - Update Test Category
  - Delete Test Category

- **Tests:**
  - List Tests (dengan filtering & pagination)
  - Get Test
  - Create Test (dengan instruction_route & test_route)
  - Update Test
  - Delete Test

### 4. Subscription Management (`Subscription_Management_Collection.json`)

Endpoints untuk subscription plans & prices management (SuperAdmin only):
- **Subscription Plans:**
  - List Subscription Plans (dengan filtering & pagination)
  - Get Subscription Plan (dengan prices)
  - Create Subscription Plan (auto-save ID)
  - Update Subscription Plan
  - Delete Subscription Plan

- **Subscription Prices:**
  - List Prices for Plan (dengan pagination)
  - Get All Prices for Plan (non-paginated, untuk dropdowns)
  - Get Subscription Price
  - Create Subscription Price (auto-save ID, dengan user_quota & pricing)
  - Update Subscription Price
  - Delete Subscription Price

### 5. Subscription & Payment (`Subscription_Payment_Collection.json`)

Endpoints untuk subscription & payment management (TenantAdmin only):
- **Subscriptions:**
  - Get Available Plans (auto-save plan & price IDs)
  - Purchase Subscription (Pre-paid)
  - Purchase Subscription (Post-paid)
  - Get Active Subscription
  - Get Subscription History
  - Purchase User Quota
  - Extend Subscription
  - Cancel Subscription

- **Payments:**
  - Get All Payments
  - Get Payment Detail
  - Upload Payment Proof

- **Invoices:**
  - Get All Invoices (dengan filter status)
  - Get Invoice Detail

### 6. Payment Verification (`Payment_Verification_Collection.json`)

Endpoints untuk payment verification (SuperAdmin only):
- **Payment Verification:**
  - Get Pending Payments (auto-save payment_id)
  - Get Payment Detail for Verification
  - Verify Payment (Approve) - activates subscription/invoice, sends email
  - Verify Payment (Reject) - rejects payment, sends email with reason

### 7. Company Management (`Company_Management_Collection.json`)

Endpoints untuk company, admin & participant management (SuperAdmin only):
- **Companies:**
  - List Companies (dengan filtering & pagination, includes counts)
  - Get Company (dengan admins, participants, subscriptions)
  - Create Company (auto-save ID)
  - Update Company
  - Toggle Company Active Status
  - Delete Company (soft delete)

- **Company Admins:**
  - List Company Admins (for a specific company)
  - Get Company Admin
  - Create Company Admin (auto-save ID, creates User with tenant_admin role)
  - Update Company Admin
  - Set Admin as Primary
  - Delete Company Admin (also deletes User)

- **Company Participants:**
  - List Company Participants (for a specific company, dengan pagination)
  - Get Company Participant (dengan test assignments & test sessions)

### 8. Participant Management (`Participant_Management_Collection.json`)

Endpoints untuk participant management (TenantAdmin only):
- **Participants:**
  - List Participants (dengan search & filter banned status, auto-save participant_id)
  - Create Participant with Test Assignments (create + assign tests + schedule dalam satu flow)
  - Get Participant Detail (dengan test assignments & history)
  - Update Participant
  - Delete Participant

- **Import:**
  - Preview Excel Import (preview data sebelum submit, returns valid & invalid rows)
  - Import Participants from Excel (import dengan test assignments & schedule)

- **Ban/Unban:**
  - Ban Participant (dengan optional reason)
  - Unban Participant

- **Test Assignment Emails:**
  - Resend Test Assignment Email (resend email untuk assignment tertentu)
  - Resend All Assignment Emails (resend semua emails untuk participant)

### 9. Public User Profile (`PublicUser_Profile_Collection.json`)

Endpoints untuk public user profile management:
- **Profile:**
  - Get Profile (dengan completion status)
  - Get Profile Completion Status (percentage, missing fields)
  - Complete Profile (first time setup)
  - Update Profile (partial update)
  - Change Password

### 10. Test Catalog (`PublicUser_TestCatalog_Collection.json`)

Endpoints untuk browsing test catalog (public - no auth required):
- **Test Catalog:**
  - Browse Tests (dengan filter category, search, pagination)
  - Get Test Details (dengan is_purchased status jika authenticated)
  - Get Test Categories (untuk filtering)

### 11. Shopping Cart (`PublicUser_Cart_Collection.json`)

Endpoints untuk shopping cart management (Public User only):
- **Cart:**
  - Get Cart (dengan items dan summary)
  - Add Test to Cart
  - Remove Test from Cart
  - Clear Cart

### 12. Checkout (`PublicUser_Checkout_Collection.json`)

Endpoints untuk checkout flow (Public User only):
- **Checkout:**
  - Verify Cart (check availability, calculate total)
  - Checkout (create transaction from cart, auto-save transaction_id)

### 13. Transactions (`PublicUser_Transaction_Collection.json`)

Endpoints untuk transaction management (Public User only):
- **Transactions:**
  - Get Transaction History (dengan optional status filter, auto-save transaction_id)
  - Get Transaction Details (dengan items dan payment info)
  - Get Purchased Tests (list semua test yang sudah dibeli)
- **Payments:**
  - Get Payment for Transaction (get payment details untuk transaction)
  - Upload Payment Proof (upload bukti pembayaran, payment auto-created saat checkout)

### 14. Participant Flow (`Participant_Flow_Collection.json`)

Endpoints untuk participant flow (No Login - menggunakan token dari email):
- **Assignment:**
  - Get Assignment by Token (public endpoint, no auth, auto-save assignment_token)
  - Get All Assignments (multi-test flow, requires token)
- **Biodata:**
  - Check Biodata Status (check if biodata is complete)
  - Complete Biodata (complete participant biodata)
- **Instructions:**
  - Get Test Instructions (get test instructions dengan assignment info)
- **Session:**
  - Start Test Session (start session untuk participant, auto-save session_token)

**Note:** Semua endpoint (kecuali Get Assignment by Token) memerlukan token di query parameter `?token={{assignment_token}}`

### 15. Test Session Management (`Test_Session_Collection.json`)

Endpoints untuk test session management (works untuk Participant & Public User):
- **Session:**
  - Get Session Details (get session dengan answers, test info, assignment info)
  - Auto-Save Answers (save answers periodically, format: { question_id: answer })
  - Submit Test (submit test session, marks as completed)
  - Update Time Spent (update time spent, called periodically)

**Note:** Menggunakan `session_token` dari start session endpoint

### 16. Anti-Cheat System (`AntiCheat_Collection.json`)

Endpoints untuk anti-cheat detection:
- **Cheat Detection:**
  - Log Cheat Event (log cheat event dari frontend, detection types: tab_switch, window_blur, keyboard_shortcut, right_click, copy_paste, time_anomaly, multiple_devices)
  - Get Cheat Detections (get all detections untuk session)

**Note:** Severity 1-5 (5 = auto-ban). Auto-ban juga terjadi jika 3+ detections same type atau 10+ total detections.

### 17. Photo Capture System (`PhotoCapture_Collection.json`)

Endpoints untuk photo capture:
- **Photos:**
  - Capture Photo (capture dan store photo untuk session, format: jpg/jpeg/png, max 2MB)
  - Get Photos (get all photos untuk session, ordered by capture time)

**Note:** Photos disimpan di `storage/test-sessions/photos/`

### 18. Public User Test Flow (`PublicUser_TestFlow_Collection.json`)

Endpoints untuk public user test flow (requires authentication):
- **My Tests:**
  - Get Available Tests (get purchased tests dengan payment verified, auto-save test_id)
  - Get Test Instructions (get instructions untuk purchased test)
  - Start Test Session (start session untuk purchased test, auto-save session_token)

**Note:** Setelah start session, gunakan `session_token` untuk Test Session Management endpoints

## 🔧 Environment Variables

| Variable | Description | Default Value |
|----------|-------------|--------------|
| `base_url` | Base URL API | `http://localhost:8000` |
| `auth_token` | General auth token | (auto-saved) |
| `super_admin_token` | Super admin token | (auto-saved) |
| `tenant_admin_token` | Tenant admin token | (auto-saved) |
| `public_user_token` | Public user token | (auto-saved) |
| `login_email` | Email for login | `superadmin@ruangtes.com` |
| `login_password` | Password for login | `password` |
| `verification_token` | Email verification token | (manual) |
| `reset_password_token` | Password reset token | (manual) |
| `test_category_id` | Test category ID for testing | (manual) |
| `test_id` | Test ID for testing | (manual) |
| `subscription_plan_id` | Subscription plan ID for testing | (auto-saved) |
| `subscription_price_id` | Subscription price ID for testing | (auto-saved) |
| `subscription_id` | Subscription ID for testing | (auto-saved) |
| `payment_id` | Payment ID for testing | (auto-saved) |
| `invoice_id` | Invoice ID for testing | (auto-saved) |
| `company_id` | Company ID for testing | (auto-saved) |
| `company_admin_id` | Company admin ID for testing | (auto-saved) |
| `participant_id` | Participant ID for testing | (auto-saved) |
| `test_assignment_id` | Test assignment ID for testing | (auto-saved) |

## 📝 Usage Tips

1. **Gunakan Environment Variables**: Semua URL dan token menggunakan environment variables untuk kemudahan switching antara development/staging/production.

2. **Auto-Save Token**: Setelah login, token akan otomatis tersimpan. Tidak perlu copy-paste manual.

3. **Role-Based Testing**: Token akan otomatis disimpan sesuai role, sehingga bisa langsung test endpoint yang memerlukan role tertentu.

4. **Update Base URL**: Jika API berjalan di port/domain berbeda, cukup update `base_url` di environment.

## 🔄 Update Collections

Jika ada perubahan API, update collection yang sesuai dan export ulang dari Postman.

## 📞 Support

Untuk pertanyaan atau issue, silakan hubungi tim development.
