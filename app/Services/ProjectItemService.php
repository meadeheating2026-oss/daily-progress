<?php

namespace App\Services;

use App\Contracts\ProjectItemServiceInterface;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Repositories\ProjectItemRepository;
use Illuminate\Support\Facades\Log;

class ProjectItemService implements ProjectItemServiceInterface
{
    protected ProjectItemRepository $itemRepository;

    public function __construct(ProjectItemRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    public function createProjectItems(Project $project, array $items, array $validated): array
    {
        $itemMapping = [];
        $order = 0;

        // Honour the form's drag order instead of whatever PHP array order arrived.
        uasort($items, static function ($a, $b) {
            return ((int) ($a['item_order'] ?? PHP_INT_MAX)) <=> ((int) ($b['item_order'] ?? PHP_INT_MAX));
        });

        foreach ($items as $itemId => $item) {
            // تخطي البنود الفارغة في حالة المسودة
            if (($validated['save_as_draft'] ?? false) && empty($item['work_item_id'])) {
                continue;
            }

            // الحصول على lag من work_item إذا لم يتم تحديده
            $lag = $item['lag'] ?? 0;
            if ($lag === '' || $lag === null) {
                $workItem = $this->itemRepository->getWorkItemById($item['work_item_id']);
                $lag = $workItem->lag ?? 0;
            }

            $startDate = $item['start_date'] ?? $validated['start_date'] ?? now()->format('Y-m-d');
            $endDate = $item['end_date'] ?? $validated['end_date'] ?? now()->addDays(30)->format('Y-m-d');

            $projectItem = $this->itemRepository->createItem($project, [
                'work_item_id' => $item['work_item_id'],
                'total_quantity' => $item['total_quantity'] ?? 0,
                'estimated_daily_qty' => $item['estimated_daily_qty'] ?? 0,
                'duration' => $item['duration'] ?? 0,
                'completed_quantity' => 0,
                'remaining_quantity' => $item['total_quantity'] ?? 0,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'planned_end_date' => $endDate,
                'dependency_type' => $item['dependency_type'] ?? 'end_to_start',
                'lag' => (int)$lag,
                'notes' => $item['notes'] ?? null,
                'is_measurable' => isset($item['is_measurable']) ? (bool)$item['is_measurable'] : false,
                'subproject_name' => $item['subproject_name'] ?? null,
                'item_order' => $order++,
            ]);

            $itemMapping[$itemId] = $projectItem->id;
        }

        return $itemMapping;
    }

    public function syncProjectItems(Project $project, array $items, array $validated): array
    {
        Log::info('===== SYNC PROJECT ITEMS START =====');
        
        $existingItemIdsBeforeSync = $project->items()->pluck('id')->toArray();

        // The form's item_order is the source of truth after drag-and-drop.
        uasort($items, static function ($a, $b) {
            return ((int) ($a['item_order'] ?? PHP_INT_MAX)) <=> ((int) ($b['item_order'] ?? PHP_INT_MAX));
        });
        
        $itemMapping = [];
        // Start from the max submitted item_order so new items are appended after existing ones
        $submittedOrders = array_column($items, 'item_order');
        $order = count($submittedOrders) > 0 ? (max(array_map('intval', $submittedOrders)) + 1) : 0;
        $submittedItemIds = [];

        foreach ($items as $itemId => $item) {
            if (($validated['save_as_draft'] ?? false) && empty($item['work_item_id'])) {
                continue;
            }

            $lag = $item['lag'] ?? 0;
            if ($lag === '' || $lag === null) {
                $workItem = $this->itemRepository->getWorkItemById($item['work_item_id']);
                $lag = $workItem->lag ?? 0;
            }

            $startDate = $item['start_date'] ?? $validated['start_date'] ?? now()->format('Y-m-d');
            $endDate = $item['end_date'] ?? $validated['end_date'] ?? now()->addDays(30)->format('Y-m-d');

            if (!empty($item['id']) && is_numeric($item['id']) && $item['id'] !== 'NEW') {
                $existingItemId = (int)$item['id'];
                $submittedItemIds[] = $existingItemId;
                
                $existingItem = ProjectItem::find($existingItemId);
                $completedQty = $existingItem ? $existingItem->completed_quantity : 0;
                
                $itemData = [
                    'work_item_id' => $item['work_item_id'],
                    'total_quantity' => $item['total_quantity'] ?? 0,
                    'estimated_daily_qty' => $item['estimated_daily_qty'] ?? 0,
                    'duration' => $item['duration'] ?? 0,
                    'remaining_quantity' => ($item['total_quantity'] ?? 0) - $completedQty,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'planned_end_date' => $endDate,
                    'dependency_type' => $item['dependency_type'] ?? 'end_to_start',
                    'lag' => (int)$lag,
                    'notes' => $item['notes'] ?? null,
                    'is_measurable' => isset($item['is_measurable']) ? (bool)$item['is_measurable'] : false,
                    'subproject_name' => $item['subproject_name'] ?? null,
                    // Preserve the exact item_order from the form (set by drag-and-drop)
                    'item_order' => isset($item['item_order']) ? (int)$item['item_order'] : $order,
                ];
                $order++;
                
                $projectItem = $this->itemRepository->updateItem($existingItemId, $itemData);
                $itemMapping[$itemId] = $projectItem->id;
            } 
            else {
                $itemData = [
                    'work_item_id' => $item['work_item_id'],
                    'total_quantity' => $item['total_quantity'] ?? 0,
                    'estimated_daily_qty' => $item['estimated_daily_qty'] ?? 0,
                    'duration' => $item['duration'] ?? 0,
                    'completed_quantity' => 0,
                    'remaining_quantity' => $item['total_quantity'] ?? 0,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'planned_end_date' => $endDate,
                    'dependency_type' => $item['dependency_type'] ?? 'end_to_start',
                    'lag' => (int)$lag,
                    'notes' => $item['notes'] ?? null,
                    'is_measurable' => isset($item['is_measurable']) ? (bool)$item['is_measurable'] : false,
                    'subproject_name' => $item['subproject_name'] ?? null,
                    // New items get the next available order number
                    'item_order' => $order,
                ];
                $order++;
                
                $projectItem = $this->itemRepository->createItem($project, $itemData);
                $itemMapping[$itemId] = $projectItem->id;
            }
        }

        $itemsToDelete = array_diff($existingItemIdsBeforeSync, $submittedItemIds);
        
        if (!empty($itemsToDelete)) {
            $itemsWithProgress = ProjectItem::whereIn('id', $itemsToDelete)
                ->whereHas('dailyProgress')
                ->with('workItem')
                ->get();
            
            if ($itemsWithProgress->isNotEmpty()) {
                $itemNames = $itemsWithProgress->map(fn($item) => $item->workItem->name ?? 'Unknown')->join(', ');
                throw new \Exception(__('general.cannot_delete_items_with_progress') . ': ' . $itemNames);
            }
            
            foreach ($itemsToDelete as $itemId) {
                $this->itemRepository->deleteItem($itemId);
            }
        }

        Log::info('===== SYNC PROJECT ITEMS END =====');

        return $itemMapping;
    }

    public function updateItemPredecessors(array $items, array $itemMapping): void
    {
        foreach ($items as $itemId => $item) {
            if (!isset($itemMapping[$itemId])) {
                continue;
            }
            
            $projectItemId = (int)$itemMapping[$itemId];
            
            $predecessorValue = isset($item['predecessor']) && $item['predecessor'] !== ''
                ? trim($item['predecessor'])
                : null;

            if ($predecessorValue && isset($itemMapping[$predecessorValue])) {
                $realPredecessorId = (int)$itemMapping[$predecessorValue];
                $this->itemRepository->updateItemPredecessor($projectItemId, $realPredecessorId);
            } 
            else {
                $this->itemRepository->updateItemPredecessor($projectItemId, null);
            }
        }
    }

    public function reorderItems(Project $project, array $itemOrders): void
    {
        foreach ($itemOrders as $order => $itemId) {
            $project->items()->where('id', $itemId)->update(['item_order' => $order]);
        }
    }
}
