<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check user roles and redirect accordingly
        $userRoles = $user->roles()->pluck('name')->toArray();
        
        if (in_array('admin', $userRoles)) {
            return redirect()->route('admin.dashboard');
        } elseif (in_array('referee', $userRoles)) {
            return redirect()->route('referee.dashboard');
        } elseif (in_array('coach', $userRoles)) {
            return redirect()->route('admin.coach-dashboard');
        }
        
        // Default dashboard for regular users
        return view('dashboard', compact('user'));
    }
}
