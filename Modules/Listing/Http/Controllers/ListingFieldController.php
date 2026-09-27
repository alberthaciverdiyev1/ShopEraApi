<?php

namespace Modules\Listing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Listing\Services\ListingFieldService;

/**
 * The choices of one field, on demand. Makes come with the section; models
 * arrive once a make is picked, which keeps thousands of rows off the wire.
 */
class ListingFieldController extends Controller
{
    public function __construct(private readonly ListingFieldService $fields)
    {
    }

    public function options(int $field, Request $request)
    {
        return $this->fields->options($field, $request);
    }
}
