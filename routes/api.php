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

    // Volunteer Registration Routes
    Route::group(['prefix' => 'volunteer'], function () {
      Route::group(['prefix' => 'registration'], function () {
        Route::get('/form-data', [VolunteerRegistrationController::class, 'getFormData']);
        Route::post('/', [VolunteerRegistrationController::class, 'register']);
        Route::get('/my-registration', [VolunteerRegistrationController::class, 'getMyRegistration']);
      });
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

  Route::prefix('transaction')->group(function () {
    Route::post('/create/{campaignSlug}', [TransactionController::class, 'createTransaction']);
    Route::post('/callback', [TransactionController::class, 'paymentCallback']);
    Route::get('/invoice/{id}', [TransactionController::class, 'invoice']);
    Route::get('/status/{id}', [TransactionController::class, 'checkStatus']);
  });


  // Test Tripay payment
  // Route::get('/donation', [TransactionController::class, 'index']);
  // Route::post('/callback', [TransactionController::class, 'callback']);
  // Route::post('/donate', [TransactionController::class, 'proccess'])->name('proccess');
});
