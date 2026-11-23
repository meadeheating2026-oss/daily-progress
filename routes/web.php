<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectDashboardController;
use App\Http\Controllers\ProjectProgressController;
use App\Http\Controllers\ProjectGanttController;
use App\Http\Controllers\ProjectSubprojectController;
use App\Http\Controllers\DailyProgressController;
use App\Http\Controllers\WorkItemController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProjectItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LangController;
use App\Http\Controllers\LocalizationController;
use App\Http\Controllers\ProjectTemplateController;
use App\Http\Controllers\ProjectTypeController;
use App\Http\Controllers\ItemStatusController;
use App\Http\Controllers\IssueController;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\RecycleBinController;
use App\Http\Controllers\DataExportController;

// ✅ Add pattern constraint to prevent non-numeric values from matching project_template parameter
// This prevents routes like 'store-from-form' from being matched as project_template IDs
Route::pattern('project_template', '[0-9]+');

Route::get('/projects/drafts', [ProjectController::class, 'drafts'])
    ->name('projects.drafts');

Route::get('/projects/archived', [ProjectController::class, 'archived'])
    ->name('projects.archived')
    ->middleware('auth');

Route::post('/projects/{project}/archive', [ProjectController::class, 'archive'])
    ->name('projects.archive')
    ->middleware('auth');

Route::post('/projects/{project}/unarchive', [ProjectController::class, 'unarchive'])
    ->name('projects.unarchive')
    ->middleware('auth');

Route::post('/projects/{project}/publish', [ProjectController::class, 'publish'])
    ->name('projects.publish')
    ->middleware('can:projects-edit');

    // باقي الـ routes...
Route::delete('/recycle-bin/{id}/permanent-delete', [RecycleBinController::class, 'permanentDelete'])
    ->name('recycle-bin.permanent-delete');


Route::get('/projects/{project}/gantt', [ProjectGanttController::class, 'ganttChart'])
    ->name('projects.gantt');

Route::get('/projects/{project}/gantt-data', [ProjectGanttController::class, 'ganttData'])
    ->name('projects.gantt.data');

Route::get('/projects/{project}/items-data', [ProjectController::class, 'getItemsData'])
    ->name('projects.items.data')
    ->middleware('auth');
Route::get('/export', function () {
    return view('export'); // resources/views/export.blade.php
})->name('export.page');

// زرار تصدير كل البيانات (zip)
Route::get('/export-data', [DataExportController::class, 'exportAllData'])->name('export.data');

// زرار تصدير SQL dump
Route::get('/export-sql', [DataExportController::class, 'exportSqlDump'])->name('export.sql');


// لوحة التحكم الرئيسية (moved below with auth middleware)

// ✅ IMPORTANT: Place specific routes BEFORE resource routes to avoid route conflicts
// Save template from form (before creating project) - MUST be before resource route
// Support both POST and PUT methods to handle form submissions
Route::match(['POST', 'PUT'], '/project-templates/store-from-form', [ProjectTemplateController::class, 'storeFromForm'])
    ->name('project-templates.store-from-form');

// Save project as template from index - MUST be before resource route
Route::post('/project-templates/store-from-project', [ProjectTemplateController::class, 'storeFromProject'])
    ->name('project-templates.store-from-project');

// Save project as template (temporarily without middleware for testing)
Route::post('/projects/{project}/save-as-template', [ProjectController::class, 'saveAsTemplate'])
    ->name('projects.save-as-template');
    // ->middleware('can:projects-save-as-template'); // Temporarily disabled for testing

// JSON: جلب بنود القالب للاستخدام في AJAX عند إنشاء مشروع
Route::get('project-templates/{project_template}/items', [ProjectTemplateController::class, 'items'])
    ->name('project-templates.items');

// JSON: جلب بيانات القالب مع الفئات للاستخدام في المشروع
Route::get('project-templates/{project_template}/data', [ProjectTemplateController::class, 'getTemplateData'])
    ->name('project-templates.data');

// Debug predecessors in template
Route::get('project-templates/{project_template}/debug-predecessors', [ProjectTemplateController::class, 'debugPredecessors'])
    ->name('project-templates.debug-predecessors')
    ->middleware('can:project-templates-view');

// Resource routes - MUST be after specific routes
Route::resource('project-templates', ProjectTemplateController::class);
Route::get('/project-templates/{id}/items', function ($id) {
    $template = \App\Models\ProjectTemplate::with('items')->findOrFail($id);
    return response()->json($template->items);
});
Route::resource('project_types', ProjectTypeController::class);
Route::resource('item-statuses', ItemStatusController::class)->middleware('auth');

// Issues Routes
Route::middleware('auth')->prefix('issues')->name('issues.')->group(function () {
    Route::get('/', [IssueController::class, 'index'])->name('index');
    Route::get('/kanban', [IssueController::class, 'kanban'])->name('kanban');
    Route::get('/statistics', [IssueController::class, 'statistics'])->name('statistics');
    Route::get('/create', [IssueController::class, 'create'])->name('create');
    Route::post('/', [IssueController::class, 'store'])->name('store');
    Route::get('/{issue}', [IssueController::class, 'show'])->name('show');
    Route::get('/{issue}/edit', [IssueController::class, 'edit'])->name('edit');
    Route::put('/{issue}', [IssueController::class, 'update'])->name('update');
    Route::delete('/{issue}', [IssueController::class, 'destroy'])->name('destroy');
    
    // Comments
    Route::post('/{issue}/comments', [IssueController::class, 'addComment'])->name('comments.store');
    Route::delete('/comments/{comment}', [IssueController::class, 'deleteComment'])->name('comments.destroy');
    
    // Attachments
    Route::get('/attachments/{attachment}/download', [IssueController::class, 'downloadAttachment'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [IssueController::class, 'deleteAttachment'])->name('attachments.destroy');
    
    // Status update (for Kanban)
    Route::patch('/{issue}/status', [IssueController::class, 'updateStatus'])->name('update-status');
});

// Copy project
Route::post('/projects/{project}/copy', [ProjectController::class, 'copy'])
    ->name('projects.copy')
    ->middleware('can:projects-copy');

// Reorder template items
Route::post('/project-templates/{project_template}/reorder-items', [ProjectTemplateController::class, 'reorderItems'])
    ->name('project-templates.reorder-items')
    ->middleware('can:project-templates-edit');
// Auth routes
Auth::routes(['register' => false]);

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);

    // Password Reset Routes
    Route::get('/password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
});

// Route::post('/dashboard/filter', [DashboardController::class, 'filter'])->name('dashboard.filter');
Route::get('/projects/{project}/progress/export', [ProjectProgressController::class, 'export'])
    ->name('projects.progress.export')
    ->middleware('auth');
Route::get('/projects/{project}/progress/print', [ProjectProgressController::class, 'progressPrint'])
    ->name('projects.progress.print')
    ->middleware('auth');
// Logout Route
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/employees/{id}/permissions', [EmployeeController::class, 'editPermissions'])
     ->name('employees.permissions');

     Route::post('/employees/{id}/permissions', [EmployeeController::class, 'updatePermissions'])
     ->name('employees.updatePermissions');
// الصفحة الرئيسية
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    } else {
        return redirect()->route('login');
    }
});
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

// Clients Routes
Route::resource('clients', ClientController::class)->except(['show'])->middleware('auth');

// Employees Routes
Route::resource('employees', EmployeeController::class)->except(['show'])->middleware('auth');

// Work Items Routes
Route::resource('work-items', WorkItemController::class)->except(['show'])->middleware('auth');

// Projects Routes
Route::resource('projects', ProjectController::class)->middleware('auth');
Route::get('projects/{project}/progress', [ProjectProgressController::class, 'progress'])->name('projects.progress')->middleware('auth');

// Project Items Routes
Route::prefix('projects/{project}')->middleware('auth')->group(function () {
    Route::post('/items', [ProjectItemController::class, 'store'])->name('project-items.store');
    Route::put('/items/{projectItem}', [ProjectItemController::class, 'update'])->name('project-items.update');
    Route::delete('/items/{projectItem}', [ProjectItemController::class, 'destroy'])->name('project-items.destroy');
    // ✅ تحديث وزن جميع المشاريع الفرعية دفعة واحدة
    Route::post('/subprojects/update-all-weights', [ProjectSubprojectController::class, 'updateAllSubprojectsWeight'])->name('projects.update-all-subprojects-weight');
    // ✅ تحديث حالة البند
    Route::patch('/items/{projectItem}/status', [ProjectController::class, 'updateItemStatus'])->name('projects.items.update-status');
});

// صلاحيات الموظفين
// Route::get('/employees/{id}/permissions', [EmployeeController::class, 'editPermissions'])->name('employees.permissions');
// Route::post('/employees/{id}/permissions', [EmployeeController::class, 'updatePermissions'])->name('employees.updatePermissions');

// Daily Progress Routes
Route::resource('daily-progress', DailyProgressController::class)->except(['show'])->middleware('auth');

// Reports Routes
Route::get('/progress-report', [App\Http\Controllers\ReportController::class, 'progressReport'])->name('progress.report')->middleware('auth');

// Language Switching Routes
Route::get('/lang/{locale}', [LocalizationController::class, 'switch'])->name('lang.switch');

// API Routes
Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/project-items/{project}', [ProjectItemController::class, 'getByProject']);
    Route::get('/daily-progress/executed-today', [DailyProgressController::class, 'executedToday']);
    Route::get('/projects/{project}/subprojects', [ProjectSubprojectController::class, 'getSubprojects']);
    Route::post('/projects/{project}/subprojects/{subproject}/update-weight', [ProjectSubprojectController::class, 'updateSubprojectWeight'])->name('projects.subprojects.update-weight');
    // ✅ تحديث وزن جميع المشاريع الفرعية دفعة واحدة (للاستخدام من index.blade.php)
    Route::post('/projects/{project}/subprojects/update-all-weights', [ProjectSubprojectController::class, 'updateAllSubprojectsWeight'])->name('api.projects.subprojects.update-all-weights');
});

// Activity Log Routes
Route::prefix('activity-logs')->middleware('auth')->name('activity-logs.')->group(function () {
    Route::get('/', [App\Http\Controllers\ActivityLogController::class, 'index'])->name('index');
    Route::get('/{activity}', [App\Http\Controllers\ActivityLogController::class, 'show'])->name('show');
    Route::get('/user/{userId}/activities', [App\Http\Controllers\ActivityLogController::class, 'userActivities'])->name('user-activities');
    Route::get('/subject/{subjectType}/{subjectId}/activities', [App\Http\Controllers\ActivityLogController::class, 'subjectActivities'])->name('subject-activities');
    Route::get('/api/activities', [App\Http\Controllers\ActivityLogController::class, 'getActivities'])->name('api.activities');
});

Route::get('/projects/{project}/dashboard', [ProjectDashboardController::class, 'dashboard'])
    ->name('projects.dashboard')
    ->middleware('auth');

Route::get('/projects/{project}/dashboard/print', [ProjectDashboardController::class, 'dashboardPrint'])
    ->name('projects.dashboard.print')
    ->middleware('auth');

// Test Activity Logs Route
Route::get('/test-activity-logs', function () {
    return view('activity-logs.test');
})->middleware('auth')->name('test-activity-logs');
Route::group(['middleware' => 'auth'], function () {
    // مسارات سلة المحذوفات
    Route::get('/recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index');
    Route::get('/recycle-bin/restore/{type}/{id}', [RecycleBinController::class, 'restore'])->name('recycle-bin.restore');
    Route::get('/recycle-bin/force-delete/{type}/{id}', [RecycleBinController::class, 'forceDelete'])->name('recycle-bin.force-delete');
});

Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
Route::resource('categories', CategoryController::class)->except(['show']);
Route::post('/work-items/reorder', [WorkItemController::class, 'reorder'])->name('work-items.reorder')->middleware('auth');
Route::get('/work-items/search-ajax', [WorkItemController::class, 'search'])->name('work-items.search')->middleware('auth');
Route::post('/work-items/store-ajax', [WorkItemController::class, 'storeAjax'])->name('work-items.store-ajax')->middleware('auth');

// Include test routes
if (app()->environment(['local', 'testing'])) {
    require __DIR__ . '/test.php';
}

// Backup & Restore Routes
Route::middleware(['auth'])->prefix('backup')->name('backup.')->group(function () {
    Route::get('/', [App\Http\Controllers\BackupController::class, 'index'])->name('index');
    Route::get('/export', [App\Http\Controllers\BackupController::class, 'export'])->name('export');
    Route::post('/import', [App\Http\Controllers\BackupController::class, 'import'])->name('import');
    Route::get('/download/{filename}', [App\Http\Controllers\BackupController::class, 'download'])->name('download');
    Route::delete('/delete/{filename}', [App\Http\Controllers\BackupController::class, 'destroy'])->name('destroy');
});

// Test permissions route
require __DIR__ . '/test_permissions.php';

// ====== Urca Public Backup (no middleware) ======
Route::get('/urcaBackUp', [App\Http\Controllers\UrcaBackUpController::class, 'index'])->name('urca-backup.index');
Route::post('/urcaBackUp/download', [App\Http\Controllers\UrcaBackUpController::class, 'download'])->name('urca-backup.download');
