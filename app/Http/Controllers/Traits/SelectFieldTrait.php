<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * SelectFieldTrait
 * 
 * Provides standardized methods for API endpoints that return searchable select field data.
 * Formats results in the Select2 JSON format with pagination support.
 */
trait SelectFieldTrait
{
    /**
     * Format search results for Select2 AJAX response
     * 
     * @param Builder $query - Eloquent query builder
     * @param int $page - Current page number (1-based)
     * @param int $limit - Results per page
     * @param callable $formatter - Function to format each item: fn($item) => ['id' => ..., 'text' => ...]
     * @return array - Select2 format: {results: [], pagination: {more: bool}}
     */
    protected function formatSelectResults($query, $page, $formatter, $limit = 10)
    {
        // Paginate the results
        $paginated = $query->paginate($limit, ['*'], 'page', $page);

        // Format results using provided formatter
        $results = $paginated->items();
        $formatted = array_map($formatter, $results);

        return [
            'results' => $formatted,
            'pagination' => [
                'more' => $paginated->hasMorePages()
            ]
        ];
    }

    /**
     * Default formatter for database models
     * Customize by overriding in your controller
     * 
     * @param mixed $item
     * @return array
     */
    protected function getDefaultFormatter()
    {
        return function ($item) {
            return [
                'id' => $item->id,
                'text' => (string)$item
            ];
        };
    }

    /**
     * Validate search input
     * 
     * @param string $query - Search query
     * @param int $maxLength - Max query length to prevent abuse
     * @return string - Sanitized query
     */
    protected function sanitizeSearchQuery($query, $maxLength = 100)
    {
        $query = trim($query ?? '');
        $query = substr($query, 0, $maxLength);
        
        return $query;
    }

    /**
     * Check if search query meets minimum requirements
     * 
     * @param string $query
     * @param int $minLength
     * @return bool
     */
    protected function isValidSearchQuery($query, $minLength = 1)
    {
        return strlen(trim($query ?? '')) >= $minLength;
    }
}
