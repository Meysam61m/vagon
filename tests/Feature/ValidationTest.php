<?php

namespace Tests\Feature;

use App\Models\CargoRequest;
use App\Models\Owner;
use App\Models\Representative;
use App\Models\Tariff;
use App\Models\User;
use App\Models\Wagon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Owner $owner;
    private Representative $representative;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'مدیر',
            'email' => 'admin@test.local',
            'password' => 'secret-1234',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->owner = Owner::create(['name' => 'شرکت الف', 'is_active' => true]);
        $this->representative = Representative::create(['name' => 'نماینده', 'is_active' => true]);
        $this->representative->owners()->attach($this->owner);
    }

    public function test_invalid_shamsi_date_is_rejected_instead_of_silently_replaced(): void
    {
        $this->actingAs($this->admin)
            ->post(route('cargo.store'), [
                'owner_id' => $this->owner->id,
                'representative_id' => $this->representative->id,
                'request_date_shamsi' => '1404/13/40',
                'wagons' => [['number' => '123456', 'description' => 'گندم']],
            ])
            ->assertSessionHasErrors('request_date_shamsi');

        $this->assertSame(0, CargoRequest::count());
    }

    public function test_persian_digits_are_accepted_and_normalized(): void
    {
        $this->actingAs($this->admin)
            ->post(route('cargo.store'), [
                'owner_id' => $this->owner->id,
                'representative_id' => $this->representative->id,
                'request_date_shamsi' => '۱۴۰۴/۰۷/۲۵',
                'wagons' => [['number' => '123456', 'description' => 'گندم']],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $cargo = CargoRequest::firstOrFail();
        $this->assertSame('1404/07/25', $cargo->request_date_shamsi);
        $this->assertSame('2025-10-17', $cargo->request_date->toDateString());
    }

    public function test_reports_reject_invalid_date_filter(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.billings', ['from' => 'نامعتبر']))
            ->assertSessionHasErrors('from');
    }

    public function test_loading_weight_cannot_exceed_tariff_capacity(): void
    {
        $tariff = Tariff::create([
            'code' => 'T-1',
            'title' => 'جرثقیل ۶۰ تن',
            'unit' => 'واگن',
            'rate' => 1_000_000,
            'category' => 1,
            'capacity' => 60,
            'equipment_type' => 'crane',
            'is_active' => true,
        ]);

        $cargo = CargoRequest::create([
            'request_number' => 'CR-000000-0001',
            'owner_id' => $this->owner->id,
            'representative_id' => $this->representative->id,
            'request_date' => now()->toDateString(),
            'request_date_shamsi' => '1404/07/25',
            'status' => CargoRequest::STATUS_PENDING,
        ]);

        $wagon = Wagon::create([
            'cargo_request_id' => $cargo->id,
            'wagon_number' => '555555',
        ]);

        $this->actingAs($this->admin)
            ->post(route('cargo.load', $cargo), [
                'loading_date_shamsi' => '1404/07/25',
                'loading' => [
                    $wagon->id => ['tariff_id' => $tariff->id, 'weight' => 75],
                ],
            ])
            ->assertSessionHasErrors('loading.'.$wagon->id.'.weight');

        $this->assertNull($wagon->fresh()->loaded_weight);
    }

    public function test_cargo_description_is_saved_per_wagon_and_is_optional(): void
    {
        $this->actingAs($this->admin)
            ->post(route('cargo.store'), [
                'owner_id' => $this->owner->id,
                'representative_id' => $this->representative->id,
                'request_date_shamsi' => '1404/07/25',
                'wagons' => [
                    ['number' => '111111', 'description' => 'گندم'],
                    ['number' => '222222', 'description' => ''],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $cargo = CargoRequest::firstOrFail();

        $this->assertSame(
            'گندم',
            $cargo->wagons()->where('wagon_number', '111111')->value('cargo_description')
        );
        $this->assertNull(
            $cargo->wagons()->where('wagon_number', '222222')->value('cargo_description')
        );
    }

    public function test_cargo_description_can_be_set_later_in_loading_form(): void
    {
        $cargo = CargoRequest::create([
            'request_number' => 'CR-000000-0002',
            'owner_id' => $this->owner->id,
            'representative_id' => $this->representative->id,
            'request_date' => now()->toDateString(),
            'request_date_shamsi' => '1404/07/25',
            'status' => CargoRequest::STATUS_PENDING,
        ]);

        $wagon = Wagon::create([
            'cargo_request_id' => $cargo->id,
            'wagon_number' => '333333',
        ]);

        $this->actingAs($this->admin)
            ->post(route('cargo.load', $cargo), [
                'loading_date_shamsi' => '1404/07/25',
                'loading' => [
                    $wagon->id => ['description' => 'کاشی', 'weight' => ''],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('کاشی', $wagon->fresh()->cargo_description);
        $this->assertNull($wagon->fresh()->loaded_weight);
        $this->assertSame(CargoRequest::STATUS_PENDING, $cargo->fresh()->status);
    }

    public function test_duplicate_wagon_numbers_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('cargo.store'), [
                'owner_id' => $this->owner->id,
                'representative_id' => $this->representative->id,
                'request_date_shamsi' => '1404/07/25',
                'wagons' => [
                    ['number' => '123456', 'description' => 'گندم'],
                    ['number' => '123456', 'description' => 'سیمان'],
                ],
            ])
            ->assertSessionHasErrors();
    }

    public function test_representative_must_belong_to_selected_owner(): void
    {
        $otherRepresentative = Representative::create(['name' => 'دیگر', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->post(route('cargo.store'), [
                'owner_id' => $this->owner->id,
                'representative_id' => $otherRepresentative->id,
                'request_date_shamsi' => '1404/07/25',
                'wagons' => [['number' => '123456', 'description' => 'گندم']],
            ])
            ->assertSessionHasErrors('representative_id');

        $this->assertSame(0, CargoRequest::count());
    }
}
