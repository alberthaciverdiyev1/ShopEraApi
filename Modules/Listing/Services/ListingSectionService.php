<?php

namespace Modules\Listing\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Listing\Http\Entities\Listing;
use Modules\Listing\Http\Entities\ListingSection;
use Modules\Listing\Http\Resources\ListingSectionResource;
use Symfony\Component\HttpFoundation\Response as StatusCode;

/**
 * Sections are what makes the classifieds extendable: the admin creates one,
 * gives it fields, and the apps draw the form, the filter and the list from
 * what comes back here. No release is involved.
 */
class ListingSectionService
{
    /** What the apps show on the home screen and in the section picker. */
    public function published(): array
    {
        $sections = ListingSection::query()
            ->where('is_active', true)
            ->withCount(['listings' => fn ($query) => $query->visible()])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return ListingSectionResource::collection($sections)->resolve();
    }

    /** One section with its fields: everything the ad form and filter need. */
    public function show(string $key): ?ListingSection
    {
        return ListingSection::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->with(['fields' => fn ($query) => $query->where('is_active', true)->withCount('options')->orderBy('sort_order')])
            ->first();
    }

    public function adminList(Request $request)
    {
        $sections = ListingSection::query()
            ->when($request->filled('search'), fn ($query) => $query->where('key', 'ilike', '%' . $request->string('search') . '%'))
            ->withCount('listings')
            ->with(['fields' => fn ($query) => $query->withCount('options')->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, ListingSectionResource::collection($sections)->resolve());
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9\-]+$/', 'unique:listing_sections,key'],
            'name' => ['required'],
            'template' => ['nullable', Rule::in(ListingSection::TEMPLATES)],
            'title_template' => ['nullable', 'string', 'max:255'],
            'warning_text' => ['nullable'],
            'auto_approve' => ['nullable', 'boolean'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'icon' => ['nullable', 'image', 'max:4096'],
            // Telefondan çəkilmiş şəkil rahatlıqla 4 MB-ı keçir.
            'banner' => ['nullable', 'image', 'max:10240'],
        ]);

        $section = new ListingSection();
        $this->fill($section, $validated, $request);
        $section->save();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, (new ListingSectionResource($section))->resolve());
    }

    public function update(int $id, Request $request)
    {
        $section = ListingSection::find($id);

        if (! $section) {
            return responseHelper('Section not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'key' => ['sometimes', 'string', 'max:40', 'regex:/^[a-z0-9\-]+$/', Rule::unique('listing_sections', 'key')->ignore($section->id)],
            'name' => ['sometimes'],
            'template' => ['sometimes', Rule::in(ListingSection::TEMPLATES)],
            'title_template' => ['nullable', 'string', 'max:255'],
            'warning_text' => ['nullable'],
            'auto_approve' => ['sometimes', 'boolean'],
            'duration_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'icon' => ['nullable', 'image', 'max:4096'],
            // Telefondan çəkilmiş şəkil rahatlıqla 4 MB-ı keçir.
            'banner' => ['nullable', 'image', 'max:10240'],
        ]);

        $this->fill($section, $validated, $request);
        $section->save();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, (new ListingSectionResource($section->fresh()))->resolve());
    }

    /**
     * Soft delete only. Ads keep pointing at the section, so hiding it takes
     * the whole branch out of the apps without losing what people posted.
     */
    public function delete(int $id)
    {
        $section = ListingSection::withCount('listings')->find($id);

        if (! $section) {
            return responseHelper('Section not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $section->update(['is_active' => false]);
        $section->delete();

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, ['listings_kept' => (int) $section->listings_count]);
    }

    private function fill(ListingSection $section, array $validated, Request $request): void
    {
        foreach (['key', 'template', 'title_template', 'auto_approve', 'duration_days', 'is_active', 'sort_order'] as $field) {
            if (array_key_exists($field, $validated)) {
                $section->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('name', $validated)) {
            $section->setTranslations('name', $this->translations($validated['name']));
        }

        if (array_key_exists('warning_text', $validated)) {
            $section->setTranslations('warning_text', $this->translations($validated['warning_text']));
        }

        foreach (['icon' => 'icon_path', 'banner' => 'banner_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                $old = $section->{$column};
                $section->{$column} = $request->file($input)->store('listings/sections', 'public');

                if ($old) {
                    Storage::disk('public')->delete($old);
                }
            }
        }
    }

    /**
     * The panel may send one string or a map of languages. One string is
     * stored under the app's own locale rather than guessed at.
     *
     * @return array<string, string>
     */
    private function translations(mixed $value): array
    {
        if (is_array($value)) {
            return array_filter($value, fn ($text) => filled($text));
        }

        return [config('app.locale', 'az') => (string) $value];
    }
}
