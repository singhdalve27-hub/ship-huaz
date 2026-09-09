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
use App\Models\Post; // <-- Idinagdag ang Post Model
use App\Models\VenuePackage; // <-- Idinagdag ang VenuePackage Model para sa landing page
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        // Kukunin natin ang lahat ng active posts sa database at ipapasa sa Welcome.vue
        'posts' => Post::where('status', 'active')->latest()->get(),
        // Kukunin natin ang mga venues na sinet-up ng admin sa database at ipapasa sa Welcome.vue
        'venues' => VenuePackage::with('eventType')->where('status', 'active')->get(),
    ]);
})->name('landing-page');

// Direct booking route that safely forwards query parameters to client booking wizard
Route::get('/booking', function (\Illuminate\Http\Request $request) {
    return redirect()->route('client.booking.index', $request->query());
})->name('booking.redirect');

Route::middleware(['auth', 'verified', 'client'])->prefix('client')->name('client.')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::prefix('booking')->name('booking.')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::post('/store', [BookingController::class, 'store'])->name('store');
        Route::put('/cancel/{booking}', [BookingController::class, 'cancel'])->name('cancel');
        Route::post('/check-availability', [BookingController::class, 'checkAvailability'])->name('check-availability');
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

Route::get('/api/chatbot', [ChatBotController::class, 'index'])->name('chatbot.index');
Route::get('/api/chatbot/nodes', [ChatBotController::class, 'nodes'])->name('chatbot.nodes');
Route::get('/api/chatbot/check-date', [ChatBotController::class, 'checkDate'])->name('chatbot.check-date');
Route::get('/api/chatbot/track-booking', [ChatBotController::class, 'trackBooking'])->name('chatbot.track-booking');
Route::match(['get', 'post'], '/api/chatbot/ask', [ChatBotController::class, 'ask'])->name('chatbot.ask');

// Fallback route para sa /storage files upang maiwasan ang 404 kung hindi naka-link o nawawala ang uploaded files
Route::get('/storage/{path}', function ($path) {
    // 1. Tignan kung may totoong file sa storage/app/public/
    $storageFile = storage_path('app/public/' . $path);
    if (file_exists($storageFile) && !is_dir($storageFile)) {
        return response()->file($storageFile);
    }

    // 2. Tignan kung may totoong file sa public/storage/
    $publicStorageFile = public_path('storage/' . $path);
    if (file_exists($publicStorageFile) && !is_dir($publicStorageFile)) {
        return response()->file($publicStorageFile);
    }

    // 3. Tignan kung nasa public root mismo
    $directPublic = public_path($path);
    if (file_exists($directPublic) && !is_dir($directPublic)) {
        return response()->file($directPublic);
    }

    // 4. Graceful fallbacks para maiwasan ang 404 console errors sa production/local
    if (str_contains($path, 'package_images') || str_contains($path, 'venue')) {
        $venueFallback = public_path('images/venue.jpg');
        if (file_exists($venueFallback)) {
            return response()->file($venueFallback);
        }
    }

    if (str_contains($path, 'blog_images') || str_contains($path, 'post')) {
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

require __DIR__ . '/auth.php';