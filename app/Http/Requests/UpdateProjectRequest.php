<?php

namespace App\Http\Requests;

class UpdateProjectRequest extends StoreProjectRequest
{
    /**
     * On update the draft flag comes from the project itself rather than a hidden
     * field, so editing an unfinished draft stays as forgiving as creating one was.
     * A live project can never fall back to draft rules.
     */
    public function savingAsDraft(): bool
    {
        if (! $this->route('project')?->is_draft) {
            return false;
        }

        return ! $this->boolean('publish');
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'publish' => 'nullable|boolean',
            'items.*.temp_id' => 'nullable|string',
            'items.*.id' => 'nullable|integer|exists:project_items,id',
        ]);
    }
}
