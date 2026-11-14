<?php

namespace Modules\Marketplace\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Marketplace\Entities\Vendor;
use Modules\Marketplace\Services\VendorTeamService;

/**
 * Store team management. Reading and changing the team is reserved for the
 * store owner; accepting an invitation is open to any signed-in user.
 */
class VendorTeamController extends Controller
{
    public function __construct(private readonly VendorTeamService $service) {}

    public function index(Request $request)
    {
        $vendor = $this->ownerVendor($request);

        return responseHelper('OK', 200, [
            'members' => $this->service->members($vendor),
            'invitations' => $this->service->invitations($vendor),
            'permissions' => $this->service->permissions(),
            'presets' => $this->service->presets(),
        ]);
    }

    /** The permission catalogue, readable by every team member. */
    public function definitions()
    {
        return responseHelper('OK', 200, [
            'permissions' => $this->service->permissions(),
            'presets' => $this->service->presets(),
        ]);
    }

    public function invite(Request $request)
    {
        $vendor = $this->ownerVendor($request);

        $data = $request->validate([
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
        ]);

        $invitation = $this->service->invite(
            $vendor,
            $data['permissions'],
            $request->user('sanctum')
        );

        return responseHelper('Dəvət linki yaradıldı.', 201, [
            'token' => $invitation->token,
            'url' => url('/magaza/devet/'.$invitation->token),
            'permissions' => $invitation->permissions,
            'expires_at' => $invitation->expires_at?->toIso8601String(),
        ]);
    }

    public function updateMember(Request $request, int $userId)
    {
        $vendor = $this->ownerVendor($request);

        $data = $request->validate([
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
        ]);

        $member = $this->service->updateMember($vendor, $userId, $data['permissions']);

        return responseHelper('İcazələr yeniləndi.', 200, [
            'user_id' => $member->user_id,
            'permissions' => $member->permissions,
        ]);
    }

    public function destroyMember(Request $request, int $userId)
    {
        $vendor = $this->ownerVendor($request);

        $this->service->removeMember($vendor, $userId);

        return responseHelper('Üzv mağazadan çıxarıldı.', 200);
    }

    /** Disable a pending invitation link. */
    public function destroyInvitation(Request $request, string $token)
    {
        $vendor = $this->ownerVendor($request);

        $this->service->revokeInvitation($vendor, $token);

        return responseHelper('Dəvət linki ləğv edildi.', 200);
    }

    /** Details of a pending invitation, for the accept page. */
    public function invitation(string $token)
    {
        $invitation = $this->service->findByToken($token);
        abort_unless($invitation, 404);

        return responseHelper('OK', 200, [
            'token' => $invitation->token,
            'permissions' => $invitation->permissions ?? [],
            'expires_at' => $invitation->expires_at?->toIso8601String(),
            'vendor' => [
                'name' => $invitation->vendor?->name,
                'slug' => $invitation->vendor?->slug,
                'logo_url' => $invitation->vendor?->logo
                    ? Storage::disk('public')->url($invitation->vendor->logo)
                    : null,
            ],
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $invitation = $this->service->findByToken($token);
        abort_unless($invitation, 404, 'Dəvət linki etibarsızdır.');

        $member = $this->service->accept($invitation, $request->user('sanctum'));

        return responseHelper('Mağazaya qoşuldunuz.', 200, [
            'vendor_id' => $member->vendor_id,
            'permissions' => $member->permissions,
        ]);
    }

    private function ownerVendor(Request $request): Vendor
    {
        $vendor = $this->service->ownedVendor($request->user('sanctum'));
        abort_unless($vendor, 403, 'Mağazanız yoxdur.');

        return $vendor;
    }
}
