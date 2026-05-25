<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $user = Auth::user();
        $authId = $user->id;
        $today = Carbon::today()->toDateString();

        $isAdmin = $user->email === 'admin@test.com' || (method_exists($user, 'hasRole') && $user->hasRole('Admin'));
        $isManager = method_exists($user, 'hasRole') && $user->hasRole('Manager');

        $stats = [];
        $recentApplications = [];

        if ($isAdmin) {
            // Admin Stats: Total employees, pending global reviews, active out-of-office, active leave configurations
            $totalEmployees = User::count();
            $pendingApprovals = LeaveApplication::where('status', 'Pending')->count();
            
            $onLeaveToday = LeaveApplication::where('status', 'Approved')
                ->where('from_date', '<=', $today)
                ->where('to_date', '>=', $today)
                ->count();
                
            $activeLeaveTypes = LeaveType::where('is_active', true)->count();

            $stats = [
                ['name' => 'Total Employees', 'value' => (string)$totalEmployees, 'change' => 'Active users in directory', 'icon' => 'users', 'color' => 'indigo'],
                ['name' => 'Pending Approvals', 'value' => (string)$pendingApprovals, 'change' => 'Requires decision', 'icon' => 'clock', 'color' => 'amber'],
                ['name' => 'On Leave Today', 'value' => (string)$onLeaveToday, 'change' => 'Currently out of office', 'icon' => 'calendar', 'color' => 'purple'],
                ['name' => 'Active Leave Types', 'value' => (string)$activeLeaveTypes, 'change' => 'Configured policies', 'icon' => 'tag', 'color' => 'pink'],
            ];

            // Recent company-wide applications
            $recentApplications = LeaveApplication::with(['user', 'leaveType'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($app) {
                    return [
                        'id' => $app->id,
                        'employee' => $app->user->name,
                        'type' => $app->leaveType->name,
                        'duration' => $app->total_days . ' days (' . Carbon::parse($app->from_date)->format('M d') . ' - ' . Carbon::parse($app->to_date)->format('M d') . ')',
                        'status' => $app->status,
                    ];
                });
        } elseif ($isManager) {
            // Manager Stats: Subordinates reporting count, subordinate pending applications, subordinates on leave today, and own leave summary
            $subordinateIds = User::where('manager_id', $authId)->pluck('id');
            $subordinatesCount = $subordinateIds->count();
            
            $pendingReviews = LeaveApplication::whereIn('user_id', $subordinateIds)
                ->where('status', 'Pending')
                ->count();
                
            $teamOnLeave = LeaveApplication::whereIn('user_id', $subordinateIds)
                ->where('status', 'Approved')
                ->where('from_date', '<=', $today)
                ->where('to_date', '>=', $today)
                ->count();

            // Own approved leave count (Managers do not apply for leaves, but fallback set for profile safety)
            $ownApprovedDays = LeaveApplication::where('user_id', $authId)
                ->where('status', 'Approved')
                ->sum('total_days');

            $stats = [
                ['name' => 'Subordinates Count', 'value' => (string)$subordinatesCount, 'change' => 'Direct reports', 'icon' => 'users', 'color' => 'indigo'],
                ['name' => 'Pending Reviews', 'value' => (string)$pendingReviews, 'change' => 'Awaiting review', 'icon' => 'clock', 'color' => 'amber'],
                ['name' => 'Team On Leave Today', 'value' => (string)$teamOnLeave, 'change' => 'Out of office today', 'icon' => 'calendar', 'color' => 'purple'],
                ['name' => 'Approved Leave Days', 'value' => $ownApprovedDays . ' Days', 'change' => 'Total days utilized', 'icon' => 'progress', 'color' => 'emerald'],
            ];

            // Recent subordinate applications
            $recentApplications = LeaveApplication::whereIn('user_id', $subordinateIds)
                ->with(['user', 'leaveType'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($app) {
                    return [
                        'id' => $app->id,
                        'employee' => $app->user->name,
                        'type' => $app->leaveType->name,
                        'duration' => $app->total_days . ' days (' . Carbon::parse($app->from_date)->format('M d') . ' - ' . Carbon::parse($app->to_date)->format('M d') . ')',
                        'status' => $app->status,
                    ];
                });
        } else {
            // Employee Stats: Total allowed leaves pool, leaves approved, leaves pending, and remaining allowance
            $totalAllowance = LeaveType::where('is_active', true)->sum('max_days_per_year');
            
            $leavesApproved = LeaveApplication::where('user_id', $authId)
                ->where('status', 'Approved')
                ->sum('total_days');
                
            $leavesPending = LeaveApplication::where('user_id', $authId)
                ->where('status', 'Pending')
                ->sum('total_days');
                
            $availableAllowance = max(0, $totalAllowance - $leavesApproved);

            $stats = [
                ['name' => 'Annual Leave Balance', 'value' => $totalAllowance . ' Days', 'change' => 'Total annual quota', 'icon' => 'balance', 'color' => 'indigo'],
                ['name' => 'Leaves Approved', 'value' => $leavesApproved . ' Days', 'change' => 'Paid leave used', 'icon' => 'check', 'color' => 'emerald'],
                ['name' => 'Leaves Pending', 'value' => $leavesPending . ' Days', 'change' => 'Awaiting decision', 'icon' => 'clock', 'color' => 'amber'],
                ['name' => 'Available Allowance', 'value' => $availableAllowance . ' Days', 'change' => 'Remaining this year', 'icon' => 'progress', 'color' => 'purple'],
            ];

            // Recent own applications
            $recentApplications = LeaveApplication::where('user_id', $authId)
                ->with(['leaveType'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($app) {
                    return [
                        'id' => $app->id,
                        'employee' => 'You',
                        'type' => $app->leaveType->name,
                        'duration' => $app->total_days . ' days (' . Carbon::parse($app->from_date)->format('M d') . ' - ' . Carbon::parse($app->to_date)->format('M d') . ')',
                        'status' => $app->status,
                    ];
                });
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentApplications' => $recentApplications,
        ]);
    }
}
