<?php

namespace App\Models\Concerns;

trait TracksChanges
{
    /**
     * Get the unsaved changes to the model as raw before and after values for the activity log.
     *
     * @return array<string, array{from: mixed, to: mixed}>
     */
    public function pendingChanges(): array
    {
        $changes = [];

        foreach (array_keys($this->getDirty()) as $key) {
            $changes[$key] = ['from' => $this->getRawOriginal($key), 'to' => $this->getAttributes()[$key]];
        }

        return $changes;
    }
}
