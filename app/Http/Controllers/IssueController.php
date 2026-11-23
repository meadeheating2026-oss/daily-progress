<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\IssueAttachment;
use App\Models\Project;
use App\Models\User;
use App\Models\Employee;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * IssueController
 * 
 * Handles all issue management operations including CRUD, Kanban board, and statistics
 */
class IssueController extends Controller
{
    /**
     * Constructor - Set up middleware for authorization
     */
    public function __construct()
    {
        $this->middleware('can:issues-list')->only(['index', 'kanban', 'statistics']);
        $this->middleware('can:issues-create')->only(['create', 'store']);
        $this->middleware('can:issues-edit')->only(['edit', 'update']);
        $this->middleware('can:issues-delete')->only('destroy');
        $this->middleware('can:issues-view')->only('show');
    }

    /**
     * Display a listing of issues with filters and pagination
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Issue::with(['project:id,name', 'reporter:id,name', 'assignedUser:id,name']);

        // Filter by user's accessible projects via SQL subquery (scales to 10,000+ projects without 10,000 bind params)
        if (!$this->applyAccessibleProjectsScope($query, $user)) {
            $query->whereRaw('1 = 0');
        }

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('module')) {
            $query->where('module', 'like', '%' . $request->module . '%');
        }

        if ($request->filled('deadline_from')) {
            $query->where('deadline', '>=', $request->deadline_from);
        }

        if ($request->filled('deadline_to')) {
            $query->where('deadline', '<=', $request->deadline_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('module', 'like', '%' . $search . '%');
            });
        }

        $projects = $this->getUserProjects($user, 500);
        $statistics = $this->getStatistics($user);
        $users = User::select('id', 'name')->orderBy('name')->get();
        $modules = Issue::whereNotNull('module')
            ->distinct()
            ->pluck('module')
            ->filter()
            ->sort()
            ->values();

        $issues = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('issues.index', compact('issues', 'statistics', 'projects', 'users', 'modules'));
    }

    /**
     * Display Kanban board view
     */
    public function kanban(Request $request)
    {
        $user = Auth::user();
        $query = Issue::with(['project:id,name', 'reporter:id,name', 'assignedUser:id,name']);

        if (!$this->applyAccessibleProjectsScope($query, $user)) {
            $query->whereRaw('1 = 0');
        }

        // Apply filters (same as index)
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Group issues by status (capped per column so 10,000 projects never freeze the browser)
        $issuesByStatus = [
            'New' => (clone $query)->where('status', 'New')->orderBy('priority', 'desc')->orderBy('created_at', 'desc')->limit(100)->get(),
            'In Progress' => (clone $query)->where('status', 'In Progress')->orderBy('priority', 'desc')->orderBy('updated_at', 'desc')->limit(100)->get(),
            'Testing' => (clone $query)->where('status', 'Testing')->orderBy('priority', 'desc')->orderBy('updated_at', 'desc')->limit(100)->get(),
            'Closed' => (clone $query)->where('status', 'Closed')->orderBy('updated_at', 'desc')->limit(100)->get(),
        ];

        $projects = $this->getUserProjects($user, 500);
        $users = User::select('id', 'name')->orderBy('name')->get();

        return view('issues.kanban', compact('issuesByStatus', 'projects', 'users'));
    }

    /**
     * Get statistics for dashboard
     */
    public function statistics()
    {
        $statistics = $this->getStatistics();
        
        return response()->json($statistics);
    }

    /**
     * Show the form for creating a new issue
     */
    public function create()
    {
        $user = Auth::user();
        $projects = $this->getUserProjects($user);
        $users = User::orderBy('name')->get();

        return view('issues.create', compact('projects', 'users'));
    }

    /**
     * Store a newly created issue
     */
    public function store(StoreIssueRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['reporter_id'] = Auth::id();
            $validated['status'] = $validated['status'] ?? 'New';

            $issue = Issue::create($validated);

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                $this->storeAttachments($issue, $request->file('attachments'));
            }

            return redirect()
                ->route('issues.show', $issue)
                ->with('success', __('general.issue_created_successfully'));

        } catch (\Exception $e) {
            Log::error('Error creating issue', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', __('general.error_creating_issue') . ': ' . $e->getMessage());
        }
    }

    /**
     * Display the specified issue
     */
    public function show(Issue $issue)
    {
        $issue->load([
            'project',
            'reporter',
            'assignedUser',
            'comments.user',
            'attachments.user'
        ]);

        return view('issues.show', compact('issue'));
    }

    /**
     * Show the form for editing the specified issue
     */
    public function edit(Issue $issue)
    {
        $user = Auth::user();
        $projects = $this->getUserProjects($user);
        $users = User::orderBy('name')->get();

        return view('issues.edit', compact('issue', 'projects', 'users'));
    }

    /**
     * Update the specified issue
     */
    public function update(UpdateIssueRequest $request, Issue $issue)
    {
        try {
            $validated = $request->validated();

            $issue->update($validated);

            // Handle new file attachments
            if ($request->hasFile('attachments')) {
                $this->storeAttachments($issue, $request->file('attachments'));
            }

            return redirect()
                ->route('issues.show', $issue)
                ->with('success', __('general.issue_updated_successfully'));

        } catch (\Exception $e) {
            Log::error('Error updating issue', [
                'issue_id' => $issue->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', __('general.error_updating_issue') . ': ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified issue
     */
    public function destroy(Issue $issue)
    {
        try {
            // Delete all attachments
            foreach ($issue->attachments as $attachment) {
                if (Storage::exists($attachment->file_path)) {
                    Storage::delete($attachment->file_path);
                }
            }

            $issue->delete();

            return redirect()
                ->route('issues.index')
                ->with('success', __('general.issue_deleted_successfully'));

        } catch (\Exception $e) {
            Log::error('Error deleting issue', [
                'issue_id' => $issue->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->with('error', __('general.error_deleting_issue') . ': ' . $e->getMessage());
        }
    }

    /**
     * Add a comment to an issue
     */
    public function addComment(Request $request, Issue $issue)
    {
        $request->validate([
            'comment' => 'required|string|max:5000',
        ]);

        $comment = IssueComment::create([
            'issue_id' => $issue->id,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
        ]);

        return back()->with('success', __('general.comment_added_successfully'));
    }

    /**
     * Delete a comment
     */
    public function deleteComment(IssueComment $comment)
    {
        // Only allow comment owner or issue reporter to delete
        if ($comment->user_id !== Auth::id() && $comment->issue->reporter_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $comment->delete();

        return back()->with('success', __('general.comment_deleted_successfully'));
    }

    /**
     * Download an attachment
     */
    public function downloadAttachment(IssueAttachment $attachment)
    {
        // Check if user has access to the issue
        $user = Auth::user();
        $issue = $attachment->issue;
        
        // Check if user can access this issue's project
        $userProjects = $this->getUserProjects($user);
        if (!$userProjects->contains('id', $issue->project_id)) {
            abort(403, 'Unauthorized');
        }

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Delete an attachment
     */
    public function deleteAttachment(IssueAttachment $attachment)
    {
        // Only allow attachment owner or issue reporter to delete
        if ($attachment->user_id !== Auth::id() && $attachment->issue->reporter_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if (Storage::exists($attachment->file_path)) {
            Storage::delete($attachment->file_path);
        }

        $attachment->delete();

        return back()->with('success', __('general.attachment_deleted_successfully'));
    }

    /**
     * Update issue status (for Kanban board drag & drop)
     */
    public function updateStatus(Request $request, Issue $issue)
    {
        $request->validate([
            'status' => 'required|in:New,In Progress,Testing,Closed',
        ]);

        $issue->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => __('general.status_updated_successfully')
        ]);
    }

    /**
     * Store file attachments for an issue
     */
    private function storeAttachments(Issue $issue, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store('issues/attachments', 'public');
            
            IssueAttachment::create([
                'issue_id' => $issue->id,
                'user_id' => Auth::id(),
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }
    }

    /**
     * Get statistics for issues filtered by user's accessible projects
     */
    private function getStatistics(?User $user = null): array
    {
        $user = $user ?? Auth::user();

        $baseQuery = Issue::query();
        if (!$this->applyAccessibleProjectsScope($baseQuery, $user)) {
            return [
                'total_open'   => 0,
                'total_closed' => 0,
                'by_status'    => ['New' => 0, 'In Progress' => 0, 'Testing' => 0, 'Closed' => 0],
                'by_priority'  => ['Low' => 0, 'Medium' => 0, 'High' => 0, 'Urgent' => 0],
                'overdue'      => 0,
                'by_project'   => collect([]),
            ];
        }

        // Single SQL pass instead of 11 separate COUNT(*) queries
        $totals = (clone $baseQuery)->selectRaw("
            SUM(CASE WHEN status != 'Closed' THEN 1 ELSE 0 END) as total_open,
            SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as total_closed,
            SUM(CASE WHEN status = 'New' THEN 1 ELSE 0 END) as st_new,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as st_in_progress,
            SUM(CASE WHEN status = 'Testing' THEN 1 ELSE 0 END) as st_testing,
            SUM(CASE WHEN priority = 'Low' THEN 1 ELSE 0 END) as pr_low,
            SUM(CASE WHEN priority = 'Medium' THEN 1 ELSE 0 END) as pr_medium,
            SUM(CASE WHEN priority = 'High' THEN 1 ELSE 0 END) as pr_high,
            SUM(CASE WHEN priority = 'Urgent' THEN 1 ELSE 0 END) as pr_urgent,
            SUM(CASE WHEN deadline IS NOT NULL AND deadline < ? AND status != 'Closed' THEN 1 ELSE 0 END) as overdue_cnt
        ", [now()])->first();

        $byProject = (clone $baseQuery)
            ->selectRaw('project_id, COUNT(*) as count')
            ->groupBy('project_id')
            ->orderByDesc('count')
            ->limit(25)
            ->with('project:id,name')
            ->get()
            ->map(fn($item) => [
                'project_name' => $item->project->name ?? 'N/A',
                'count'        => (int) $item->count,
            ]);

        return [
            'total_open'   => (int) ($totals->total_open ?? 0),
            'total_closed' => (int) ($totals->total_closed ?? 0),
            'by_status'    => [
                'New'         => (int) ($totals->st_new ?? 0),
                'In Progress' => (int) ($totals->st_in_progress ?? 0),
                'Testing'     => (int) ($totals->st_testing ?? 0),
                'Closed'      => (int) ($totals->total_closed ?? 0),
            ],
            'by_priority'  => [
                'Low'    => (int) ($totals->pr_low ?? 0),
                'Medium' => (int) ($totals->pr_medium ?? 0),
                'High'   => (int) ($totals->pr_high ?? 0),
                'Urgent' => (int) ($totals->pr_urgent ?? 0),
            ],
            'overdue'      => (int) ($totals->overdue_cnt ?? 0),
            'by_project'   => $byProject,
        ];
    }

    /**
     * Constrain an Issue query to non-draft, non-archived projects accessible by $user using SQL subqueries (no 10,000-ID IN list).
     */
    private function applyAccessibleProjectsScope($query, User $user): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            $query->whereHas('project', function ($q) {
                $q->where('is_draft', false)->where('is_archived', false);
            });
            return true;
        }

        $employeeId = Employee::where('user_id', $user->id)->value('id');
        if (!$employeeId) {
            return false;
        }

        $query->whereHas('project', function ($q) use ($employeeId) {
            $q->where('is_draft', false)
              ->where('is_archived', false)
              ->whereHas('employees', fn($eq) => $eq->where('employees.id', $employeeId));
        });
        return true;
    }

    private function getUserProjects(User $user, int $limit = 500)
    {
        $query = Project::where('is_draft', false)
            ->where('is_archived', false)
            ->select('id', 'name')
            ->orderBy('name');

        if (!$user->hasRole('admin') && !$user->hasRole('manager')) {
            $employeeId = Employee::where('user_id', $user->id)->value('id');
            if (!$employeeId) {
                return collect([]);
            }
            $query->whereHas('employees', function ($q) use ($employeeId) {
                $q->where('employees.id', $employeeId);
            });
        }

        return $query->limit($limit)->get();
    }
}
