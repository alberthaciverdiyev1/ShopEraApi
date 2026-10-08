<?php

namespace Modules\Marketplace\Support;

use Modules\Marketplace\Entities\CategoryField;

/**
 * The configurable fields of a marketplace listing. Each category can hide a
 * field or make it required; anything it does not configure keeps the default.
 */
class ListingFields
{
    /** @var array<int,string> */
    public const FIELDS = ['title', 'description', 'price', 'photos', 'city', 'condition', 'delivery'];

    /** @var array<string,array{visible:bool,required:bool}> */
    public const DEFAULTS = [
        'title' => ['visible' => true, 'required' => true],
        'description' => ['visible' => true, 'required' => true],
        'price' => ['visible' => true, 'required' => true],
        'photos' => ['visible' => true, 'required' => false],
        'city' => ['visible' => true, 'required' => true],
        'condition' => ['visible' => true, 'required' => false],
        'delivery' => ['visible' => true, 'required' => false],
    ];

    /** Effective schema for a category: defaults overridden by saved rows. */
    public static function forCategory(?int $categoryId): array
    {
        $schema = self::DEFAULTS;

        if ($categoryId) {
            $rows = CategoryField::query()->where('category_id', $categoryId)->get();
            foreach ($rows as $row) {
                if (! array_key_exists($row->field, $schema)) {
                    continue;
                }
                $schema[$row->field] = [
                    'visible' => (bool) $row->is_visible,
                    'required' => (bool) ($row->is_required && $row->is_visible),
                ];
            }
        }

        return $schema;
    }
}
