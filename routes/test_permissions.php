<?php

use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

Route::get('/test-permissions', function () {
    $output = [];
    
    // Check if permissions exist
    $permissions = Permission::whereIn('name', ['projects-save-as-template', 'projects-copy'])->get();
    $output[] = "=== PERMISSIONS IN DATABASE ===";
    foreach ($permissions as $perm) {
        $output[] = "Permission: {$perm->name} (ID: {$perm->id})";
    }
    
    // Check admin role permissions
    $adminRole = Role::where('name', 'admin')->first();
    if ($adminRole) {
        $output[] = "\n=== ADMIN ROLE PERMISSIONS ===";
        $adminPerms = $adminRole->permissions()->whereIn('name', ['projects-save-as-template', 'projects-copy'])->get();
        foreach ($adminPerms as $perm) {
            $output[] = "Admin has: {$perm->name}";
        }
    }
    
    // Check current user permissions
    if (Auth::check()) {
        $user = Auth::user();
        $output[] = "\n=== CURRENT USER INFO ===";
        $output[] = "User ID: {$user->id}";
        $output[] = "User Email: {$user->email}";
        
        $userRoles = $user->roles()->get();
        $output[] = "User Roles: " . $userRoles->pluck('name')->join(', ');
        
        $output[] = "\n=== USER PERMISSIONS CHECK ===";
        $output[] = "Can projects-save-as-template: " . ($user->can('projects-save-as-template') ? 'YES' : 'NO');
        $output[] = "Can projects-copy: " . ($user->can('projects-copy') ? 'YES' : 'NO');
        
        $allUserPerms = $user->getAllPermissions();
        $output[] = "\nAll user permissions: " . $allUserPerms->pluck('name')->join(', ');
    } else {
        $output[] = "\n=== USER NOT AUTHENTICATED ===";
    }
    
    return '<pre>' . implode("\n", $output) . '</pre>';
})->middleware('auth');