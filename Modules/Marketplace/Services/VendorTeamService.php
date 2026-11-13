<?php

namespace Modules\Marketplace\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Marketplace\Entities\Vendor;
use Modules\Marketplace\Entities\VendorInvitation;
use Modules\Marketplace\Entities\VendorMember;
use Modules\User\Entities\User;

/**
 * Store teams: the owner invites people by link and picks, per member, which
 * parts of the store they may manage.
 */
class VendorTeamService
{
    /** Every permission a team member can be given. */
    public const PERMISSIONS = [
        'listings' => 'Elanları idarə et',
        'promotions' => 'Elanları irəli çək / premium et',
        'orders' => 'Sifarişləri idarə et',
        'messages' => 'Mesajlara cavab ver',
        'stats' => 'Statistika və hesabatlar',
        'settings' => 'Mağaza məlumatlarını redaktə et',
    ];

    private const INVITE_DAYS = 7;

    public function permissions(): array
    {
        $list = [];

        foreach (self::PERMISSIONS as $key => $label) {
            $list[] = ['key' => $key, 'label' => $label];
        }

        return $list;
    }

    public function ownedVendor(User $user): ?Vendor
    {
        return Vendor::query()->where('user_id', $user->id)->first();
    }

    public function membership(User $user): ?VendorMember
    {
        return VendorMember::query()
            ->with('vendor')
            ->where('user_id', $user->id)
            ->whereHas('vendor')
            ->first();
    }

    /** The store this user works in: their own store first, else a team one. */
    public function vendorFor(User $user): ?Vendor
    {
        return $this->ownedVendor($user) ?? $this->membership($user)?->vendor;
    }

    /**
     * Stores the user may publish a listing under, with the permissions they
     * hold in each.
     *
     * @return array<int, array<string, mixed>>
     */
    public function publishOptions(User $user): array
    {
        $options = [];
        $owned = $this->ownedVendor($user);

        if ($owned) {
            $options[] = [
                'vendor' => $owned,
                'is_owner' => true,
                'permissions' => array_keys(self::PERMISSIONS),
            ];
        }

        $membership = $this->membership($user);

        if ($membership && (! $owned || $membership->vendor_id !== $owned->id)) {
            $options[] = [
                'vendor' => $membership->vendor,
                'is_owner' => false,
                'permissions' => $membership->permissions ?? [],
            ];
        }

        return $options;
    }

    /** Permissions this user holds in a store (owners hold all of them). */
    public function permissionsFor(User $user, Vendor $vendor): array
    {
        if ((int) $vendor->user_id === (int) $user->id) {
            return array_keys(self::PERMISSIONS);
        }

        $member = VendorMember::query()
            ->where('vendor_id', $vendor->id)
            ->where('user_id', $user->id)
            ->first();

        return $member?->permissions ?? [];
    }

    public function can(User $user, Vendor $vendor, string $permission): bool
    {
        return in_array($permission, $this->permissionsFor($user, $vendor), true);
    }

    /** Members of a store plus the owner as the first row. */
    public function members(Vendor $vendor): array
    {
        $owner = $vendor->user;
        $rows = [];

        if ($owner) {
            $rows[] = [
                'user_id' => $owner->id,
                'name' => trim($owner->name.' '.($owner->surname ?? '')),
                'email' => $owner->email,
                'avatar' => $owner->avatar,
                'is_owner' => true,
                'permissions' => array_keys(self::PERMISSIONS),
            ];
        }

        foreach ($vendor->members()->with('user')->get() as $member) {
            if (! $member->user) {
                continue;
            }

            $rows[] = [
                'user_id' => $member->user->id,
                'name' => trim($member->user->name.' '.($member->user->surname ?? '')),
                'email' => $member->user->email,
                'avatar' => $member->user->avatar,
                'is_owner' => false,
                'permissions' => $member->permissions ?? [],
            ];
        }

        return $rows;
    }

    /** Active invite links, so the owner can re-share them. */
    public function invitations(Vendor $vendor): array
    {
        return $vendor->invitations()
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()
            ->get()
            ->map(fn (VendorInvitation $invitation) => [
                'token' => $invitation->token,
                'url' => url('/magaza/devet/'.$invitation->token),
                'permissions' => $invitation->permissions ?? [],
                'expires_at' => $invitation->expires_at?->toIso8601String(),
            ])
            ->all();
    }

    public function invite(Vendor $vendor, array $permissions, User $inviter): VendorInvitation
    {
        $permissions = $this->normalisePermissions($permissions);

        return $vendor->invitations()->create([
            'token' => Str::random(48),
            'permissions' => $permissions,
            'created_by' => $inviter->id,
            'expires_at' => now()->addDays(self::INVITE_DAYS),
        ]);
    }

    public function findByToken(string $token): ?VendorInvitation
    {
        $invitation = VendorInvitation::query()
            ->with('vendor')
            ->where('token', $token)
            ->first();

        return $invitation && $invitation->isUsable() ? $invitation : null;
    }

    public function accept(VendorInvitation $invitation, User $user): VendorMember
    {
        if ((int) $invitation->vendor->user_id === (int) $user->id) {
            throw ValidationException::withMessages([
                'token' => 'Bu mağazanın sahibisiniz.',
            ]);
        }

        $member = VendorMember::query()->updateOrCreate(
            ['vendor_id' => $invitation->vendor_id, 'user_id' => $user->id],
            ['permissions' => $invitation->permissions ?? [], 'invited_by' => $invitation->created_by]
        );

        $invitation->forceFill([
            'accepted_at' => now(),
            'accepted_by' => $user->id,
        ])->save();

        return $member;
    }

    public function updateMember(Vendor $vendor, int $userId, array $permissions): VendorMember
    {
        $member = VendorMember::query()
            ->where('vendor_id', $vendor->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $member->permissions = $this->normalisePermissions($permissions);
        $member->save();

        return $member;
    }

    public function removeMember(Vendor $vendor, int $userId): void
    {
        VendorMember::query()
            ->where('vendor_id', $vendor->id)
            ->where('user_id', $userId)
            ->delete();
    }

    /** @return array<int, string> */
    private function normalisePermissions(array $permissions): array
    {
        $known = array_keys(self::PERMISSIONS);
        $permissions = array_values(array_intersect($known, array_map('strval', $permissions)));

        if ($permissions === []) {
            throw ValidationException::withMessages([
                'permissions' => 'Ən azı bir icazə seçilməlidir.',
            ]);
        }

        return $permissions;
    }
}
