<?php

namespace Modules\Listing\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One answer an ad gave to one field. The column that carries it depends on
 * the field's type, which keeps "year between 2015 and 2020" an indexed
 * lookup rather than a scan through JSON.
 */
class ListingValue extends Model
{
    protected $table = 'listing_values';

    protected $guarded = [];

    protected $casts = [
        'value_number' => 'decimal:4',
        'value_bool' => 'boolean',
        'value_date' => 'date',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ListingAttribute::class, 'attribute_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ListingAttributeOption::class, 'option_id');
    }
}
