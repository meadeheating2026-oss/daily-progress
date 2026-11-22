<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\DailyProgress;
use App\Models\Client;
use App\Models\Employee;
use App\Models\WorkItem;
use App\Models\ProjectTemplate;
use App\Models\ProjectType;

class RecycleBinController extends Controller
{
    public function index()
    {
        $deletedProjects = Project::onlyTrashed()->get();
        $deletedDailyProgress = DailyProgress::onlyTrashed()->get();
        $deletedClients = Client::onlyTrashed()->get();
        $deletedEmployees = Employee::onlyTrashed()->get();
        $deletedWorkItems = WorkItem::onlyTrashed()->get();
        $deletedTemplates = ProjectTemplate::onlyTrashed()->get();
        $deletedTypes = ProjectType::onlyTrashed()->get();

        return view('recycle-bin.index', compact(
            'deletedProjects',
            'deletedDailyProgress',
            'deletedClients',
            'deletedEmployees',
            'deletedWorkItems',
            'deletedTemplates',
            'deletedTypes'
        ));
    }

    public function restore($type, $id)
    {
        $model = $this->getModel($type);

        if ($model) {
            $item = $model::onlyTrashed()->findOrFail($id);
            $item->restore();
            return redirect()->back()->with('success', 'تم استعادة العنصر بنجاح');
        }

        return redirect()->back()->with('error', 'نوع العنصر غير صحيح');
    }

    public function forceDelete($type, $id)
    {
        $model = $this->getModel($type);

        if ($model) {
            $item = $model::onlyTrashed()->findOrFail($id);
            $item->forceDelete();
            return redirect()->back()->with('success', 'تم حذف العنصر نهائياً بنجاح');
        }

        return redirect()->back()->with('error', 'نوع العنصر غير صحيح');
    }

    private function getModel($type)
    {
        $models = [
            'project' => Project::class,
            'daily-progress' => DailyProgress::class,
            'client' => Client::class,
            'employee' => Employee::class,
            'work-item' => WorkItem::class,
            'template' => ProjectTemplate::class,
            'type' => ProjectType::class,
        ];

        return $models[$type] ?? null;
    }
}
