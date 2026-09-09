<?php

namespace App\Http\Controllers;

use App\Models\VenuePackage;
use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class VenuePackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = [
            'venuePackages' => VenuePackage::with('eventType')->get(),
            'eventTypes'    => EventType::where('status', 'active')->get(),
        ];

        return Inertia::render('Admin/VenuePackages', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_type_id'   => 'nullable',
            'title'           => 'required|string|min:2|max:255',
            'description'     => 'required|string|min:3',
            'guests'          => 'required|numeric|min:1',
            'price'           => 'required|numeric|min:0',
            'price_morning'   => 'nullable|numeric|min:0',
            'price_afternoon' => 'nullable|numeric|min:0',
            'price_night'     => 'nullable|numeric|min:0',
            'price_fullday'   => 'nullable|numeric|min:0',
            'price_visitor'   => 'nullable|numeric|min:0',
            'status'          => 'nullable|in:active,inactive',
        ]);

        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            ]);
        }

        $cleanData = [
            'title'           => $request->input('title'),
            'description'     => $request->input('description'),
            'guests'          => (int) $request->input('guests'),
            'price'           => (float) $request->input('price'),
            'status'          => $request->input('status', 'active'),
            'event_type_id'   => $request->filled('event_type_id') ? (int) $request->input('event_type_id') : null,
            'price_morning'   => $request->filled('price_morning') ? (float) $request->input('price_morning') : null,
            'price_afternoon' => $request->filled('price_afternoon') ? (float) $request->input('price_afternoon') : null,
            'price_night'     => $request->filled('price_night') ? (float) $request->input('price_night') : null,
            'price_fullday'   => $request->filled('price_fullday') ? (float) $request->input('price_fullday') : null,
            'price_visitor'   => $request->filled('price_visitor') ? (float) $request->input('price_visitor') : null,
        ];

        // Handle image file or preset image
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('package_images', 'public');
            $cleanData['image'] = '/storage/' . $path;
        } elseif ($request->filled('preset_image')) {
            $cleanData['image'] = $request->input('preset_image');
        } else {
            $cleanData['image'] = '/images/venue.jpg';
        }

        // Filter out any columns that don't exist in the database table
        try {
            $columns = Schema::getColumnListing('venue_packages');
            if (!empty($columns)) {
                $cleanData = array_intersect_key($cleanData, array_flip($columns));
            }
        } catch (\Throwable $e) {
            Log::warning('Schema listing failed: ' . $e->getMessage());
        }

        try {
            VenuePackage::create($cleanData);
        } catch (\Throwable $e) {
            Log::error('Error creating venue package: ' . $e->getMessage());
            return back()->withErrors(['title' => 'Could not save venue package: ' . $e->getMessage()]);
        }

        return redirect()->route('admin.venue-packages.index')->with('success', 'Venue Package created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VenuePackage $venuePackage)
    {
        $request->validate([
            'event_type_id'   => 'nullable',
            'title'           => 'required|string|min:2|max:255',
            'description'     => 'required|string|min:3',
            'guests'          => 'required|numeric|min:1',
            'price'           => 'required|numeric|min:0',
            'price_morning'   => 'nullable|numeric|min:0',
            'price_afternoon' => 'nullable|numeric|min:0',
            'price_night'     => 'nullable|numeric|min:0',
            'price_fullday'   => 'nullable|numeric|min:0',
            'price_visitor'   => 'nullable|numeric|min:0',
            'status'          => 'required|in:active,inactive',
        ]);

        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            ]);
        }

        $cleanData = [
            'title'           => $request->input('title'),
            'description'     => $request->input('description'),
            'guests'          => (int) $request->input('guests'),
            'price'           => (float) $request->input('price'),
            'status'          => $request->input('status', 'active'),
            'event_type_id'   => $request->filled('event_type_id') ? (int) $request->input('event_type_id') : null,
            'price_morning'   => $request->filled('price_morning') ? (float) $request->input('price_morning') : null,
            'price_afternoon' => $request->filled('price_afternoon') ? (float) $request->input('price_afternoon') : null,
            'price_night'     => $request->filled('price_night') ? (float) $request->input('price_night') : null,
            'price_fullday'   => $request->filled('price_fullday') ? (float) $request->input('price_fullday') : null,
            'price_visitor'   => $request->filled('price_visitor') ? (float) $request->input('price_visitor') : null,
        ];

        // Handle image file or preset image
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('package_images', 'public');
            $cleanData['image'] = '/storage/' . $path;
        } elseif ($request->filled('preset_image')) {
            $cleanData['image'] = $request->input('preset_image');
        }

        // Filter out any columns that don't exist in the database table
        try {
            $columns = Schema::getColumnListing('venue_packages');
            if (!empty($columns)) {
                $cleanData = array_intersect_key($cleanData, array_flip($columns));
            }
        } catch (\Throwable $e) {
            Log::warning('Schema listing failed: ' . $e->getMessage());
        }

        try {
            $venuePackage->update($cleanData);
        } catch (\Throwable $e) {
            Log::error('Error updating venue package: ' . $e->getMessage());
            return back()->withErrors(['title' => 'Could not update venue package: ' . $e->getMessage()]);
        }

        return redirect()->route('admin.venue-packages.index')->with('success', 'Venue Package updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VenuePackage $venuePackage)
    {
        try {
            $venuePackage->delete();
        } catch (\Throwable $e) {
            Log::error('Error deleting venue package: ' . $e->getMessage());
            return back()->withErrors(['title' => 'Could not delete venue package: ' . $e->getMessage()]);
        }

        return redirect()->route('admin.venue-packages.index')->with('success', 'Venue Package deleted successfully.');
    }
}