<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Booking;
use App\Models\PackageAddOn;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{

    public function index()
    {
        $allAddOns = PackageAddOn::pluck('title', 'id');

        $data = [
            'bookings' => Booking::with('eventType', 'venuePackage', 'paymentOption')
                ->latest()
                ->where('user_id', auth()->user()->id)
                ->get()
                ->map(function ($booking) use ($allAddOns) {
                    $addonIds = $booking->package_add_ons ?? [];
                    $booking->package_add_ons = collect($addonIds)
                        ->map(fn($id) => $allAddOns[$id] ?? null)
                        ->filter()
                        ->values();
                    return $booking;
                }),
        ];

        return Inertia::render('Client/Profile', $data);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's information.
     */
    public function updateInformation(Request $request): RedirectResponse
    {
        $phone = preg_replace('/[^0-9]/', '', (string)$request->phone);
        if (str_starts_with($phone, '63') && strlen($phone) === 12) {
            $phone = substr($phone, 2);
        } elseif (str_starts_with($phone, '0') && strlen($phone) === 11) {
            $phone = substr($phone, 1);
        }
        $request->merge(['phone' => $phone]);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'phone' => ['required', 'regex:/^9\d{9}$/'],
            'address' => 'required|string|max:255',
        ]);

        $user = $request->user();
        if ($user->userInfo) {
            $user->userInfo->update([
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'birth_date' => $request->birth_date,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
        } else {
            $userInfo = \App\Models\UserInfo::create([
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'birth_date' => $request->birth_date,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
            $user->user_info_id = $userInfo->id;
            $user->save();
        }

        if ($request->user()->role === 'admin') {
            return Redirect::route('admin.profile');
        }

        return Redirect::route('client.profile.index');
    }

    /**
     * Update the user's credentials.
     */
    public function updateCredentials(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($request->user()->id)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->update([
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $request->user()->update([
                'password' => Hash::make($request->password),
            ]);
        }

        if ($request->user()->role === 'admin') {
            return Redirect::route('admin.profile');
        }

        return Redirect::route('client.profile.index');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
