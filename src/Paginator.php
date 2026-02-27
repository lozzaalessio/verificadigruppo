<?php

declare(strict_types=1);

namespace App;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Paginazione semplice su array già recuperato dal DB.
 *
 * Parametri query-string:
 *   ?page=1        (default 1)
 *   ?per_page=20   (default 20, max 100)
 */
class Paginator
{
    public static function paginate(array $data, Request $request): array
    {
        $params  = $request->getQueryParams();
        $page    = max(1, (int)($params['page']     ?? 1));
        $perPage = min(100, max(1, (int)($params['per_page'] ?? 20)));
        $total   = count($data);
        $pages   = (int)ceil($total / $perPage);
        $slice   = array_slice($data, ($page - 1) * $perPage, $perPage);

        return [
            'data' => $slice,
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => max(1, $pages),
            ],
        ];
    }
}
