<?php

namespace App\Http\Controllers;

use App\Models\PackageAddOn;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PackageAddOnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = [
            'packageAddOns' => PackageAddOn::all(),
        ];

        return Inertia::render('Admin/PackageAddOns', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|min:3|unique:package_add_ons,title',
            'description' => 'required|min:3',
            'pricing_type' => 'required|in:fixed,per_head', // <-- BAGO
            'price' => 'required|numeric|min:100',
            'guests' => 'required|numeric|min:1',
            'total' => 'required|numeric|min:0',
        ]);

        // Idinagdag ang 'pricing_type' sa loob ng only() array para isave
        PackageAddOn::create($request->only(['title', 'description', 'pricing_type', 'price', 'guests', 'total']));

        return redirect()->route('admin.package-add-ons.index')->with('success', 'Package Add-on created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PackageAddOn $packageAddOn)
    {
        $request->validate([
            'title' => 'required|min:3|unique:package_add_ons,title,' . $packageAddOn->id,
            'description' => 'required|min:3',
            'pricing_type' => 'required|in:fixed,per_head', // <-- BAGO
            'price' => 'required|numeric|min:100',
            'guests' => 'required|numeric|min:1',
            'total' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        // Idinagdag ang 'pricing_type' sa loob ng only() array para i-update
        $packageAddOn->update($request->only(['title', 'description', 'pricing_type', 'price', 'guests', 'total', 'status']));

        return redirect()->route('admin.package-add-ons.index')->with('success', 'Package Add-on updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PackageAddOn $packageAddOn)
    {
        $packageAddOn->delete();

        return redirect()->route('admin.package-add-ons.index')->with('success', 'Package Add-on deleted successfully.');
    }
}