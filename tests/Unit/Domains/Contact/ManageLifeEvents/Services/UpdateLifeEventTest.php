<?php

namespace Tests\Unit\Domains\Contact\ManageLifeEvents\Services;

use App\Domains\Contact\ManageLifeEvents\Services\UpdateLifeEvent;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateLifeEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_life_event(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $lifeEventCategory = LifeEventCategory::factory()->create(['vault_id' => $vault->id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $lifeEvent = LifeEvent::factory()->create([
            'vault_id' => $vault->id,
            'life_event_type_id' => $lifeEventType->id,
        ]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, $lifeEvent, 'test');
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'summary' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateLifeEvent())->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $lifeEventCategory = LifeEventCategory::factory()->create(['vault_id' => $vault->id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $lifeEvent = LifeEvent::factory()->create([
            'vault_id' => $vault->id,
            'life_event_type_id' => $lifeEventType->id,
        ]);

        $this->executeService($user, $account, $vault, $contact, $lifeEventType, $lifeEvent, 'test');
    }

    /** @test */
    public function it_fails_if_vault_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $lifeEventCategory = LifeEventCategory::factory()->create(['vault_id' => $vault->id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $lifeEvent = LifeEvent::factory()->create([
            'vault_id' => $vault->id,
            'life_event_type_id' => $lifeEventType->id,
        ]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, $lifeEvent, 'test');
    }

    /** @test */
    public function it_fails_if_user_isnt_vault_editor(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $lifeEventCategory = LifeEventCategory::factory()->create(['vault_id' => $vault->id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $lifeEvent = LifeEvent::factory()->create([
            'vault_id' => $vault->id,
            'life_event_type_id' => $lifeEventType->id,
        ]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, $lifeEvent, 'test');
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $lifeEventCategory = LifeEventCategory::factory()->create(['vault_id' => $vault->id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $lifeEvent = LifeEvent::factory()->create([
            'vault_id' => $vault->id,
            'life_event_type_id' => $lifeEventType->id,
        ]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, $lifeEvent, 'test');
    }

    /** @test */
    public function it_fails_if_life_event_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $lifeEventCategory = LifeEventCategory::factory()->create();
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $lifeEvent = LifeEvent::factory()->create([
            'vault_id' => $vault->id,
            'life_event_type_id' => $lifeEventType->id,
        ]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, $lifeEvent, 'test');
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, LifeEventType $lifeEventType, LifeEvent $lifeEvent, string $summary): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'life_event_type_id' => $lifeEventType->id,
            'life_event_id' => $lifeEvent->id,
            'summary' => null,
            'description' => null,
            'happened_at' => '1982-02-04',
            'costs' => null,
            'currency_id' => null,
            'paid_by_contact_id' => null,
            'duration_in_minutes' => null,
            'distance_in_km' => null,
            'from_place' => null,
            'to_place' => null,
            'place' => null,
            'participant_ids' => [$contact->id],
        ];

        $lifeEvent = (new UpdateLifeEvent())->execute($request);

        $this->assertDatabaseHas('life_events', [
            'id' => $lifeEvent->id,
            'vault_id' => $vault->id,
            'life_event_type_id' => $lifeEventType->id,
            'summary' => null,
            'happened_at' => '1982-02-04 00:00:00',
        ]);

        $this->assertDatabaseHas('life_event_participants', [
            'life_event_id' => $lifeEvent->id,
            'contact_id' => $contact->id,
        ]);
    }
}
