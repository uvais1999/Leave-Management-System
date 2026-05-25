<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    /**
     * Display a listing of the leave types.
     */
    public function index(): Response
    {
        $leaveTypes = LeaveType::orderBy('id', 'asc')->get();

        return Inertia::render('LeaveTypes/Index', [
            'leaveTypes' => $leaveTypes,
        ]);
    }

    /**
     * Store a newly created leave type in database.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name',
            'max_days_per_year' => 'required|integer|min:1|max:365',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
        ]);

        LeaveType::create($request->all());

        return redirect()->back()->with('success', 'Leave type created successfully.');
    }

    /**
     * Update the specified leave type in database.
     */
    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name,' . $leaveType->id,
            'max_days_per_year' => 'required|integer|min:1|max:365',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
        ]);

        $leaveType->update($request->all());

        return redirect()->back()->with('success', 'Leave type updated successfully.');
    }

    /**
     * Remove the specified leave type from database.
     */
    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $leaveType->delete();

        return redirect()->back()->with('success', 'Leave type deleted successfully.');
    }

    /**
     * Toggle the active/inactive status of the specified leave type.
     */
    public function toggleStatus(LeaveType $leaveType): RedirectResponse
    {
        $leaveType->is_active = !$leaveType->is_active;
        $leaveType->save();

        return redirect()->back()->with('success', 'Leave type status updated.');
    }
}
