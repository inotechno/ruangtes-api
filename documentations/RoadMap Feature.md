# Roadmap Fitur Selanjutnya

## Phase 1: Participant & Test Assignment (Prioritas Tinggi)

1. **Participant Management** ✅ **SELESAI**
   - ✅ CRUD participants
   - ✅ Import Excel dengan preview
   - ✅ Create participant + assign tests + schedule dalam satu flow
   - ✅ Ban/Unban participants
   - ✅ View participant details & history
   - ✅ SuperAdmin dapat melihat semua participants (dengan filter company)

2. **Test Assignment Email System** ✅ **SELESAI**
   - ✅ Generate unique token per assignment
   - ✅ Email template untuk test assignment
   - ✅ Email berisi link unik dengan token
   - ✅ Resend assignment email
   - ⏳ Track email status (sent, opened, clicked) - *Belum diimplementasikan*

---

## Phase 2: Public User Features (Paralel dengan Phase 3)

3. **Public User Registration & Profile** ✅ **SELESAI**
   - ✅ Public user registration
   - ✅ Email verification
   - ✅ Profile completion flow
   - ✅ Profile management (update profile, change password)
   - ✅ Profile completion status tracking

4. **Public User Test Catalog** ✅ **SELESAI**
   - ✅ Browse available tests (dengan filter category, search)
   - ✅ Test details & pricing (dengan is_purchased status)
   - ✅ Shopping cart (add, remove, clear, view)
   - ✅ Checkout flow (verify cart, create transaction)

5. **Public User Payment** ✅ **SELESAI**
   - ✅ Payment flow untuk test purchase (auto-create payment saat checkout)
   - ✅ Upload payment proof
   - ✅ Transaction history (dengan status filter)
   - ✅ Purchased tests listing
   - ✅ Payment verification (SuperAdmin) - sudah support public transactions

---

## Phase 3: Test Taking Flow (Setelah Participant Management)

6. **Participant Flow (No Login)** ✅ **SELESAI**
   - ✅ Access via unique link/token
   - ✅ Token validation middleware
   - ✅ Biodata completion page
   - ✅ Test instruction pages
   - ✅ Multi-test flow navigation

7. **Test Session Management** ✅ **SELESAI**
   - ✅ Start test session
   - ✅ Auto-save answers (periodic)
   - ✅ Manual submit
   - ✅ Multi-test flow handling
   - ✅ Session state management

8. **Anti-Cheat System (Frontend)** ⏳ **BELUM** (Frontend implementation)
   - ⏳ Browser lock (prevent tab switch)
   - ⏳ Keyboard shortcut detection
   - ⏳ Window focus/blur detection
   - ⏳ Prevent right-click
   - ⏳ Copy-paste prevention

9. **Anti-Cheat System (Backend)** ✅ **SELESAI**
   - ✅ Session validation
   - ✅ Time tracking & anomaly detection
   - ✅ Cheat event logging
   - ✅ Auto-ban mechanism
   - ✅ Pattern detection

10. **Photo Capture System** ✅ **SELESAI**
    - ✅ Photo capture endpoint
    - ✅ Store photos
    - ✅ Associate dengan test session
    - ✅ Photo gallery per session
    - ⏳ Random photo capture (30-60 detik interval) - *Frontend implementation*

---

## Phase 4: Real-Time Monitoring (Setelah Phase 3)

11. **WebSockets Setup** ⏳ **BELUM**
    - ⏳ Laravel WebSockets installation
    - ⏳ Channel configuration
    - ⏳ Authentication untuk WebSocket

12. **Real-Time Monitoring** ⏳ **BELUM**
    - ⏳ Broadcast session status
    - ⏳ Photo updates broadcast
    - ⏳ Cheat detection alerts
    - ⏳ Active participant tracking

13. **TenantAdmin Monitoring Dashboard** ⏳ **BELUM**
    - ⏳ View active participants (real-time)
    - ⏳ See photos (real-time)
    - ⏳ View answers (real-time)
    - ⏳ History tracking
    - ⏳ Ban participants (real-time)

14. **SuperAdmin Monitoring** ⏳ **BELUM**
    - ⏳ View public user test sessions
    - ⏳ View photos & history
    - ⏳ Monitoring dashboard

---

## Phase 5: Test Results (Setelah Test Session)

15. **Test Results Calculation** ⏳ **BELUM**
    - ⏳ Per test type calculation logic
    - ⏳ Store results
    - ⏳ Retrieve results
    - ⏳ Result validation

16. **Test Results Viewing** ⏳ **BELUM**
    - ⏳ TenantAdmin: view participant results
    - ⏳ PublicUser: view own results
    - ⏳ SuperAdmin: view all results
    - ⏳ Export results (PDF/Excel)

---

## Status Summary

### ✅ Selesai (9 fitur utama)
1. **Participant Management** - Full CRUD, Import, Ban/Unban, SuperAdmin view
2. **Test Assignment Email System** - Email sending, templates, resend functionality
3. **Public User Registration & Profile** - Registration, verification, profile completion & management
4. **Public User Test Catalog** - Browse tests, cart, checkout flow
5. **Public User Payment** - Payment flow, upload proof, transaction history, purchased tests
6. **Participant Flow (No Login)** - Token access, biodata, instructions, multi-test navigation
7. **Test Session Management** - Start session, auto-save, submit, session state
8. **Anti-Cheat System (Backend)** - Session validation, time tracking, cheat logging, auto-ban
9. **Photo Capture System** - Photo capture, storage, gallery per session

### ⏳ Belum (7 fitur utama)
- Anti-Cheat System (Frontend) - *Frontend implementation*
- WebSockets Setup
- Real-Time Monitoring
- TenantAdmin Monitoring Dashboard
- SuperAdmin Monitoring
- Test Results Calculation
- Test Results Viewing

---

## Urutan Rekomendasi

**Immediate next (setelah Participant Management):**
- ✅ Test Assignment Email System — **SELESAI**
- ⏳ Participant Flow — core feature untuk test taking
- ⏳ Test Session Management — foundation untuk anti-cheat & monitoring

**Setelah itu (dapat paralel):**
- ⏳ Public User Features (Catalog, Payment) — **Profile sudah selesai**
- ⏳ Anti-Cheat System (Frontend + Backend)
- ⏳ Photo Capture System

**Final Phase:**
- ⏳ WebSockets & Real-Time Monitoring
- ⏳ Test Results Calculation & Viewing

---

## Rekomendasi Prioritas

**Fokus pada:**
- ✅ Participant Management — **SELESAI**
- ✅ Test Assignment Email System — **SELESAI**
- ⏳ Participant Flow — **SELANJUTNYA**
- ⏳ Test Session Management

**Progress: 9 dari 16 fitur utama (56.25%)**
