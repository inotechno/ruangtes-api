---
name: RuangTes SaaS Application Plan
overview: Membangun aplikasi SaaS RuangTes untuk psikotes online dengan multi-tenant architecture, sistem subscription, real-time monitoring, dan anti-cheat detection menggunakan Laravel 12, PostgreSQL, Redis, dan Laravel WebSockets.
todos:
  - id: setup-dependencies
    content: ""
    status: pending
  - id: database-schema
    content: ""
    status: pending
  - id: models-relationships
    content: Create all Eloquent models dengan relationships (polymorphic untuk users, company subscriptions, test sessions, dll)
    status: pending
    dependencies:
      - database-schema
  - id: enums-constants
    content: Create Enums untuk UserRole, TestType, SubscriptionStatus, TransactionStatus, PaymentStatus, CheatDetectionType
    status: pending
  - id: permissions-setup
    content: "Setup Spatie Permissions dengan roles (super_admin, tenant_admin, public_user) dan semua permissions. Note: Participant tidak menggunakan role/permission karena tidak login, hanya token-based access"
    status: pending
    dependencies:
      - setup-dependencies
  - id: auth-system
    content: ""
    status: completed
    dependencies:
      - models-relationships
      - permissions-setup
  - id: superadmin-dashboard
    content: Create SuperAdmin dashboard dengan statistics, analytics, dan overview metrics
    status: completed
    dependencies:
      - auth-system
  - id: superadmin-tests
    content: Implement SuperAdmin test & category management (CRUD dengan instruction_route dan test_route fields)
    status: completed
    dependencies:
      - superadmin-dashboard
  - id: superadmin-subscription
    content: Implement SuperAdmin subscription plan & pricing management (CRUD untuk plans, pricing tiers, custom options)
    status: completed
    dependencies:
      - superadmin-dashboard
  - id: superadmin-companies
    content: Implement SuperAdmin company & participant management (CRUD, view details, manage admins)
    status: completed
    dependencies:
      - superadmin-dashboard
  - id: subscription-system
    content: ""
    status: completed
    dependencies:
      - superadmin-subscription
  - id: payment-system
    content: Implement payment system dengan manual verification, prepare abstraction layer untuk future payment gateway integration
    status: completed
    dependencies:
      - subscription-system
  - id: tenantadmin-dashboard
    content: Create TenantAdmin dashboard dengan company-specific analytics dan statistics
    status: completed
    dependencies:
      - auth-system
      - subscription-system
  - id: tenantadmin-subscription
    content: Implement TenantAdmin subscription purchase, view active subscription, transaction history
    status: pending
    dependencies:
      - tenantadmin-dashboard
      - subscription-system
  - id: participant-management
    content: ""
    status: pending
    dependencies:
      - tenantadmin-dashboard
  - id: test-assignment-email
    content: ""
    status: completed
    dependencies:
      - participant-management
  - id: public-user-registration
    content: Implement public user registration, email verification, profile completion flow
    status: pending
    dependencies:
      - auth-system
  - id: public-user-catalog
    content: Implement public user test catalog browsing, shopping cart, checkout flow
    status: pending
    dependencies:
      - public-user-registration
  - id: public-user-payment
    content: Implement public user payment flow dan transaction history
    status: pending
    dependencies:
      - public-user-catalog
      - payment-system
  - id: participant-flow
    content: ""
    status: pending
    dependencies:
      - test-assignment-email
  - id: test-session-management
    content: ""
    status: pending
    dependencies:
      - participant-flow
      - public-user-payment
  - id: anti-cheat-frontend
    content: ""
    status: pending
    dependencies:
      - test-session-management
  - id: anti-cheat-backend
    content: ""
    status: completed
    dependencies:
      - test-session-management
  - id: photo-capture
    content: ""
    status: completed
    dependencies:
      - test-session-management
  - id: websockets-setup
    content: ""
    status: pending
    dependencies:
      - setup-dependencies
  - id: realtime-monitoring
    content: ""
    status: pending
    dependencies:
      - websockets-setup
      - photo-capture
      - anti-cheat-backend
  - id: tenantadmin-monitoring
    content: ""
    status: pending
    dependencies:
      - realtime-monitoring
  - id: superadmin-monitoring
    content: Implement SuperAdmin monitoring untuk public users (view photos & history setelah selesai, atau real-time - tergantung resource consideration)
    status: pending
    dependencies:
      - realtime-monitoring
  - id: test-results-calculation
    content: ""
    status: pending
    dependencies:
      - test-session-management
  - id: test-results-viewing
    content: ""
    status: pending
    dependencies:
      - test-results-calculation
  - id: api-structure
    content: ""
    status: completed
  - id: middleware-security
    content: ""
    status: completed
    dependencies:
      - permissions-setup
      - subscription-system
  - id: error-handling
    content: ""
    status: pending
    dependencies:
      - api-structure
  - id: seeders-factories
    content: ""
    status: completed
    dependencies:
      - models-relationships
      - permissions-setup
---

# RuangTes SaaS Application - Implementation Plan

## Architecture Overview

Aplikasi multi-tenant SaaS dengan 4 role utama: SuperAdmin, TenantAdmin, PublicUser, dan Participant. **Penting**: Participant tidak memerlukan login - hanya akses via unique token/link yang dikirim via email. Menggunakan hybrid approach untuk test structure (hardcoded routes dengan metadata di database), Laravel WebSockets untuk real-time monitoring, dan Spatie Permissions untuk role management.

## Database Schema Design

### Core Tables

**Users & Authentication:**

- `users` - Base user table dengan polymorphic relationship untuk authenticated roles (SuperAdmin, TenantAdmin, PublicUser)
- `companies` - Data perusahaan/tenant
- `company_admins` - Admin perusahaan (polymorphic dari users)
- `public_users` - Public user data (polymorphic dari users)
- `participants` - Participant data (STANDALONE, tidak polymorphic dari users karena tidak perlu login. Fields: id, company_id, name, email, unique_token, biodata JSON, banned_at, timestamps)
- `email_verifications` - Token verifikasi email

**Subscription & Billing:**

- `subscription_plans` - Paket langganan (3/6/12 bulan dengan berbagai user quota)
- `subscription_prices` - Harga untuk setiap paket dan jumlah user
- `company_subscriptions` - Subscription aktif perusahaan
- `subscription_transactions` - Transaksi subscription (pre-paid)
- `user_quota_purchases` - Pembelian tambahan quota user
- `subscription_extensions` - Perpanjangan masa aktif
- `invoices` - Invoice untuk post-paid billing
- `payments` - Data pembayaran (manual verification)

**Tests & Categories:**

- `test_categories` - Kategori tes
- `tests` - Master data tes (nama, kode, price, durasi, type, instruction_route, test_route, dll)
- `test_metadata` - Metadata tambahan tes (JSON)

**Public User Transactions:**

- `carts` - Keranjang belanja public user
- `cart_items` - Item dalam keranjang
- `public_transactions` - Transaksi pembelian tes oleh public user
- `public_transaction_items` - Item dalam transaksi

**Test Assignments & Sessions:**

- `test_assignments` - Assignment tes untuk participant (dengan unique_token untuk akses tanpa login, relasi ke participants table via participant_id)
- `test_sessions` - Session pengerjaan tes (menggunakan polymorphic relationship: testable_type/testable_id untuk participant atau user/public_user)
- `test_session_answers` - Jawaban yang diisi
- `test_session_photos` - Foto monitoring selama tes
- `test_session_events` - Event kecurangan yang terdeteksi
- `test_results` - Hasil tes yang sudah selesai

**Monitoring & Anti-Cheat:**

- `monitoring_sessions` - Active monitoring session
- `cheat_detections` - Deteksi kecurangan
- `banned_participants` - Participant yang di-ban

**Settings & Menus:**

- `settings` - System settings
- `menus` - Menu management
- `permissions` - Spatie permissions (via package)

## Application Structure

### Directory Structure

```javascript
app/
├── Enums/
│   ├── UserRole.php
│   ├── TestType.php
│   ├── SubscriptionStatus.php
│   ├── TransactionStatus.php
│   ├── PaymentStatus.php
│   └── CheatDetectionType.php
├── Models/
│   ├── User.php (polymorphic untuk authenticated users)
│   ├── Company.php
│   ├── CompanyAdmin.php
│   ├── PublicUser.php
│   ├── Participant.php (STANDALONE model, tidak extends Authenticatable, tidak polymorphic dari User)
│   ├── Test.php
│   ├── TestCategory.php
│   ├── SubscriptionPlan.php
│   ├── CompanySubscription.php
│   ├── TestSession.php
│   └── ... (all models)
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── AuthController.php
│   │   │   ├── EmailVerificationController.php
│   │   │   └── PasswordResetController.php
│   │   ├── Dashboard/
│   │   │   ├── DashboardController.php
│   │   │   └── AnalyticsController.php
│   │   ├── Test/
│   │   │   ├── TestController.php
│   │   │   ├── TestCategoryController.php
│   │   │   ├── TestSessionController.php
│   │   │   └── TestResultController.php
│   │   ├── Subscription/
│   │   │   ├── SubscriptionPlanController.php
│   │   │   ├── SubscriptionController.php
│   │   │   ├── InvoiceController.php
│   │   │   └── PaymentController.php
│   │   ├── Company/
│   │   │   ├── CompanyController.php
│   │   │   └── CompanyProfileController.php
│   │   ├── Participant/
│   │   │   ├── ParticipantController.php
│   │   │   ├── ParticipantAssignmentController.php
│   │   │   └── ParticipantAccessController.php (untuk akses via unique token, tanpa login)
│   │   ├── Cart/
│   │   │   ├── CartController.php
│   │   │   └── CheckoutController.php
│   │   ├── Transaction/
│   │   │   ├── TransactionController.php
│   │   │   └── PublicTransactionController.php
│   │   ├── Monitoring/
│   │   │   ├── MonitoringController.php
│   │   │   └── CheatDetectionController.php
│   │   ├── Menu/
│   │   │   └── MenuController.php
│   │   └── Setting/
│   │       └── SettingController.php
│   ├── Requests/
│   │   ├── Auth/
│   │   ├── Test/
│   │   ├── Subscription/
│   │   ├── Company/
│   │   ├── Participant/
│   │   ├── Cart/
│   │   ├── Transaction/
│   │   └── Monitoring/
│   ├── Resources/
│   │   └── (API Resources untuk response)
│   └── Middleware/
│       ├── CheckRole.php
│       ├── CheckSubscription.php
│       ├── PreventCheating.php
│       └── ValidateParticipantToken.php (untuk validasi unique token participant)
├── Services/
│   ├── Auth/
│   │   ├── AuthService.php
│   │   └── EmailVerificationService.php
│   ├── Subscription/
│   │   ├── SubscriptionService.php
│   │   ├── BillingService.php
│   │   └── InvoiceService.php
│   ├── Test/
│   │   ├── TestService.php
│   │   ├── TestSessionService.php
│   │   └── TestResultService.php
│   ├── Participant/
│   │   ├── ParticipantImportService.php
│   │   └── ParticipantAssignmentService.php
│   ├── Monitoring/
│   │   ├── MonitoringService.php
│   │   ├── CheatDetectionService.php
│   │   └── PhotoCaptureService.php
│   ├── Payment/
│   │   └── PaymentService.php (prepared for future gateway)
│   └── Notification/
│       └── NotificationService.php
├── Events/
│   ├── TestSessionStarted.php
│   ├── TestSessionCompleted.php
│   ├── CheatDetected.php
│   └── ParticipantBanned.php
├── Listeners/
│   ├── SendTestAssignmentEmail.php
│   ├── CaptureMonitoringPhoto.php
│   └── LogCheatDetection.php
├── Broadcasts/
│   └── MonitoringChannel.php (WebSocket channels)
├── Jobs/
│   ├── ProcessTestResult.php
│   ├── SendTestAssignmentEmails.php
│   └── GenerateInvoice.php
└── Exceptions/
    └── Custom exception handlers
```



## Implementation Phases

### Phase 1: Foundation & Authentication

1. Install dependencies (Spatie Permissions, Laravel WebSockets, dll)
2. Setup database migrations untuk core tables
3. Implement authentication system dengan email verification
4. Setup Spatie Permissions dengan roles dan permissions
5. Create User model dengan polymorphic relationships
6. Implement registration flow untuk Company + Admin
7. Implement login dengan role-based redirection

### Phase 2: SuperAdmin Features

1. Dashboard dengan statistics & analytics
2. Test & Category management (CRUD)
3. Subscription plan & pricing management
4. Company & participant management
5. Transaction management
6. Menu management
7. Settings management
8. Monitoring dashboard untuk public users
9. Test results management

### Phase 3: Subscription & Billing System

1. Subscription plan CRUD dengan pricing tiers
2. Pre-paid subscription flow (pilih paket → bayar → aktif)
3. Post-paid subscription flow (paket → invoice di akhir periode)
4. User quota purchase system
5. Subscription extension system
6. Invoice generation & management
7. Payment verification system (manual, prepared for gateway)
8. Subscription status tracking & auto-expiry

### Phase 4: TenantAdmin Features

1. Dashboard dengan company-specific analytics
2. Subscription purchase & management
3. Transaction history
4. Available tests listing
5. Participant management (CRUD, import, preview)
6. Test assignment dengan periode & email notification
7. Real-time monitoring dashboard
8. Test results viewing & management
9. Company profile management
10. Participant banning system

### Phase 5: Public User Features

1. Registration & email verification
2. Profile completion
3. Test catalog browsing
4. Shopping cart system
5. Checkout & payment flow
6. Transaction history
7. Purchased tests listing
8. Test taking flow
9. Test results viewing

### Phase 6: Participant Flow (No Login Required)

1. Participant link access via unique token (tidak perlu login/authentication)
2. Token validation middleware untuk verify assignment & check banned status
3. Biodata completion page (store di session atau langsung ke database)
4. Test instruction pages
5. Test taking interface dengan anti-cheat
6. Multi-test flow (test 1 → test 2 → ...)
7. Completion confirmation
8. Session management tanpa authentication (gunakan unique token sebagai identifier)

### Phase 7: Test Taking & Anti-Cheat System

1. Test session management
2. Browser lock mechanism (prevent tab switching, minimize, dll)
3. Random photo capture during test
4. Keyboard shortcut detection (Alt+Tab, Ctrl+W, dll)
5. Window focus/blur detection
6. Cheat event logging
7. Auto-ban mechanism
8. Answer saving (auto-save & manual submit)

### Phase 8: Real-Time Monitoring

1. Laravel WebSockets setup
2. Monitoring channel untuk TenantAdmin & SuperAdmin
3. Real-time test session status updates
4. Real-time photo streaming
5. Real-time cheat detection alerts
6. Active participant tracking

### Phase 9: Test Results & Analytics

1. Test result calculation (per test type)
2. Result storage & retrieval
3. Analytics dashboard
4. Export functionality (PDF/Excel)
5. Comparison & reporting

### Phase 10: API Structure & Error Handling

1. Standardized API response format
2. Custom exception handlers
3. Request validation classes
4. API Resource classes
5. Error logging & monitoring
6. Rate limiting

## Key Technical Decisions

### Test Structure (Hybrid Approach)

- Setiap test memiliki route tersendiri: `/tests/{test_code}/instruction` dan `/tests/{test_code}/take`
- Metadata test (durasi, jumlah soal, dll) disimpan di database
- Test-specific logic di controller/service terpisah
- Field `instruction_route` dan `test_route` di table `tests` untuk routing

### Multi-Tenant Architecture

- Company-based isolation menggunakan `company_id` di queries
- Middleware untuk auto-scope queries berdasarkan company
- Subscription-based feature access

### Real-Time Monitoring

- Laravel WebSockets untuk broadcasting
- Private channels per company untuk TenantAdmin
- Public channel untuk SuperAdmin
- Photo capture setiap 30-60 detik secara random
- Event-driven updates untuk cheat detection

### Anti-Cheat Strategy

- Frontend: JavaScript untuk detect tab switching, keyboard shortcuts, window focus
- Backend: Validate session integrity, track time spent, detect anomalies
- Photo evidence untuk audit trail
- Progressive warnings → auto-ban

### Payment System

- Manual verification untuk sekarang
- Service layer abstraction untuk future payment gateway integration
- Invoice system untuk post-paid companies
- Payment proof upload & verification workflow

## Files to Create

### Core Configuration

- `config/permissions.php` - Permission definitions
- `config/websockets.php` - WebSocket configuration
- `config/subscription.php` - Subscription settings

### Database Migrations (Priority Order)

1. `create_users_table` (untuk authenticated users: SuperAdmin, TenantAdmin, PublicUser)
2. `create_companies_table`
3. `create_company_admins_table`
4. `create_public_users_table`
5. `create_participants_table` (STANDALONE, tidak ada relasi ke users. Fields: company_id, name, email, unique_token, biodata JSON, banned_at, timestamps)
6. `create_test_categories_table`
7. `create_tests_table`
8. `create_subscription_plans_table`
9. `create_subscription_prices_table`
10. `create_company_subscriptions_table`
11. `create_test_assignments_table` (participant_id foreign key ke participants, unique_token)
12. `create_test_sessions_table` (polymorphic: testable_type/testable_id untuk participant atau user)
13. `create_carts_table`
14. `create_public_transactions_table`
15. `create_monitoring_sessions_table`
16. `create_test_session_photos_table`
17. `create_cheat_detections_table`
18. `create_test_results_table`
19. `create_invoices_table`
20. `create_payments_table`

### Seeders

- `PermissionSeeder` - Setup roles & permissions
- `SuperAdminSeeder` - Create initial super admin
- `TestCategorySeeder` - Sample categories
- `SubscriptionPlanSeeder` - Default subscription plans

## Security Considerations

1. **API Authentication**: Sanctum tokens untuk SuperAdmin, TenantAdmin, PublicUser
2. **Participant Access**: Unique token-based (tidak perlu login), token di-generate per assignment dan dikirim via email
3. **Role-based Access**: Spatie Permissions middleware untuk authenticated users
4. **Subscription Validation**: Middleware untuk check active subscription
5. **Test Session Security**: Unique tokens untuk participant, Sanctum + session untuk public user, time-based validation
6. **Anti-Cheat**: Multiple detection layers
7. **Data Isolation**: Company-scoped queries
8. **Rate Limiting**: Per endpoint & per user/token

## Performance Optimizations

1. Redis untuk caching (subscription status, test metadata)
2. Queue jobs untuk heavy operations (email sending, result calculation)
3. Database indexing pada foreign keys & frequently queried fields
4. Eager loading untuk relationships
5. API response pagination
6. Photo storage optimization (compression, lazy loading)

## Testing Strategy

1. Unit tests untuk Services
2. Feature tests untuk API endpoints