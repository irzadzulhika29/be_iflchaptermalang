<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Blog\BlogController;
use App\Http\Controllers\Blog\BlogCategoriesController;
use App\Http\Controllers\Blog\CommentController;
use App\Http\Controllers\Donation\CategoryController;
use App\Http\Controllers\Donation\CampaignController;
use App\Http\Controllers\Donation\DonationController;
use App\Http\Controllers\Donation\TransactionController;
use App\Http\Controllers\Volunteer\VolunteerRegistrationController;
use App\Http\Controllers\Event\EventController;
use App\Http\Controllers\Volunteer\ReferralCodeController;
use App\Http\Controllers\Admin\ReferralCodeController as AdminReferralCodeController;
use App\Http\Controllers\Admin\VolunteerRegistrationController as AdminVolunteerRegistrationController;
use App\Http\Controllers\Event\SdgController;

// testestes push
Route::prefix('v1')->group(function () {
  Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login'])->name('login');
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->name('verification.resend');
    Route::get('/google', [GoogleController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [GoogleController::class, 'handleGoogleCallback']);
    Route::get('/refresh-token', [LoginController::class, 'refreshToken']);
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);

    Route::middleware(['jwt.verify'])->group(function () {
      Route::post('/logout', [LoginController::class, 'logout']);
    });

    Route::get('/notice/emailNotVerified', [NoticeController::class, 'emailNotVerifiedNotice'])->name('email_not_verified.notice');
    Route::get('/notice/unauthorized', [NoticeController::class, 'unauthorizedNotice'])->name('unauthorized.notice');
  });

  Route::middleware(['jwt.verify', 'verified'])->group(function () {
    Route::group(['prefix' => 'profile'], function () {
      Route::get('/', [ProfileController::class, 'showProfile']);
      Route::put('/edit', [ProfileController::class, 'updateProfile']);
      Route::post('/update-password', [ProfileController::class, 'updatePassword']);
    });

    Route::get('/volunteer/referral-code/validate/{code}', [ReferralCodeController::class, 'validateReferralCode']);

    // Volunteer Registration Routes
    Route::middleware(['jwt.verify', 'verified'])->group(function () {
      Route::group(['prefix' => 'volunteer'], function () {
        Route::group(['prefix' => 'registration'], function () {
          Route::get('/form-data', [VolunteerRegistrationController::class, 'getFormData']);
          Route::post('/', [VolunteerRegistrationController::class, 'register']);
          Route::get('/my-registration', [VolunteerRegistrationController::class, 'getMyRegistration']);
        });
      });
    });


    Route::group(['prefix' => 'admin', 'middleware' => 'role:admin'], function () {
      Route::get('/referral-codes', [AdminReferralCodeController::class, 'index']);
      Route::get('/referral-codes/{id}', [AdminReferralCodeController::class, 'show']);
      Route::post('/referral-codes', [AdminReferralCodeController::class, 'store']);
      Route::put('/referral-codes/{id}', [AdminReferralCodeController::class, 'update']);
      Route::patch('/referral-codes/{id}', [AdminReferralCodeController::class, 'update']);
      Route::delete('/referral-codes/{id}', [AdminReferralCodeController::class, 'destroy']);
      Route::patch('/referral-codes/{id}/toggle-active', [AdminReferralCodeController::class, 'toggleActive']);
    });


    Route::group(['prefix' => 'supervisor', 'middleware' => 'role:admin,bismar,copywriter'], function () {
      Route::get('/', [UserController::class, 'getAllUsers']);
    });

    Route::group(['prefix' => 'shop-manager', 'middleware' => 'role:admin,bismar'], function () {
      Route::apiResource('/campaign', CampaignController::class)->except(['index', 'show']);
      Route::apiResource('/campaign/category', CategoryController::class)->except(['index']);
    });

    Route::group(['prefix' => 'copywriter', 'middleware' => 'role:admin,copywriter'], function () {
      Route::apiResource('/blog', BlogController::class)->except(['index', 'show']);
      Route::apiResource('/blog/category', BlogCategoriesController::class)->except(['index']);
    });


    Route::group(['prefix' => 'admin', 'middleware' => 'role:admin'], function () {
      Route::get('/volunteer-registrations', [AdminVolunteerRegistrationController::class, 'index']);
      Route::get('/volunteer-registrations/export', [AdminVolunteerRegistrationController::class, 'export']);
      Route::get('/volunteer-registrations/{id}', [AdminVolunteerRegistrationController::class, 'show']);
      Route::patch('/volunteer-registrations/{id}/status', [AdminVolunteerRegistrationController::class, 'updateStatus']);
      Route::post('/volunteer-registrations/bulk-update-status', [AdminVolunteerRegistrationController::class, 'bulkUpdateStatus']);
      Route::get('/events/{eventId}/volunteer-registrations', [AdminVolunteerRegistrationController::class, 'getByEvent']);

      Route::apiResource('role', RoleController::class);
      Route::get('/{id}', [UserController::class, 'getUserById']);
      Route::put('/{id}', [UserController::class, 'updateUser']);
      Route::delete('/{id}', [UserController::class, 'deleteUser']);

      // Donation management
      Route::get('/donations/pending', [DonationController::class, 'getPending']);
      Route::get('/donations/campaign/{campaignId}/total', [DonationController::class, 'getTotalDonationByCampaign']);
      Route::post('/donations/{id}/approve', [DonationController::class, 'approve']);
      Route::post('/donations/{id}/reject', [DonationController::class, 'reject']);
    });
  });

  Route::group(['prefix' => 'blog'], function () {
    // blog categories
    Route::group(['prefix' => 'category'], function () {
      Route::get('/', [BlogCategoriesController::class, 'index']);
    });

    // blog
    Route::get('/', [BlogController::class, 'index']);
    Route::post('/upload', [BlogController::class, 'upload']);
    Route::get('/{blog_slug}', [BlogController::class, 'show']);

    // blog comment
    Route::post('/{blog_id}/toggle-like', [BlogController::class, 'likeBlog']);
    Route::get('/{blog_id}/comment', [CommentController::class, 'viewComment']);
    Route::post('/{blog_id}/comment', [CommentController::class, 'addComment']);
    Route::post('/{blog_id}/comment/{comment_id}', [CommentController::class, 'replyComment']);
    Route::put('/{blog_id}/comment/{comment_id}', [CommentController::class, 'editComment']);
  });

  Route::group(['prefix' => 'comment'], function () {
    Route::post('/{comment_id}/toggle-like', [CommentController::class, 'likeComment']);
    Route::get('/{comment_id}', [CommentController::class, 'viewCommentById']);
    Route::delete('/{comment_id}', [CommentController::class, 'deleteComment']);
  });

  Route::group(['prefix' => 'campaign'], function () {
    // campaign categories
    Route::group(['prefix' => 'category'], function () {
      Route::get('/', [CategoryController::class, 'index']);
    });

    Route::get('/', [CampaignController::class, 'index']);
    Route::get('/total-donation', [CampaignController::class, 'getTotalDonation']);
    Route::get('/{campaign_slug}', [CampaignController::class, 'show']);
    Route::get('/{campaign_slug}/donation', [CampaignController::class, 'donation']);
    Route::post('/{campaign_slug}/donation', [DonationController::class, 'donate']);
    Route::get('/invoice/{campaign_id}', [TransactionController::class, 'invoice']);
  });

  Route::prefix('event')->group(function () {
    Route::get('/', [EventController::class, 'index']);
    Route::get('/{id}', [EventController::class, 'show']);

    Route::middleware('jwt.verify', 'role:admin')->group(function () {
      Route::post('/', [EventController::class, 'store']);
      Route::post('/{id}', [EventController::class, 'update']); // POST untuk support file upload
      Route::delete('/{id}', [EventController::class, 'destroy']);
    });
  });

  Route::prefix('transaction')->group(function () {
    Route::post('/create/{campaignSlug}', [TransactionController::class, 'createTransaction']);
    Route::post('/callback', [TransactionController::class, 'paymentCallback']);
    Route::get('/invoice/{id}', [TransactionController::class, 'invoice']);
    Route::get('/status/{id}', [TransactionController::class, 'checkStatus']);
  });



  Route::prefix('sdg')->group(function () {
    Route::get('/', [SdgController::class, 'index']);
    Route::get('/{id}', [SdgController::class, 'show']);
  });


  // Test Tripay payment
  // Route::get('/donation', [TransactionController::class, 'index']);
  // Route::post('/callback', [TransactionController::class, 'callback']);
  // Route::post('/donate', [TransactionController::class, 'proccess'])->name('proccess');
});
