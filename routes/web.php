<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ChatBotController;
use App\Http\Controllers\ChatBotNodeController;
use App\Http\Controllers\ChatBotNodeOptionController;
use App\Http\Controllers\EventTypeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MessageThreadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PackageAddOnController;
use App\Http\Controllers\PaymentOptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenuePackageController;
use App\Http\Controllers\PostController; // <-- Idinagdag ang PostController
use App\Http\Controllers\FeedbackController;
use App\Models\Post; // <-- Idinagdag ang Post Model
use App\Models\VenuePackage; // <-- Idinagdag ang VenuePackage Model para sa landing page
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    try {
        $posts = Post::where('status', 'active')->latest()->get();
    } catch (\Throwable $e) {
        $posts = collect();
    }

    try {
        $venues = VenuePackage::with('eventType')->where('status', 'active')->get();
    } catch (\Throwable $e) {
        $venues = collect();
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'posts' => $posts,
        'venues' => $venues,
    ]);
})->name('landing-page');

Route::get('/system-health', function (\Illuminate\Http\Request $request) {
    try {
        $dbStatus = \Illuminate\Support\Facades\DB::connection()->getPdo() ? 'connected' : 'error';
    } catch (\Throwable $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }

    $tables = [
        'password_reset_tokens' => \Illuminate\Support\Facades\Schema::hasTable('password_reset_tokens'),
        'password_resets' => \Illuminate\Support\Facades\Schema::hasTable('password_resets'),
        'users' => \Illuminate\Support\Facades\Schema::hasTable('users'),
    ];

    $mailCheck = null;
    if ($request->has('check_mail')) {
        try {
            \Illuminate\Support\Facades\Mail::raw('Diagnostic email from Ship Hauz', function($m) use ($request) {
                $m->to($request->get('email', 'Singhdalve27@gmail.com'))->subject('Ship Hauz Diagnostic');
            });
            $mailCheck = 'Mail sent successfully';
        } catch (\Throwable $e) {
            $mailCheck = 'Mail error: ' . $e->getMessage() . ' (' . get_class($e) . ')';
        }
    }

    $resetCheck = null;
    if ($request->has('check_reset')) {
        try {
            $resetCheck = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $request->get('email', 'Singhdalve27@gmail.com')]);
        } catch (\Throwable $e) {
            $resetCheck = 'Reset error: ' . $e->getMessage() . ' (' . get_class($e) . ')';
        }
    }

    return response()->json([
        'status' => 'online',
        'php_version' => PHP_VERSION,
        'app_env' => app()->environment(),
        'db' => $dbStatus,
        'base_path' => base_path(),
        'composer_exists' => file_exists(base_path('composer.json')),
        'composer_writable' => is_writable(base_path()),
        'tables' => $tables,
        'mail_check' => $mailCheck,
        'reset_check' => $resetCheck,
        'time' => now()->toIso8601String(),
    ]);
});

// Direct booking route that safely forwards query parameters to client booking wizard
Route::get('/booking', function (\Illuminate\Http\Request $request) {
    return redirect()->route('client.booking.index', $request->query());
})->name('booking.redirect');

Route::middleware(['auth', 'verified', 'client'])->prefix('client')->name('client.')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::prefix('booking')->name('booking.')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::post('/store', [BookingController::class, 'store'])->middleware('throttle:15,1')->name('store');
        Route::put('/cancel/{booking}', [BookingController::class, 'cancel'])->name('cancel');
        Route::post('/check-availability', [BookingController::class, 'checkAvailability'])->middleware('throttle:30,1')->name('check-availability');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/notifications/{thread}/reply', [NotificationController::class, 'reply'])->name('reply');
        Route::patch('/notifications/{thread}/read', [NotificationController::class, 'markRead'])->name('markRead');
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('markAllRead');
        Route::delete('/notifications/{thread}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->name('destroyAll');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
    });

    Route::post('/bookings/{booking}/feedback', [FeedbackController::class, 'store'])->middleware('throttle:10,1')->name('feedback.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('index');
    });

    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [BookingController::class, 'indexAdmin'])->name('index');
        Route::put('/update/{booking}', [BookingController::class, 'update'])->name('update');
    });

    Route::prefix('event-types')->name('event-types.')->group(function () {
        Route::get('/', [EventTypeController::class, 'index'])->name('index');
        Route::post('/store', [EventTypeController::class, 'store'])->name('store');
        Route::put('/update/{eventType}', [EventTypeController::class, 'update'])->name('update');
        Route::delete('/destroy/{eventType}', [EventTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('venue-packages')->name('venue-packages.')->group(function () {
        Route::get('/', [VenuePackageController::class, 'index'])->name('index');
        Route::post('/store', [VenuePackageController::class, 'store'])->name('store');
        Route::put('/update/{venuePackage}', [VenuePackageController::class, 'update'])->name('update');
        Route::delete('/destroy/{venuePackage}', [VenuePackageController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('package-add-ons')->name('package-add-ons.')->group(function () {
        Route::get('/', [PackageAddOnController::class, 'index'])->name('index');
        Route::post('/store', [PackageAddOnController::class, 'store'])->name('store');
        Route::put('/update/{packageAddOn}', [PackageAddOnController::class, 'update'])->name('update');
        Route::delete('/destroy/{packageAddOn}', [PackageAddOnController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('payment-options')->name('payment-options.')->group(function () {
        Route::get('/', [PaymentOptionController::class, 'index'])->name('index');
        Route::put('/update/{paymentOption}', [PaymentOptionController::class, 'update'])->name('update');
    });

    Route::prefix('/messages')->name('messages.')->group(function () {
        Route::get('/', [MessageThreadController::class, 'index'])->name('index');
        Route::post('/store', [MessageThreadController::class, 'store'])->name('store');
        Route::post('/{thread}/reply', [MessageThreadController::class, 'reply'])->name('reply');
        Route::patch('/{thread}/read', [MessageThreadController::class, 'markRead'])->name('markRead');
        Route::delete('/{thread}', [MessageThreadController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::put('/update-status/{user}', [UserController::class, 'updateStatus'])->name('update-status');
    });

    Route::prefix('chat-nodes')->name('chat-nodes.')->group(function () {
        Route::get('/', [ChatBotNodeController::class, 'index'])->name('index');
        Route::post('/store', [ChatBotNodeController::class, 'store'])->name('store');
        Route::put('/update/{chatBotNode}', [ChatBotNodeController::class, 'update'])->name('update');
        Route::delete('/destroy/{chatBotNode}', [ChatBotNodeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('chat-node-options')->name('chat-node-options.')->group(function () {
        Route::get('/', [ChatBotNodeOptionController::class, 'index'])->name('index');
        Route::post('/store', [ChatBotNodeOptionController::class, 'store'])->name('store');
        Route::put('/update/{nodeId}', [ChatBotNodeOptionController::class, 'update'])->name('update');
        Route::delete('/destroy/{nodeId}', [ChatBotNodeOptionController::class, 'destroy'])->name('destroy');
    });

    // <-- BAGONG CMS POSTS ROUTE PARA SA ADMIN -->
    Route::prefix('posts')->name('posts.')->group(function () {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::post('/store', [PostController::class, 'store'])->name('store');
        Route::put('/update/{post}', [PostController::class, 'update'])->name('update');
        Route::delete('/destroy/{post}', [PostController::class, 'destroy'])->name('destroy');
    });

    Route::get('/profile', function () {
        return Inertia::render('Admin/Profile');
    })->name('profile');
});

Route::middleware('auth')->group(function () {
    Route::put('/update-information', [ProfileController::class, 'updateInformation'])->name('update-information');
    Route::put('/update-credentials', [ProfileController::class, 'updateCredentials'])->name('update-credentials');
});

// Chatbot public API endpoints protected by rate limiting
Route::middleware('throttle:45,1')->prefix('api/chatbot')->group(function () {
    Route::get('/', [ChatBotController::class, 'index'])->name('chatbot.index');
    Route::get('/nodes', [ChatBotController::class, 'nodes'])->name('chatbot.nodes');
    Route::get('/check-date', [ChatBotController::class, 'checkDate'])->name('chatbot.check-date');
    Route::get('/track-booking', [ChatBotController::class, 'trackBooking'])->name('chatbot.track-booking');
    Route::match(['get', 'post'], '/ask', [ChatBotController::class, 'ask'])->name('chatbot.ask');
});

// Secure fallback route for /storage assets with strict path traversal prevention
Route::get('/storage/{path}', function (string $path) {
    // Block path traversal attempts
    if (str_contains($path, '..') || str_contains($path, "\0") || str_contains($path, '\\')) {
        abort(404);
    }

    $cleanPath = ltrim($path, '/');

    // 1. Check storage/app/public/
    $baseStorage = realpath(storage_path('app/public'));
    $storageFile = realpath(storage_path('app/public/' . $cleanPath));
    if ($storageFile && $baseStorage && str_starts_with($storageFile, $baseStorage) && is_file($storageFile)) {
        return response()->file($storageFile);
    }

    // 2. Check public/storage/
    $basePublicStorage = realpath(public_path('storage'));
    $publicStorageFile = realpath(public_path('storage/' . $cleanPath));
    if ($publicStorageFile && $basePublicStorage && str_starts_with($publicStorageFile, $basePublicStorage) && is_file($publicStorageFile)) {
        return response()->file($publicStorageFile);
    }

    // 3. Check public/images/
    $baseImages = realpath(public_path('images'));
    $imageFile = realpath(public_path('images/' . $cleanPath));
    if ($imageFile && $baseImages && str_starts_with($imageFile, $baseImages) && is_file($imageFile)) {
        return response()->file($imageFile);
    }

    // 4. Graceful image fallbacks to prevent 404 broken image icons
    if (str_contains($cleanPath, 'package_images') || str_contains($cleanPath, 'venue')) {
        $venueFallback = public_path('images/venue.jpg');
        if (file_exists($venueFallback)) {
            return response()->file($venueFallback);
        }
    }

    if (str_contains($cleanPath, 'blog_images') || str_contains($cleanPath, 'post')) {
        $blogFallback = public_path('images/blog1.jpg');
        if (file_exists($blogFallback)) {
            return response()->file($blogFallback);
        }
    }

    $defaultFallback = public_path('images/venue.jpg');
    if (file_exists($defaultFallback)) {
        return response()->file($defaultFallback);
    }

    abort(404);
})->where('path', '.*');

// Secure fallback route for /images with strict path traversal prevention
Route::get('/images/{filename}', function (string $filename) {
    if (str_contains($filename, '..') || str_contains($filename, "\0") || str_contains($filename, '\\')) {
        abort(404);
    }

    $cleanName = ltrim($filename, '/');
    $baseImages = realpath(public_path('images'));
    $filePath = realpath(public_path('images/' . $cleanName));

    if ($filePath && $baseImages && str_starts_with($filePath, $baseImages) && is_file($filePath)) {
        return response()->file($filePath);
    }

    if (str_contains($cleanName, 'gcash') || str_contains($cleanName, 'qr')) {
        return redirect('https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=09207139299&margin=10');
    }

    $fallback = public_path('images/venue.jpg');
    if (file_exists($fallback)) {
        return response()->file($fallback);
    }

    abort(404);
})->where('filename', '.*');

require __DIR__ . '/auth.php';