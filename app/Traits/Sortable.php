<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait Sortable
{
    /**
     * Apply sorting dari query string
     * 
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySorting($query, Request $request)
    {
        $defaultColumn = $this->orderColumn ?? 'created_at';
        $sortColumn = $request->get('sort', $defaultColumn);
        $sortDirection = $request->get('direction', 'desc');

        // Whitelist kolom yang boleh di-sort
        $allowedSorts = $this->sortable ?? [$defaultColumn];
        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = $defaultColumn;
        }

        // Validasi direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        return $query->orderBy($sortColumn, $sortDirection);
    }
}