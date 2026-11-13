<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Marketplace\Entities\Vendor;
use Modules\Marketplace\Services\ListingService;
use Modules\Marketplace\Services\VendorTeamService;
use Modules\User\Entities\User;
use Tests\TestCase;

class VendorTeamTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::query()->create([
            'name' => 'Test',
            'surname' => 'User',
            'email' => 'user'.random_int(1000, 999999).'@example.test',
            'phone' => '+9945'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => 'secret',
        ]);
    }

    private function store(User $owner): Vendor
    {
        return Vendor::query()->create([
            'user_id' => $owner->id,
            'name' => ['az' => 'Test Mağaza', 'en' => 'Test Store', 'ru' => 'Test', 'tr' => 'Test'],
            'slug' => 'test-magaza',
            'status' => Vendor::STATUS_ACTIVE,
        ]);
    }

    private function listingPayload(string $publishAs): array
    {
        return [
            'category_id' => null,
            'title' => 'Test elan',
            'price' => 10,
            'publish_as' => $publishAs,
        ];
    }

    public function test_owner_invites_members_with_chosen_permissions(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $vendor = $this->store($owner);
        $team = app(VendorTeamService::class);

        $invitation = $team->invite($vendor, ['listings', 'messages'], $owner);

        $this->assertNotNull($team->findByToken($invitation->token));

        $team->accept($invitation->refresh(), $member);

        $this->assertTrue($team->can($member, $vendor, 'listings'));
        $this->assertFalse($team->can($member, $vendor, 'orders'));
        $this->assertTrue($team->can($owner, $vendor, 'orders'));
        $this->assertSame($vendor->id, $team->vendorFor($member)?->id);
        $this->assertFalse($invitation->refresh()->isUsable());

        $team->updateMember($vendor, $member->id, ['orders']);
        $this->assertTrue($team->can($member, $vendor, 'orders'));
        $this->assertFalse($team->can($member, $vendor, 'listings'));

        $team->removeMember($vendor, $member->id);
        $this->assertNull($team->vendorFor($member));
    }

    public function test_owner_can_publish_personally_or_for_the_store(): void
    {
        $owner = $this->user();
        $vendor = $this->store($owner);
        $service = app(ListingService::class);

        $personal = $service->create($this->listingPayload('personal'), $owner, Request::create('/'));
        $this->assertNull($personal['listing']->vendor_id);
        $this->assertSame('user', $personal['listing']->seller_type);

        $store = $service->create($this->listingPayload('store'), $owner, Request::create('/'));
        $this->assertSame($vendor->id, $store['listing']->vendor_id);
        $this->assertSame('vendor', $store['listing']->seller_type);
    }

    public function test_member_without_listings_permission_cannot_publish_for_the_store(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $vendor = $this->store($owner);
        $team = app(VendorTeamService::class);

        $invitation = $team->invite($vendor, ['orders'], $owner);
        $team->accept($invitation->refresh(), $member);

        $this->expectException(ValidationException::class);

        app(ListingService::class)->create($this->listingPayload('store'), $member, Request::create('/'));
    }

    public function test_owner_creates_an_invitation_link_from_the_team_page(): void
    {
        $owner = $this->user();
        $this->store($owner);

        $this->actingAs($owner)
            ->post('/magaza/komanda/devet', ['permissions' => ['listings', 'stats']])
            ->assertRedirect(route('storefront.store.team'))
            ->assertSessionHas('invite');

        $this->assertDatabaseCount('vendor_invitations', 1);

        $this->actingAs($owner)
            ->get('/magaza/komanda')
            ->assertOk()
            ->assertSee('Dəvət linki yarat')
            ->assertSee('Elanları idarə et');
    }

    public function test_invited_user_joins_the_store_through_the_link(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $vendor = $this->store($owner);
        $team = app(VendorTeamService::class);

        $invitation = $team->invite($vendor, ['stats'], $owner);

        $this->actingAs($member)
            ->get('/magaza/devet/'.$invitation->token)
            ->assertOk()
            ->assertSee('Dəvəti qəbul et');

        $this->actingAs($member)
            ->post('/magaza/devet/'.$invitation->token)
            ->assertRedirect(route('storefront.store.panel'));

        $this->assertTrue($team->can($member, $vendor, 'stats'));

        $this->actingAs($member)
            ->get('/magaza/komanda')
            ->assertOk()
            ->assertSee('İcazələriniz');
    }
}
