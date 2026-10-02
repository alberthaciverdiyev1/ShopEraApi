<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Shared behaviour for every admin page: the sidebar title, permission checks
 * and the small helpers used to answer htmx partials.
 */
abstract class AdminController extends Controller
{
    protected string $title = 'Panel';

    protected function requirePermission(string $permission): void
    {
        abort_unless(admin_can($permission), 403, __('Bu əməliyyat üçün icazəniz yoxdur.'));
    }

    protected function isHtmx(Request $request): bool
    {
        return $request->header('HX-Request') !== null;
    }

    /**
     * Tell htmx to close the modal / show a toast after swapping the table.
     */
    protected function htmxTriggers(array $events): string
    {
        return json_encode($events, JSON_THROW_ON_ERROR);
    }
}
