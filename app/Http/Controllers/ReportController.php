<?php
namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function progressReport()
    {
    $projects = Project::with(['items.workItem', 'items.dailyProgress'])->get();
        return view('reports.progress', compact('projects'));
    }
}