<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'name',
        'description',
        'client_id',
        'start_date',
        'end_date',
        'status',
        'working_zone',
        'project_type_id',
        'working_days',
        'daily_work_hours',
        'weekly_holidays',
        'is_draft',
        'is_archived',
        'archived_at',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_project', 'project_id', 'employee_id');
    }

    public function projectItems()
    {
        return $this->hasMany(ProjectItem::class)->orderBy('item_order');
    }

    public function type()
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(ProjectItem::class)->orderBy('item_order');
    }

    /**
     * Get all issues for the project
     */
    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    public function subprojects()
    {
        return $this->hasMany(Subproject::class)->orderBy('sort_order')->orderBy('name');
    }

    public function dailyProgress()
    {
        return $this->hasManyThrough(DailyProgress::class, ProjectItem::class);
    }

    /**
     * Get items grouped by category
     */
    public function getItemsGroupedByCategory()
    {
        return $this->items()
            ->with('workItem.category')
            ->get()
            ->groupBy(function($item) {
                return $item->workItem->category->name ?? __('general.uncategorized');
            });
    }

    /**
     * Calculate total duration of all items
     */
    public function getTotalDurationAttribute()
    {
        return $this->items()->sum('duration');
    }

    /**
     * Calculate total lag of all items
     */
    public function getTotalLagAttribute()
    {
        return $this->items()->sum('lag');
    }

    /**
     * Check if project has any items with notes
     */
    public function getHasNotesAttribute()
    {
        return $this->items()->whereNotNull('notes')->exists();
    }

    /**
     * Calculate overall project progress percentage
     * يحسب نسبة الإنجاز الإجمالية للمشروع بناءً على completed_quantity
     * ✅ يحسب فقط من البنود القابلة للقياس (is_measurable = true)
     * يتعامل بشكل صحيح مع البنود المكررة (كل بند له project_item_id منفصل)
     */
    public function getOverallProgressAttribute()
    {
        return app(\App\Contracts\ProjectProgressServiceInterface::class)->calculateOverallProgress($this);
    }

    /**
     * هل المشروع يحتوي على مشاريع فرعية لم تكتمل أوزانها إلى 100% (أو أوزانها 0%)؟
     */
    public function getSubprojectsWeightStatusAttribute(): array
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->select('id', 'project_id', 'subproject_name')->get();
        $subprojects = $this->relationLoaded('subprojects') ? $this->subprojects : $this->subprojects()->get();

        $namesWithItems = $items->pluck('subproject_name')->filter(fn($n) => !empty($n))->unique()->values()->all();
        if (empty($namesWithItems)) {
            return [
                'has_subprojects' => false,
                'total_weight'    => 0.0,
                'is_zero'         => false,
                'is_incomplete'   => false,
            ];
        }

        $withoutName = __('general.without_subproject');
        $hasUnassigned = $items->contains(fn($it) => empty($it->subproject_name));

        $activeSubprojects = $subprojects->filter(function ($sp) use ($namesWithItems, $hasUnassigned, $withoutName) {
            return in_array($sp->name, $namesWithItems, true)
                || ($hasUnassigned && $sp->name === $withoutName);
        });

        $totalWeight = round((float) $activeSubprojects->sum(fn($sp) => (float) ($sp->weight ?? 0)), 2);

        return [
            'has_subprojects' => true,
            'total_weight'    => $totalWeight,
            'is_zero'         => $totalWeight <= 0.0,
            'is_incomplete'   => abs($totalWeight - 100.0) > 0.01,
        ];
    }

    /**
     * Scope for active projects
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for draft projects
     */
    public function scopeDraft($query)
    {
        return $query->where('is_draft', true);
    }

    /**
     * Scope for published projects
     */
     public function scopePublished($query)
     {
         return $query->where('is_draft', false);
     }

    /**
     * Scope for archived projects
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope for unarchived projects
     */
    public function scopeUnarchived($query)
    {
        return $query->where('is_archived', false);
    }
}
