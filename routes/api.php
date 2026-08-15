<?php

use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MessageBundleController;
use App\Http\Controllers\Api\SupportRequestController;
use App\Http\Controllers\Api\InteractionController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\VerificationPaymentController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::get('check-identity', [AuthController::class, 'checkIdentity']);
    Route::post('password/forgot', [AuthController::class, 'forgotPassword'])->name('password.forgot');
    Route::post('/verify-reset-otp', [AuthController::class,'verifyResetOtp']);
    Route::post('password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');

    Route::post('delete/account', [AuthController::class, 'deleteAccount']);

    Route::post('google/exchange', [GoogleAuthController::class, 'exchange']);
    Route::post('google/complete', [GoogleAuthController::class, 'completeRegistration']);

    //Route::post('/registration', [GoogleAuthController::class, 'registration']);
});

Route::prefix('otp')->group(function () {
    Route::post('generate-login', [OtpController::class, 'generateLogin']);
    Route::post('login', [OtpController::class, 'login']);
});

Route::get('/countries', [ConfigController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Public Feed
|--------------------------------------------------------------------------
*/

Route::get('discover/feed', [ProfileController::class, 'discoverFeed']);
Route::get('stories', [StoryController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Campaigns
|--------------------------------------------------------------------------
*/

Route::prefix('campaigns')->group(function () {
    //Route::get('/', [CampaignController::class, 'index']);
    //Route::get('/{campaign}', [CampaignController::class, 'show']);
    Route::post('/{campaign}/view', [CampaignController::class, 'view']);
    Route::post('/{campaign}/click', [CampaignController::class, 'click']);
});

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    Route::get('me', [ProfileController::class, 'me']);
    Route::patch('me', [ProfileController::class, 'update']);
    Route::patch('me/password', [ProfileController::class, 'updatePassword']);
    Route::post('me/profile-photo', [ProfileController::class, 'uploadPhoto']);
    Route::delete('me/profile-photo/{photo}', [ProfileController::class, 'deletePhoto']);


    /*
    |--------------------------------------------------------------------------
    | Stories
    |--------------------------------------------------------------------------
    */

    Route::get('me/stories', [StoryController::class, 'mine']);
    Route::get('me/stories/delete-expired', [StoryController::class, 'deleteExpiredStories']);
    Route::post('me/stories', [StoryController::class, 'store']);
    Route::delete('me/stories/{storyMedia}', [StoryController::class, 'destroy']);


    /*
    |--------------------------------------------------------------------------
    | Interactions
    |--------------------------------------------------------------------------
    */

    Route::get('profiles/liked-me', [ProfileController::class, 'likedMe']);
    Route::post('likes', [InteractionController::class, 'like']);
    Route::post('pops', [InteractionController::class, 'pop']);
    Route::post('dismiss-matches', [InteractionController::class, 'decline']);
    Route::get('likes/pending/count', [InteractionController::class, 'pendingLikesCount']);

    /*
    |--------------------------------------------------------------------------
    | Conversations
    |--------------------------------------------------------------------------
    */

    Route::get('matches', [ConversationController::class, 'matches']);
    Route::get('conversations', [ConversationController::class, 'index']);
    Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
    Route::post('conversations/{conversation}/messages', [ConversationController::class, 'storeMessage']);
    Route::post('conversations/{conversation}/read', [ConversationController::class, 'markAsRead']);
    //Route::delete('/conversations/{conversation}/messages/{message}', [ConversationController::class, 'deleteMessage']);
    //Route::delete('/conversations/{conversation}', [ConversationController::class, 'deleteConversation']);

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    /*
    |--------------------------------------------------------------------------
    | Marketplace / Credits
    |--------------------------------------------------------------------------
    */

    Route::get('message-bundles', [MessageBundleController::class, 'index']);
    Route::post('message-bundles/{messageBundle}/purchase', [MessageBundleController::class, 'purchaseBundle']);
    Route::post('message-bundle-requests', [MessageBundleController::class, 'requestBundle']);
    Route::post('message-bundles/free', [MessageBundleController::class, 'purchaseFreeBundle']);
    Route::get('marketplace-items', [MarketplaceController::class, 'index']);

    Route::post('payments/initiate', [MessageBundleController::class, 'initiate']);

    Route::post('support-requests', [SupportRequestController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | Push notifications
    |--------------------------------------------------------------------------
    */

    Route::post('me/push-token', [AuthController::class, 'savePushToken']);

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    */

    Route::post('/broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    });

    /*
    |--------------------------------------------------------------------------
    | Support Conversation
    |--------------------------------------------------------------------------
    */

    Route::get('support/conversation', [SupportRequestController::class, 'conversation']);
    Route::post('support/request/client', [SupportRequestController::class, 'storeRequestClient']);

    /*
    |--------------------------------------------------------------------------
    | Verification d'identité pour certifier son compte
    |--------------------------------------------------------------------------
    */

    Route::post('/verification-payments', [VerificationPaymentController::class, 'initiate']);

});

Route::post('/payments/callback/{reference}', [MessageBundleController::class, 'callback'])->name('payments.callback');
Route::get('/payments/approved/{reference}', [MessageBundleController::class, 'success'])->name('payments.success');
Route::get('/payments/canceled/{reference}', [MessageBundleController::class, 'cancel'])->name('payments.canceled');
Route::get('/payments/declined/{reference}', [MessageBundleController::class, 'decline'])->name('payments.declined');
Route::get('/payments/status', [MessageBundleController::class, 'status'])->name('payments.check');

Route::post('/verification/callback/{reference}', [VerificationPaymentController::class,'callback'])->name('verification.callback');
Route::get('/verification/success/{reference}', [VerificationPaymentController::class,'success'])->name('verification.success');
Route::get('/verification/cancel/{reference}', [VerificationPaymentController::class,'cancel'])->name('verification.cancel');
Route::get('/verification/decline/{reference}', [VerificationPaymentController::class,'decline'])->name('verification.decline');
Route::get('/verification/status', [VerificationPaymentController::class, 'status'])->name('Verification.check');

/*
|--------------------------------------------------------------------------
| On isole /profiles/{user} pour éviter le conflit avec la route /profile/liked-me
|--------------------------------------------------------------------------
*/
Route::get('profiles/{user}', [ProfileController::class, 'show']);
