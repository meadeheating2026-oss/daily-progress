<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectType;
class ProjectTypeController extends Controller
{
        public function __construct()
{
    $this->middleware('can:project-types-list')->only('index');
    $this->middleware('can:project-types-create')->only(['create', 'store']);
    $this->middleware('can:project-types-edit')->only(['edit', 'update']);
    $this->middleware('can:project-types-delete')->only('destroy');
    // $this->middleware('can:employees-permissions')->only('assignPermissions');
}
    public function index()
    {
        $types = ProjectType::all();
        return view('project_types.index', compact('types'));
    }

    public function create()
    {
        return view('project_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:project_types,name',
        ]);

        ProjectType::create($request->all());
        return redirect()->route('project_types.index')->with('success', 'تم إضافة النوع بنجاح');
    }

    public function show(ProjectType $projectType)
    {
        return view('project_types.show', compact('projectType'));
    }

    public function edit(ProjectType $projectType)
    {
        return view('project_types.edit', compact('projectType'));
    }

    public function update(Request $request, ProjectType $projectType)
    {
        $request->validate([
            'name' => 'required|unique:project_types,name,' . $projectType->id,
        ]);

        $projectType->update($request->all());
        return redirect()->route('project_types.index')->with('success', 'تم تعديل النوع بنجاح');
    }

    public function destroy(ProjectType $projectType)
    {
        $projectType->delete();
        return redirect()->route('project_types.index')->with('success', 'تم حذف النوع بنجاح');
    }
}
