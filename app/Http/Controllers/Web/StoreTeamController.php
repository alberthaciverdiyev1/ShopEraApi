<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Http\Controllers\VendorController;
use Modules\Marketplace\Http\Controllers\VendorTeamController;

/**
 * Store teams: the owner invites people by link and chooses which parts of
 * the store each member may manage.
 */
class StoreTeamController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(): View|RedirectResponse
    {
        $vendor = $this->payload(
            app(VendorController::class)->mine($this->moduleHttpRequest())
        );

        if (! is_array($vendor) || $vendor === []) {
            return redirect()->route('storefront.store.open');
        }

        // Only the owner manages the team; members see their own workspace.
        if (empty($vendor['is_owner'])) {
            $definitions = (array) $this->payload(
                app(VendorTeamController::class)->definitions($this->moduleHttpRequest())
            );

            return view('storefront.store-panel.team-member', [
                'vendor' => $vendor,
                'permissions' => $definitions['permissions'] ?? [],
            ]);
        }

        $team = (array) $this->payload(
            app(VendorTeamController::class)->index($this->moduleHttpRequest())
        );

        return view('storefront.store-panel.team', [
            'vendor' => $vendor,
            'members' => $team['members'] ?? [],
            'invitations' => $team['invitations'] ?? [],
            'permissions' => $team['permissions'] ?? [],
            'presets' => $team['presets'] ?? [],
            'invite' => session('invite'),
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
        ]);

        $invite = (array) $this->payload(
            app(VendorTeamController::class)->invite($this->moduleHttpRequest($data, 'POST'))
        );

        return redirect()
            ->route('storefront.store.team')
            ->with('invite', $invite)
            ->with('status', 'Dəvət linki yaradıldı.');
    }

    public function updateMember(Request $request, int $user): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
        ]);

        app(VendorTeamController::class)->updateMember($this->moduleHttpRequest($data, 'PUT'), $user);

        return back()->with('status', 'İcazələr yeniləndi.');
    }

    public function destroyMember(int $user): RedirectResponse
    {
        app(VendorTeamController::class)->destroyMember($this->moduleHttpRequest([], 'DELETE'), $user);

        return back()->with('status', 'Üzv mağazadan çıxarıldı.');
    }

    public function destroyInvitation(string $token): RedirectResponse
    {
        app(VendorTeamController::class)->destroyInvitation($this->moduleHttpRequest([], 'DELETE'), $token);

        return back()->with('status', 'Dəvət linki ləğv edildi.');
    }

    /** Invitation link landing page. */
    public function invitation(Request $request, string $token): View|RedirectResponse
    {
        $invite = $this->payload(
            app(VendorTeamController::class)->invitation($token)
        );

        if (! is_array($invite) || $invite === []) {
            abort(404, 'Dəvət linki etibarsızdır.');
        }

        return view('storefront.store-panel.invitation', [
            'invite' => $invite,
            'token' => $token,
        ]);
    }

    public function accept(string $token): RedirectResponse
    {
        app(VendorTeamController::class)->accept($this->moduleHttpRequest([], 'POST'), $token);

        return redirect()
            ->route('storefront.store.panel')
            ->with('status', 'Mağazaya qoşuldunuz.');
    }
}
