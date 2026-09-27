<?php

namespace Modules\Store\Services;

use App\Services\Notification\AdminNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Setting\Http\Entities\Setting;
use Modules\Setting\Services\SettingService;
use Modules\Store\Http\Entities\Store;

class StoreService
{
    public function instructions(): array
    {
        $instructions = Setting::query()->value('seller_instructions') ?? [];
        if (is_string($instructions)) {
            $instructions = json_decode($instructions, true) ?: [];
        }

        return [
            'instructions' => $instructions,
            'commission_percent' => app(SettingService::class)->getStoreCommissionPercent(),
            'negative_balance_limit' => app(SettingService::class)->getStoreNegativeBalanceLimit(),
            'handover_hours' => app(SettingService::class)->getStoreHandoverHours(),
            'late_penalty_amount' => app(SettingService::class)->getStoreLatePenaltyAmount(),
        ];
    }

    public function register(Request $request): Store
    {
        $data = $request->validate([
            'owner_full_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:2000'],
            'identity_front' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'identity_back' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'instructions_accepted' => ['accepted'],
        ]);

        $existing = Store::withTrashed()->where('user_id', $request->user()->id)->first();
        if ($existing && ! in_array($existing->status, ['rejected', 'changes_requested'], true)) {
            throw ValidationException::withMessages(['store' => 'Bu istifadəçi üçün artıq mağaza müraciəti mövcuddur.']);
        }

        $frontPath = $request->file('identity_front')->store('store-identity-documents');
        $backPath = $request->file('identity_back')->store('store-identity-documents');
        $logoPath = $request->hasFile('logo')
            ? compressAndUploadImage($request->file('logo'), 'stores', 'logo')
            : null;

        if ($existing) {
            Storage::delete(array_filter([$existing->identity_front_path, $existing->identity_back_path]));
            $existing->restore();
            $existing->update([
                'owner_full_name' => $data['owner_full_name'],
                'name' => $data['name'],
                'about' => $data['about'] ?? $existing->about,
                'identity_front_path' => $frontPath,
                'identity_back_path' => $backPath,
                'logo_path' => $logoPath ?? $existing->logo_path,
                'status' => 'pending',
                'is_active' => false,
                'is_trusted' => false,
                'rejection_reason' => null,
                'rejected_at' => null,
                'instructions_accepted_at' => now(),
            ]);

            $existing->refresh();
            $this->notifyAdminsOfApplication($existing);

            return $existing;
        }

        $created = Store::create([
            'user_id' => $request->user()->id,
            'owner_full_name' => $data['owner_full_name'],
            'name' => $data['name'],
            'about' => $data['about'] ?? null,
            'identity_front_path' => $frontPath,
            'identity_back_path' => $backPath,
            'logo_path' => $logoPath,
            'instructions_accepted_at' => now(),
        ]);

        $this->notifyAdminsOfApplication($created);

        return $created;
    }

    /**
     * Tells staff a store is waiting in the approval queue.
     *
     * Nothing announced a new application before, so it sat until someone
     * happened to open the queue.
     */
    private function notifyAdminsOfApplication(Store $store): void
    {
        app(AdminNotifier::class)->notify(
            __('Yeni mağaza müraciəti'),
            __('":name" mağazası təsdiq gözləyir.', ['name' => $store->name]),
            [
                'type' => 'admin_store_application',
                'store_id' => (string) $store->id,
            ],
        );
    }
}
