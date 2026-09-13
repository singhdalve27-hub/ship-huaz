<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = [
            'users' => User::with('userInfo')
                ->where('role', 'client')
                ->whereIn('status', ['active', 'inactive'])
                ->orderBy('created_at', 'desc')
                ->get(),
        ];

        return Inertia::render('Admin/Clients', $data);
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function updateStatus(Request $request, User $user)
    {
        if ($user->role === 'admin' || $user->id === auth()->id()) {
            return back()->withErrors(['status' => 'Cannot modify the status of an administrator or your own account.']);
        }

        $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $user->update(['status' => $request->status]);

        return back()->with('success', 'Client status updated successfully.');
    }
}
