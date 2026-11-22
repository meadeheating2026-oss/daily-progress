<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;

// Test route for save as template
Route::get('/test-save-template/{project}', function($project) {
    $project = \App\Models\Project::findOrFail($project);
    
    return response()->json([
        'project_id' => $project->id,
        'project_name' => $project->name,
        'items_count' => $project->items()->count(),
        'route_exists' => route('projects.save-as-template', $project),
        'user_can_access' => auth()->check(),
        'user_id' => auth()->id(),
    ]);
})->middleware('auth');