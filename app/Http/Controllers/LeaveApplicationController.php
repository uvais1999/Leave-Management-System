<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LeaveApplicationController extends Controller
{
    /**
     * Display a listing of the leave applications.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $authId = $user->id;

        // Fetch active leave types for applying
        $leaveTypes = LeaveType::where('is_active', true)->get();

        // Determine user role and load applications accordingly
        $isAdmin = $user->email === 'admin@test.com' || (method_exists($user, 'hasRole') && $user->hasRole('Admin'));
        $isManager = method_exists($user, 'hasRole') && $user->hasRole('Manager');

        $myApplications = [];
        $teamApplications = [];
        $allApplications = [];

        if ($isAdmin) {
            // Admin sees everything
            $allApplications = LeaveApplication::with(['user', 'leaveType', 'approver'])
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($isManager) {
            // Manager sees their own leaves and direct reports' leaves
            $myApplications = LeaveApplication::where('user_id', $authId)
                ->with(['leaveType', 'approver'])
                ->orderBy('created_at', 'desc')
                ->get();

            $subordinateIds = User::where('manager_id', $authId)->pluck('id');
            $teamApplications = LeaveApplication::whereIn('user_id', $subordinateIds)
                ->with(['user', 'leaveType'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Employee sees only their own leaves
            $myApplications = LeaveApplication::where('user_id', $authId)
                ->with(['leaveType', 'approver'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return Inertia::render('Leaves/Index', [
            'myApplications' => $myApplications,
            'teamApplications' => $teamApplications,
            'allApplications' => $allApplications,
            'leaveTypes' => $leaveTypes,
            'isAdmin' => $isAdmin,
            'isManager' => $isManager,
        ]);
    }

    /**
     * Store a newly created leave application in database.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'from_date' => 'required|date|after_or_equal:today',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|string|min:10|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:2048',
        ]);

        // Verify active leave type
        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        if (!$leaveType->is_active) {
            return redirect()->back()->withErrors(['leave_type_id' => 'The selected leave type is not active.']);
        }

        // Auto-calculate Total Days
        $fromDate = Carbon::parse($request->from_date);
        $toDate = Carbon::parse($request->to_date);
        $totalDays = $fromDate->diffInDays($toDate) + 1;

        // Handle attachment file upload
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        LeaveApplication::create([
            'user_id' => Auth::id(),
            'leave_type_id' => $request->leave_type_id,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'total_days' => $totalDays,
            'reason' => $request->reason,
            'attachment_path' => $attachmentPath,
            'status' => 'Pending', // Defaults to Pending
        ]);

        return redirect()->back()->with('success', 'Leave application submitted successfully.');
    }

    /**
     * Update the specified leave application in database.
     */
    public function update(Request $request, LeaveApplication $leave): RedirectResponse
    {
        // Safety check: Only allow updating pending leaves
        if ($leave->status !== 'Pending') {
            return redirect()->back()->withErrors(['status' => 'Only pending leaves can be updated.']);
        }

        // Safety check: Ensure the leave belongs to the authenticated user
        if ($leave->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'from_date' => 'required|date|after_or_equal:today',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|string|min:10|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:2048',
        ]);

        // Verify active leave type
        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        if (!$leaveType->is_active) {
            return redirect()->back()->withErrors(['leave_type_id' => 'The selected leave type is not active.']);
        }

        // Recalculate days
        $fromDate = Carbon::parse($request->from_date);
        $toDate = Carbon::parse($request->to_date);
        $totalDays = $fromDate->diffInDays($toDate) + 1;

        // Handle attachment file upload
        $attachmentPath = $leave->attachment_path;
        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        $leave->update([
            'leave_type_id' => $request->leave_type_id,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'total_days' => $totalDays,
            'reason' => $request->reason,
            'attachment_path' => $attachmentPath,
        ]);

        return redirect()->back()->with('success', 'Leave application updated successfully.');
    }

    /**
     * Remove the specified leave application from database.
     */
    public function destroy(LeaveApplication $leave): RedirectResponse
    {
        // Safety check: Only allow deleting pending leaves
        if ($leave->status !== 'Pending') {
            return redirect()->back()->withErrors(['status' => 'Only pending leaves can be deleted.']);
        }

        // Safety check: Ensure the leave belongs to the authenticated user
        if ($leave->user_id !== Auth::id()) {
            abort(403);
        }

        // Delete attachment from storage
        if ($leave->attachment_path) {
            Storage::disk('public')->delete($leave->attachment_path);
        }

        $leave->delete();

        return redirect()->back()->with('success', 'Leave application deleted.');
    }

    /**
     * Approve the specified leave application.
     */
    public function approve(LeaveApplication $leave): RedirectResponse
    {
        $user = Auth::user();
        $isAdmin = $user->email === 'admin@test.com' || (method_exists($user, 'hasRole') && $user->hasRole('Admin'));
        $isManager = method_exists($user, 'hasRole') && $user->hasRole('Manager');

        // Verify manager authorization (leave userReports to manager)
        if (!$isAdmin) {
            if (!$isManager || $leave->user->manager_id !== $user->id) {
                abort(403);
            }
        }

        $leave->update([
            'status' => 'Approved',
            'approved_by' => $user->id,
            'rejection_reason' => null,
        ]);

        return redirect()->back()->with('success', 'Leave application approved.');
    }

    /**
     * Reject the specified leave application with a reason.
     */
    public function reject(Request $request, LeaveApplication $leave): RedirectResponse
    {
        $user = Auth::user();
        $isAdmin = $user->email === 'admin@test.com' || (method_exists($user, 'hasRole') && $user->hasRole('Admin'));
        $isManager = method_exists($user, 'hasRole') && $user->hasRole('Manager');

        // Verify manager authorization
        if (!$isAdmin) {
            if (!$isManager || $leave->user->manager_id !== $user->id) {
                abort(403);
            }
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $leave->update([
            'status' => 'Rejected',
            'approved_by' => $user->id,
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()->back()->with('success', 'Leave application rejected.');
    }
}
