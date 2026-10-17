<?php

namespace Tests\Feature;

use App\Models\Billing;
use App\Models\CargoRequest;
use App\Models\Owner;
use App\Models\Representative;
use App\Models\Tariff;
use App\Models\TaxSetting;
use App\Models\User;
use App\Models\Wagon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Owner $owner;
    private Representative $representative;
    private Tariff $tariff;

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
        $this->representative = Representative::create(['name' => 'نماینده یک', 'is_active' => true]);
        $this->representative->owners()->attach($this->owner);

        $this->tariff = Tariff::create([
            'code' => 'T-1',
            'title' => 'جرثقیل ۶۰ تن',
            'unit' => 'واگن - ۶۰ تن',
            'rate' => 6_000_000,
            'category' => 1,
            'capacity' => 60,
            'equipment_type' => 'crane',
            'is_active' => true,
        ]);

        TaxSetting::create(['percentage' => 9, 'is_current' => true]);
    }

    public function test_full_cargo_loading_and_billing_flow(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('cargo.store'), [
            'owner_id' => $this->owner->id,
            'representative_id' => $this->representative->id,
            'request_date_shamsi' => '1404/07/25',
            'wagons' => [
                ['number' => '123456', 'description' => 'گندم'],
                ['number' => '123457', 'description' => 'سیمان فله'],
            ],
        ])->assertRedirect();

        $cargo = CargoRequest::firstOrFail();
        $this->assertSame('1404/07/25', $cargo->request_date_shamsi);
        $this->assertSame(CargoRequest::STATUS_PENDING, $cargo->status);
        $this->assertMatchesRegularExpression('/^CR-\d{6}-\d{4}$/', $cargo->request_number);
        $this->assertCount(2, $cargo->wagons);

        /** @var Wagon $firstWagon */
        $firstWagon = $cargo->wagons()->orderBy('id')->first();

        // شرح کالا برای هر واگن جداگانه ذخیره می‌شود
        $this->assertSame('گندم', $firstWagon->cargo_description);
        $this->assertSame('سیمان فله', $cargo->wagons()->orderByDesc('id')->first()->cargo_description);

        // ثبت بارگیری فقط برای واگن اول
        $this->post(route('cargo.load', $cargo), [
            'loading_date_shamsi' => '1404/07/25',
            'loading' => [
                $firstWagon->id => ['tariff_id' => $this->tariff->id, 'weight' => 30],
            ],
        ])->assertRedirect();

        $this->assertSame(30.0, (float) $firstWagon->fresh()->loaded_weight);
        $this->assertSame(CargoRequest::STATUS_PENDING, $cargo->fresh()->status);

        // صفحه صدور فاکتور فقط واگن بارگیری‌شده را پیشنهاد می‌دهد
        $this->get(route('billing.create', ['owner_id' => $this->owner->id]))
            ->assertOk()
            ->assertSee($firstWagon->wagon_number);

        $this->post(route('billing.store'), [
            'owner_id' => $this->owner->id,
            'billing_date_shamsi' => '1404/07/26',
            'wagons' => [
                ['id' => $firstWagon->id, 'tariff_id' => $this->tariff->id, 'weight' => 30],
            ],
        ])->assertRedirect();

        $billing = Billing::with('items')->firstOrFail();

        // 30/60 × 6,000,000 = 3,000,000 و مالیات ۹٪ = 270,000
        $this->assertSame(3_000_000, $billing->base_amount);
        $this->assertSame(270_000, $billing->tax_amount);
        $this->assertSame(3_270_000, $billing->total_amount);
        $this->assertSame('شرکت الف', $billing->owner_name);
        $this->assertTrue($firstWagon->fresh()->is_settled);
        $this->assertCount(1, $billing->items);

        // صدور مجدد برای همان واگن باید رد شود
        $this->post(route('billing.store'), [
            'owner_id' => $this->owner->id,
            'billing_date_shamsi' => '1404/07/26',
            'wagons' => [
                ['id' => $firstWagon->id, 'tariff_id' => $this->tariff->id, 'weight' => 30],
            ],
        ])->assertSessionHasErrors('wagons');

        $this->assertSame(1, Billing::count());

        // لغو فاکتور، واگن را آزاد می‌کند
        $this->post(route('billing.cancel', $billing))->assertRedirect();

        $billing->refresh();
        $this->assertSame(Billing::STATUS_CANCELLED, $billing->status);
        $this->assertFalse($firstWagon->fresh()->is_settled);
        $this->assertSame(0, $billing->items()->count());
    }

    public function test_billing_rejects_wagon_of_another_owner(): void
    {
        $otherOwner = Owner::create(['name' => 'شرکت ب', 'is_active' => true]);
        $otherRepresentative = Representative::create(['name' => 'نماینده دو', 'is_active' => true]);
        $otherRepresentative->owners()->attach($otherOwner);

        $cargo = CargoRequest::create([
            'request_number' => 'CR-000000-0001',
            'owner_id' => $otherOwner->id,
            'representative_id' => $otherRepresentative->id,
            'request_date' => now()->toDateString(),
            'request_date_shamsi' => '1404/07/25',
            'status' => CargoRequest::STATUS_LOADED,
        ]);

        $wagon = Wagon::create([
            'cargo_request_id' => $cargo->id,
            'wagon_number' => '999999',
            'tariff_id' => $this->tariff->id,
            'loaded_weight' => 30,
        ]);

        $this->actingAs($this->admin)
            ->post(route('billing.store'), [
                'owner_id' => $this->owner->id,
                'billing_date_shamsi' => '1404/07/26',
                'wagons' => [
                    ['id' => $wagon->id, 'tariff_id' => $this->tariff->id, 'weight' => 30],
                ],
            ])
            ->assertSessionHasErrors('wagons');

        $this->assertSame(0, Billing::count());
        $this->assertFalse($wagon->fresh()->is_settled);
    }
}
