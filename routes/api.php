<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register-company', [AuthController::class, 'registerCompany']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
    Route::post('/verify-email', [EmailVerificationController::class, 'verify']);
});

// Test Catalog (public - no auth required for browsing)
Route::prefix('tests/catalog')->group(function () {
    Route::get('/', [\App\Http\Controllers\Test\TestCatalogController::class, 'index']);
    Route::get('/categories', [\App\Http\Controllers\Test\TestCatalogController::class, 'categories']);
    Route::get('/{testId}', [\App\Http\Controllers\Test\TestCatalogController::class, 'show']);
});

// Participant Flow (public - no auth, uses token from email)
// Get assignment info by token (public, no middleware - token validation in controller)
Route::get('/participant/assignment/{token}', [\App\Http\Controllers\Participant\ParticipantFlowController::class, 'getAssignment']);

// Participant Flow routes (requires token validation middleware)
Route::prefix('participant')->middleware(\App\Http\Middleware\ValidateParticipantToken::class)->group(function () {
    // Get all assignments for participant (multi-test flow)
    Route::get('/assignments', [\App\Http\Controllers\Participant\ParticipantFlowController::class, 'getAssignments']);
    
    // Biodata management
    Route::get('/biodata/status', [\App\Http\Controllers\Participant\ParticipantFlowController::class, 'checkBiodata']);
    Route::post('/biodata/complete', [\App\Http\Controllers\Participant\ParticipantFlowController::class, 'completeBiodata']);
    
    // Test instructions
    Route::get('/instructions', [\App\Http\Controllers\Participant\ParticipantFlowController::class, 'getInstructions']);
    
    // Test Session Management
    Route::post('/session/start', [\App\Http\Controllers\TestSession\TestSessionController::class, 'start']);
});

// Test Session routes (public - uses session token)
Route::prefix('test-session')->group(function () {
    Route::get('/{sessionToken}', [\App\Http\Controllers\TestSession\TestSessionController::class, 'show']);
    Route::post('/{sessionToken}/save-answers', [\App\Http\Controllers\TestSession\TestSessionController::class, 'saveAnswers']);
    Route::post('/{sessionToken}/submit', [\App\Http\Controllers\TestSession\TestSessionController::class, 'submit']);
    Route::post('/{sessionToken}/update-time', [\App\Http\Controllers\TestSession\TestSessionController::class, 'updateTime']);
    
    // Anti-Cheat System
    Route::post('/{sessionToken}/cheat/log', [\App\Http\Controllers\AntiCheat\CheatDetectionController::class, 'logEvent']);
    Route::get('/{sessionToken}/cheat/detections', [\App\Http\Controllers\AntiCheat\CheatDetectionController::class, 'getDetections']);
    
    // Photo Capture System
    Route::post('/{sessionToken}/photo/capture', [\App\Http\Controllers\PhotoCapture\PhotoCaptureController::class, 'capture']);
    Route::get('/{sessionToken}/photos', [\App\Http\Controllers\PhotoCapture\PhotoCaptureController::class, 'getPhotos']);
});

// Public User Profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('/', [\App\Http\Controllers\Profile\ProfileController::class, 'show'])
            ->middleware('can:view_own_profile');
        Route::get('/completion-status', [\App\Http\Controllers\Profile\ProfileController::class, 'completionStatus'])
            ->middleware('can:view_own_profile');
        Route::post('/complete', [\App\Http\Controllers\Profile\ProfileController::class, 'complete'])
            ->middleware('can:manage_own_profile');
        Route::put('/update', [\App\Http\Controllers\Profile\ProfileController::class, 'update'])
            ->middleware('can:manage_own_profile');
        Route::patch('/update', [\App\Http\Controllers\Profile\ProfileController::class, 'update'])
            ->middleware('can:manage_own_profile');
        Route::post('/change-password', [\App\Http\Controllers\Profile\ProfileController::class, 'changePassword'])
            ->middleware('can:manage_own_profile');
    });

    // Cart (Public User only)
    Route::prefix('cart')->group(function () {
        Route::get('/', [\App\Http\Controllers\Cart\CartController::class, 'show'])
            ->middleware('can:view_cart');
        Route::post('/add', [\App\Http\Controllers\Cart\CartController::class, 'add'])
            ->middleware('can:manage_cart');
        Route::delete('/{testId}', [\App\Http\Controllers\Cart\CartController::class, 'remove'])
            ->middleware('can:manage_cart');
        Route::delete('/clear', [\App\Http\Controllers\Cart\CartController::class, 'clear'])
            ->middleware('can:manage_cart');
    });

    // Checkout (Public User only)
    Route::prefix('checkout')->group(function () {
        Route::get('/verify', [\App\Http\Controllers\Cart\CheckoutController::class, 'verify'])
            ->middleware('can:view_cart');
        Route::post('/', [\App\Http\Controllers\Cart\CheckoutController::class, 'checkout'])
            ->middleware('can:purchase_tests');
    });

    // Transactions (Public User only)
    Route::prefix('transactions')->group(function () {
        Route::get('/', [\App\Http\Controllers\Transaction\TransactionController::class, 'index'])
            ->middleware('can:view_transactions');
        Route::get('/purchased-tests', [\App\Http\Controllers\Transaction\TransactionController::class, 'purchasedTests'])
            ->middleware('can:view_transactions');
        Route::get('/{transactionId}', [\App\Http\Controllers\Transaction\TransactionController::class, 'show'])
            ->middleware('can:view_transactions');
    });

    // Public Payments (Public User only)
    Route::prefix('transactions/{transactionId}/payment')->group(function () {
        Route::get('/', [\App\Http\Controllers\Payment\PublicPaymentController::class, 'show'])
            ->middleware('can:view_transactions');
        Route::post('/upload-proof', [\App\Http\Controllers\Payment\PublicPaymentController::class, 'uploadProof'])
            ->middleware('can:purchase_tests');
    });

    // Public User Test Flow (Public User only)
    Route::prefix('my-tests')->group(function () {
        Route::get('/', [\App\Http\Controllers\PublicUser\PublicUserTestFlowController::class, 'getAvailableTests'])
            ->middleware('can:take_tests');
        Route::get('/{testId}/instructions', [\App\Http\Controllers\PublicUser\PublicUserTestFlowController::class, 'getInstructions'])
            ->middleware('can:take_tests');
        Route::post('/{testId}/start', [\App\Http\Controllers\PublicUser\PublicUserTestFlowController::class, 'startTest'])
            ->middleware('can:take_tests');
    });
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/resend-verification', [EmailVerificationController::class, 'resend']);
    });

    // Public User Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [\App\Http\Controllers\Profile\ProfileController::class, 'show'])
            ->middleware('can:view_own_profile');
        Route::get('/completion-status', [\App\Http\Controllers\Profile\ProfileController::class, 'completionStatus'])
            ->middleware('can:view_own_profile');
        Route::post('/complete', [\App\Http\Controllers\Profile\ProfileController::class, 'complete'])
            ->middleware('can:manage_own_profile');
        Route::put('/update', [\App\Http\Controllers\Profile\ProfileController::class, 'update'])
            ->middleware('can:manage_own_profile');
        Route::patch('/update', [\App\Http\Controllers\Profile\ProfileController::class, 'update'])
            ->middleware('can:manage_own_profile');
        Route::post('/change-password', [\App\Http\Controllers\Profile\ProfileController::class, 'changePassword'])
            ->middleware('can:manage_own_profile');
    });

    // Dashboard (both SuperAdmin and TenantAdmin use same endpoint, service handles role-based data)
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            return app(\App\Http\Controllers\Dashboard\SuperAdminDashboardController::class)->index();
        } elseif ($user->isTenantAdmin()) {
            return app(\App\Http\Controllers\Dashboard\TenantAdminDashboardController::class)->index();
        }
        
        return (new \App\Http\Resources\ErrorResource('Unauthorized', 403))->response();
    })->middleware('can:view_dashboard');

    // Users
    Route::get('users', [\App\Http\Controllers\User\UserController::class, 'index'])
        ->middleware('can:view_users');
    Route::post('users', [\App\Http\Controllers\User\UserController::class, 'store'])
        ->middleware('can:create_users');
    Route::get('users/{user}', [\App\Http\Controllers\User\UserController::class, 'show'])
        ->middleware('can:view_users');
    Route::put('users/{user}', [\App\Http\Controllers\User\UserController::class, 'update'])
        ->middleware('can:edit_users');
    Route::patch('users/{user}', [\App\Http\Controllers\User\UserController::class, 'update'])
        ->middleware('can:edit_users');
    Route::delete('users/{user}', [\App\Http\Controllers\User\UserController::class, 'destroy'])
        ->middleware('can:delete_users');

    // Test Categories
    Route::get('test-categories', [\App\Http\Controllers\Test\TestCategoryController::class, 'index'])
        ->middleware('can:view_test_categories');
    Route::post('test-categories', [\App\Http\Controllers\Test\TestCategoryController::class, 'store'])
        ->middleware('can:manage_test_categories');
    Route::get('test-categories/{category}', [\App\Http\Controllers\Test\TestCategoryController::class, 'show'])
        ->middleware('can:view_test_categories');
    Route::put('test-categories/{category}', [\App\Http\Controllers\Test\TestCategoryController::class, 'update'])
        ->middleware('can:manage_test_categories');
    Route::patch('test-categories/{category}', [\App\Http\Controllers\Test\TestCategoryController::class, 'update'])
        ->middleware('can:manage_test_categories');
    Route::delete('test-categories/{category}', [\App\Http\Controllers\Test\TestCategoryController::class, 'destroy'])
        ->middleware('can:manage_test_categories');

    // Tests
    Route::get('tests', [\App\Http\Controllers\Test\TestController::class, 'index'])
        ->middleware('can:view_tests');
    Route::post('tests', [\App\Http\Controllers\Test\TestController::class, 'store'])
        ->middleware('can:create_tests');
    Route::get('tests/{test}', [\App\Http\Controllers\Test\TestController::class, 'show'])
        ->middleware('can:view_tests');
    Route::put('tests/{test}', [\App\Http\Controllers\Test\TestController::class, 'update'])
        ->middleware('can:edit_tests');
    Route::patch('tests/{test}', [\App\Http\Controllers\Test\TestController::class, 'update'])
        ->middleware('can:edit_tests');
    Route::delete('tests/{test}', [\App\Http\Controllers\Test\TestController::class, 'destroy'])
        ->middleware('can:delete_tests');

    // Subscription Plans (SuperAdmin manage, TenantAdmin view available)
    Route::get('subscription-plans', [\App\Http\Controllers\Subscription\SubscriptionPlanController::class, 'index'])
        ->middleware('can:view_subscriptions');
    Route::post('subscription-plans', [\App\Http\Controllers\Subscription\SubscriptionPlanController::class, 'store'])
        ->middleware('can:manage_subscription_plans');
    Route::get('subscription-plans/{plan}', [\App\Http\Controllers\Subscription\SubscriptionPlanController::class, 'show'])
        ->middleware('can:view_subscriptions');
    Route::put('subscription-plans/{plan}', [\App\Http\Controllers\Subscription\SubscriptionPlanController::class, 'update'])
        ->middleware('can:manage_subscription_plans');
    Route::patch('subscription-plans/{plan}', [\App\Http\Controllers\Subscription\SubscriptionPlanController::class, 'update'])
        ->middleware('can:manage_subscription_plans');
    Route::delete('subscription-plans/{plan}', [\App\Http\Controllers\Subscription\SubscriptionPlanController::class, 'destroy'])
        ->middleware('can:manage_subscription_plans');

    // Subscription Prices (nested under plans)
    Route::get('subscription-plans/{plan}/prices', [\App\Http\Controllers\Subscription\SubscriptionPriceController::class, 'index'])
        ->middleware('can:view_subscriptions');
    Route::get('subscription-plans/{plan}/prices/all', [\App\Http\Controllers\Subscription\SubscriptionPriceController::class, 'getAllForPlan'])
        ->middleware('can:view_subscriptions');
    Route::post('subscription-prices', [\App\Http\Controllers\Subscription\SubscriptionPriceController::class, 'store'])
        ->middleware('can:manage_subscription_prices');
    Route::get('subscription-prices/{price}', [\App\Http\Controllers\Subscription\SubscriptionPriceController::class, 'show'])
        ->middleware('can:view_subscriptions');
    Route::put('subscription-prices/{price}', [\App\Http\Controllers\Subscription\SubscriptionPriceController::class, 'update'])
        ->middleware('can:manage_subscription_prices');
    Route::patch('subscription-prices/{price}', [\App\Http\Controllers\Subscription\SubscriptionPriceController::class, 'update'])
        ->middleware('can:manage_subscription_prices');
    Route::delete('subscription-prices/{price}', [\App\Http\Controllers\Subscription\SubscriptionPriceController::class, 'destroy'])
        ->middleware('can:manage_subscription_prices');

    // Subscriptions (TenantAdmin purchase & manage)
    Route::get('subscriptions/available-plans', [\App\Http\Controllers\Subscription\CompanySubscriptionController::class, 'availablePlans'])
        ->middleware('can:purchase_subscription');
    Route::post('subscriptions/purchase', [\App\Http\Controllers\Subscription\CompanySubscriptionController::class, 'purchase'])
        ->middleware('can:purchase_subscription');
    Route::get('subscriptions/active', [\App\Http\Controllers\Subscription\CompanySubscriptionController::class, 'active'])
        ->middleware('can:purchase_subscription');
    Route::get('subscriptions/history', [\App\Http\Controllers\Subscription\CompanySubscriptionController::class, 'history'])
        ->middleware('can:purchase_subscription');
    Route::post('subscriptions/{subscription}/purchase-quota', [\App\Http\Controllers\Subscription\CompanySubscriptionController::class, 'purchaseQuota'])
        ->middleware('can:purchase_subscription');
    Route::post('subscriptions/{subscription}/extend', [\App\Http\Controllers\Subscription\CompanySubscriptionController::class, 'extend'])
        ->middleware('can:purchase_subscription');
    Route::post('subscriptions/{subscription}/cancel', [\App\Http\Controllers\Subscription\CompanySubscriptionController::class, 'cancel'])
        ->middleware('can:purchase_subscription');

    // Payments - Specific routes must come before parameterized routes
    // Payment Verification (SuperAdmin only) - specific routes first
    Route::get('payments/all', [\App\Http\Controllers\Payment\PaymentVerificationController::class, 'index'])
        ->middleware('can:manage_payments');
    Route::get('payments/pending', [\App\Http\Controllers\Payment\PaymentVerificationController::class, 'pending'])
        ->middleware('can:manage_payments');
    Route::post('payments/verify', [\App\Http\Controllers\Payment\PaymentVerificationController::class, 'verify'])
        ->middleware('can:manage_payments');
    Route::get('payments/{payment}/verify', [\App\Http\Controllers\Payment\PaymentVerificationController::class, 'show'])
        ->middleware('can:manage_payments');

    // Payments (TenantAdmin) - specific routes first
    Route::get('payments', [\App\Http\Controllers\Payment\CompanyPaymentController::class, 'index'])
        ->middleware('can:purchase_subscription');
    Route::post('payments/upload-proof', [\App\Http\Controllers\Payment\CompanyPaymentController::class, 'uploadProof'])
        ->middleware('can:purchase_subscription');
    Route::get('payments/{payment}', [\App\Http\Controllers\Payment\CompanyPaymentController::class, 'show'])
        ->middleware('can:purchase_subscription');

    // Invoices
    Route::get('invoices', [\App\Http\Controllers\Invoice\InvoiceController::class, 'index'])
        ->middleware('can:view_invoices');
    Route::get('invoices/{invoice}', [\App\Http\Controllers\Invoice\InvoiceController::class, 'show'])
        ->middleware('can:view_invoices');

    // Participants (TenantAdmin & SuperAdmin - view)
    // SuperAdmin: can view all participants (optional filter by company_id)
    // TenantAdmin: can only view participants from their company
    Route::get('participants', [\App\Http\Controllers\Participant\ParticipantController::class, 'index'])
        ->middleware('can:view_participants');
    Route::get('participants/{participant}', [\App\Http\Controllers\Participant\ParticipantController::class, 'show'])
        ->middleware('can:view_participants');

    // Participants (TenantAdmin only - create/update/delete)
    Route::post('participants', [\App\Http\Controllers\Participant\ParticipantController::class, 'store'])
        ->middleware('can:create_participants');
    Route::put('participants/{participant}', [\App\Http\Controllers\Participant\ParticipantController::class, 'update'])
        ->middleware('can:edit_participants');
    Route::patch('participants/{participant}', [\App\Http\Controllers\Participant\ParticipantController::class, 'update'])
        ->middleware('can:edit_participants');
    Route::delete('participants/{participant}', [\App\Http\Controllers\Participant\ParticipantController::class, 'destroy'])
        ->middleware('can:delete_participants');
    Route::post('participants/import-preview', [\App\Http\Controllers\Participant\ParticipantController::class, 'previewImport'])
        ->middleware('can:import_participants');
    Route::post('participants/import', [\App\Http\Controllers\Participant\ParticipantController::class, 'import'])
        ->middleware('can:import_participants');
    Route::post('participants/{participant}/ban', [\App\Http\Controllers\Participant\ParticipantController::class, 'ban'])
        ->middleware('can:ban_participants');
    Route::post('participants/{participant}/unban', [\App\Http\Controllers\Participant\ParticipantController::class, 'unban'])
        ->middleware('can:ban_participants');

    // Test Assignments (TenantAdmin only)
    Route::post('test-assignments/{assignment}/resend-email', [\App\Http\Controllers\Participant\TestAssignmentController::class, 'resendEmail'])
        ->middleware('can:assign_tests');
    Route::post('participants/{participant}/resend-all-emails', [\App\Http\Controllers\Participant\TestAssignmentController::class, 'resendAllEmails'])
        ->middleware('can:assign_tests');

    // Companies (SuperAdmin only)
    Route::get('companies', [\App\Http\Controllers\Company\CompanyController::class, 'index'])
        ->middleware('can:view_companies');
    Route::post('companies', [\App\Http\Controllers\Company\CompanyController::class, 'store'])
        ->middleware('can:create_companies');
    Route::get('companies/{company}', [\App\Http\Controllers\Company\CompanyController::class, 'show'])
        ->middleware('can:view_companies');
    Route::put('companies/{company}', [\App\Http\Controllers\Company\CompanyController::class, 'update'])
        ->middleware('can:edit_companies');
    Route::patch('companies/{company}', [\App\Http\Controllers\Company\CompanyController::class, 'update'])
        ->middleware('can:edit_companies');
    Route::delete('companies/{company}', [\App\Http\Controllers\Company\CompanyController::class, 'destroy'])
        ->middleware('can:delete_companies');
    Route::post('companies/{company}/toggle-active', [\App\Http\Controllers\Company\CompanyController::class, 'toggleActive'])
        ->middleware('can:manage_companies');

    // Company Admins (nested under companies)
    Route::get('companies/{company}/admins', [\App\Http\Controllers\Company\CompanyAdminController::class, 'index'])
        ->middleware('can:manage_company_admins');
    Route::post('companies/{company}/admins', [\App\Http\Controllers\Company\CompanyAdminController::class, 'store'])
        ->middleware('can:manage_company_admins');
    Route::get('company-admins/{admin}', [\App\Http\Controllers\Company\CompanyAdminController::class, 'show'])
        ->middleware('can:manage_company_admins');
    Route::put('company-admins/{admin}', [\App\Http\Controllers\Company\CompanyAdminController::class, 'update'])
        ->middleware('can:manage_company_admins');
    Route::patch('company-admins/{admin}', [\App\Http\Controllers\Company\CompanyAdminController::class, 'update'])
        ->middleware('can:manage_company_admins');
    Route::delete('company-admins/{admin}', [\App\Http\Controllers\Company\CompanyAdminController::class, 'destroy'])
        ->middleware('can:manage_company_admins');
    Route::post('company-admins/{admin}/set-primary', [\App\Http\Controllers\Company\CompanyAdminController::class, 'setPrimary'])
        ->middleware('can:manage_company_admins');
});
