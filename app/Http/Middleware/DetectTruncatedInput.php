<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * PHP stops parsing a request body once it has read `max_input_vars` fields and
 * discards the rest without raising anything Laravel can see. A project form
 * with a few hundred items easily exceeds the limit, and the missing rows then
 * look like the user deleted them, so the save silently destroys data.
 *
 * Reaching the limit is therefore treated as a failed request rather than a
 * partial one.
 */
class DetectTruncatedInput
{
    public function handle(Request $request, Closure $next): Response
    {
        $limit    = (int) ini_get('max_input_vars');
        $received = $this->countInputVars($request->request->all());

        if ($limit > 0 && $received >= $limit) {
            Log::warning('Request truncated by max_input_vars', [
                'url'      => $request->fullUrl(),
                'limit'    => $limit,
                'received' => $received,
            ]);

            throw ValidationException::withMessages([
                'form' => __('general.request_truncated', [
                    'received' => $received,
                    'limit'    => $limit,
                ]),
            ]);
        }

        return $next($request);
    }

    /**
     * Count leaf values the way PHP counts input variables: `items[3][name]`
     * is one variable, not three.
     */
    protected function countInputVars(array $input): int
    {
        $count = 0;

        array_walk_recursive($input, function () use (&$count) {
            $count++;
        });

        return $count;
    }
}
