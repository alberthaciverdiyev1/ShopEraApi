<?php

namespace Modules\Listing\Services;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Listing\Http\Entities\ListingAttribute;
use Modules\Listing\Http\Entities\ListingAttributeOption;
use Modules\Listing\Http\Entities\ListingSection;
use Modules\Listing\Http\Entities\ListingValue;
use Modules\Listing\Http\Resources\ListingFieldResource;
use Modules\Listing\Http\Resources\ListingOptionResource;
use Symfony\Component\HttpFoundation\Response as StatusCode;

/**
 * The fields of a section and their choices - the other half of the
 * constructor. A field decides what the ad form asks, what the filter offers
 * and what the card shows; a child field (model under make) narrows its
 * choices to the option picked in its parent.
 */
class ListingFieldService
{
    private const MAX_OPTIONS_PER_CALL = 5000;

    public function store(int $sectionId, Request $request)
    {
        $section = ListingSection::find($sectionId);

        if (! $section) {
            return responseHelper('Section not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $validated = $request->validate($this->rules($sectionId));

        $field = new ListingAttribute(['section_id' => $section->id]);
        $error = $this->fill($field, $validated, $section);

        if ($error) {
            return $error;
        }

        $field->save();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, $this->present($field));
    }

    public function update(int $fieldId, Request $request)
    {
        $field = ListingAttribute::find($fieldId);

        if (! $field) {
            return responseHelper('Field not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $validated = $request->validate($this->rules($field->section_id, $field->id, true));
        $error = $this->fill($field, $validated, $field->section);

        if ($error) {
            return $error;
        }

        $field->save();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, $this->present($field->fresh()));
    }

    /**
     * A field nobody answered is removed outright. One with answers behind it
     * is only switched off: deleting it would take those answers with it.
     */
    public function destroy(int $fieldId)
    {
        $field = ListingAttribute::find($fieldId);

        if (! $field) {
            return responseHelper('Field not found.', StatusCode::HTTP_NOT_FOUND);
        }

        if (ListingValue::where('attribute_id', $field->id)->exists()) {
            $field->update(['is_active' => false]);

            return responseHelper('Operation successful.', StatusCode::HTTP_OK, ['deactivated' => true]);
        }

        $field->delete();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, ['deleted' => true]);
    }

    /**
     * The choices of one field, asked for on demand: a model list runs to
     * thousands of rows, so it is never inlined into the section payload.
     */
    public function options(int $fieldId, Request $request)
    {
        $field = ListingAttribute::find($fieldId);

        if (! $field || ! $field->usesOptions()) {
            return responseHelper('Field not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $perPage = min(max((int) $request->integer('per_page', 200), 1), 500);

        $options = $field->options()
            ->where('is_active', true)
            ->when($request->filled('parent_option_id'), fn ($query) => $query->where('parent_option_id', $request->integer('parent_option_id')))
            ->when($request->filled('search'), fn ($query) => $query->where('value', 'ilike', '%' . $request->string('search') . '%'))
            ->orderBy('sort_order')
            ->orderBy('value')
            ->paginate($perPage);

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'items' => ListingOptionResource::collection($options->items())->resolve(),
            'total' => $options->total(),
            'per_page' => $options->perPage(),
            'current_page' => $options->currentPage(),
            'last_page' => $options->lastPage(),
        ]);
    }

    /**
     * Bulk upsert, because choices arrive in lists: every make in the country,
     * every model of a make. Rows already used by an ad are never deleted -
     * with `replace` they are switched off instead.
     */
    public function saveOptions(int $fieldId, Request $request)
    {
        $field = ListingAttribute::find($fieldId);

        if (! $field || ! $field->usesOptions()) {
            return responseHelper('Field not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'replace' => ['nullable', 'boolean'],
            'options' => ['required', 'array', 'min:1', 'max:' . self::MAX_OPTIONS_PER_CALL],
            'options.*.value' => ['required', 'string', 'max:80'],
            'options.*.label' => ['required'],
            // The parent's option this one belongs under, named by its value:
            // "X5" arrives with parent_value "bmw".
            'options.*.parent_value' => ['nullable', 'string', 'max:80'],
            'options.*.sort_order' => ['nullable', 'integer'],
            // A choice may stand for a badge - "Təmirli" is the repair badge -
            // and a colour choice carries the swatch the ad form paints.
            'options.*.badge_key' => ['nullable', 'string', 'max:24'],
            'options.*.color_hex' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $parentIds = $field->parent_id
            ? ListingAttributeOption::where('attribute_id', $field->parent_id)->pluck('id', 'value')
            : collect();

        $seen = [];

        foreach ($validated['options'] as $index => $row) {
            $parentValue = $row['parent_value'] ?? null;

            if ($parentValue !== null && ! $parentIds->has($parentValue)) {
                return responseHelper('Unknown parent option: ' . $parentValue, StatusCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $option = ListingAttributeOption::firstOrNew([
                'attribute_id' => $field->id,
                'value' => $row['value'],
            ]);

            $option->setTranslations('label', $this->translations($row['label']));
            $option->parent_option_id = $parentValue !== null ? $parentIds[$parentValue] : $option->parent_option_id;
            $option->sort_order = $row['sort_order'] ?? $index;
            if (array_key_exists('badge_key', $row)) {
                $option->badge_key = $row['badge_key'] ?: null;
            }
            if (array_key_exists('color_hex', $row)) {
                $option->color_hex = $row['color_hex'] ?: null;
            }
            $option->is_active = true;
            $option->save();

            $seen[] = $option->id;
        }

        $deactivated = 0;

        if ($request->boolean('replace')) {
            $deactivated = ListingAttributeOption::where('attribute_id', $field->id)
                ->whereNotIn('id', $seen)
                ->update(['is_active' => false]);
        }

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'saved' => count($seen),
            'deactivated' => $deactivated,
        ]);
    }

    public function destroyOption(int $optionId)
    {
        $option = ListingAttributeOption::find($optionId);

        if (! $option) {
            return responseHelper('Option not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $used = ListingValue::where('option_id', $option->id)->exists()
            || ListingAttributeOption::where('parent_option_id', $option->id)->exists();

        if ($used) {
            $option->update(['is_active' => false]);

            return responseHelper('Operation successful.', StatusCode::HTTP_OK, ['deactivated' => true]);
        }

        $option->delete();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, ['deleted' => true]);
    }

    private function rules(int $sectionId, ?int $ignoreId = null, bool $optional = false): array
    {
        $required = $optional ? 'sometimes' : 'required';

        return [
            'key' => [$required, 'string', 'max:40', 'regex:/^[a-z0-9_]+$/', Rule::unique('listing_attributes', 'key')->where('section_id', $sectionId)->ignore($ignoreId)],
            'label' => [$required],
            'type' => [$required, Rule::in(ListingAttribute::TYPES)],
            'unit' => ['nullable', 'string', 'max:16'],
            'is_required' => ['nullable', 'boolean'],
            'in_filter' => ['nullable', 'boolean'],
            'in_card' => ['nullable', 'boolean'],
            'is_range' => ['nullable', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:listing_attributes,id'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            // Which badge this field's answer stands for, and which answer
            // earns it. Several fields may share a key: the badge then needs
            // all of them to agree.
            'badge_key' => ['nullable', 'string', 'max:24'],
            'badge_when' => ['nullable', Rule::in(['true', 'false'])],
        ];
    }

    /** @return mixed null when the field is fine, a response when it is not */
    private function fill(ListingAttribute $field, array $validated, ListingSection $section): mixed
    {
        foreach (['key', 'type', 'unit', 'is_required', 'in_filter', 'in_card', 'is_range', 'is_active', 'sort_order', 'badge_key', 'badge_when'] as $column) {
            if (array_key_exists($column, $validated)) {
                $field->{$column} = $validated[$column];
            }
        }

        if (array_key_exists('label', $validated)) {
            $field->setTranslations('label', $this->translations($validated['label']));
        }

        if (array_key_exists('parent_id', $validated)) {
            $parentId = $validated['parent_id'];

            if ($parentId !== null) {
                $parent = ListingAttribute::find($parentId);

                // A child narrows its choices by the parent's chosen option,
                // so both have to be choice fields in the same section, and a
                // field cannot follow itself.
                if (! $parent || $parent->section_id !== $section->id || ! $parent->usesOptions() || $parent->id === $field->id) {
                    return responseHelper('A field can only follow a choice field of the same section.', StatusCode::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            $field->parent_id = $parentId;
        }

        if ($field->parent_id && ! $field->usesOptions()) {
            return responseHelper('Only a choice field can follow another field.', StatusCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        // A video and a point have nothing to match or to print on a card, so
        // the flags are cleared rather than refused - the panel simply never
        // shows them switched on.
        if (in_array($field->type, ['youtube', 'location'], true)) {
            $field->in_filter = false;
            $field->is_range = false;
            $field->in_card = false;
            $field->unit = null;
        }

        return null;
    }

    private function present(ListingAttribute $field): array
    {
        return (new ListingFieldResource($field->loadCount('options')))->resolve();
    }

    /** @return array<string, string> */
    private function translations(mixed $value): array
    {
        if (is_array($value)) {
            return array_filter($value, fn ($text) => filled($text));
        }

        return [config('app.locale', 'az') => (string) $value];
    }
}
