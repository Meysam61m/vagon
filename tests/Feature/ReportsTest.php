<?php

namespace Tests\Feature;

use App\Models\Billing;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_report_totals_include_all_pages_and_exclude_cancelled(): void
    {
        $admin = User::create([
            'name' => 'مدیر',
            'email' => 'admin@test.local',
            'password' => 'secret-1234',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $owner = Owner::create(['name' => 'شرکت الف', 'is_active' => true]);

        // ۲۵ فاکتور (بیشتر از حجم یک صفحه) + یک فاکتور لغوشده
        for ($i = 1; $i <= 25; $i++) {
            Billing::create([
                'billing_number' => sprintf('INV-000000-%04d', $i),
                'owner_id' => $owner->id,
                'operator_id' => $admin->id,
                'owner_name' => $owner->name,
                'billing_date' => '2025-10-17',
                'billing_date_shamsi' => '1404/07/25',
                'base_amount' => 1_000_000,
                'tax_percentage' => 9,
                'tax_amount' => 90_000,
                'total_amount' => 1_090_000,
                'status' => Billing::STATUS_ISSUED,
            ]);
        }

        Billing::create([
            'billing_number' => 'INV-000000-9999',
            'owner_id' => $owner->id,
            'operator_id' => $admin->id,
            'owner_name' => $owner->name,
            'billing_date' => '2025-10-17',
            'billing_date_shamsi' => '1404/07/25',
            'base_amount' => 5_000_000,
            'tax_percentage' => 9,
            'tax_amount' => 450_000,
            'total_amount' => 5_450_000,
            'status' => Billing::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.billings', [
            'from' => '1404/07/01',
            'to' => '1404/07/30',
        ]));

        $response->assertOk();

        $totals = $response->viewData('totals');

        $this->assertSame(25_000_000, (int) $totals['base']);
        $this->assertSame(2_250_000, (int) $totals['tax']);
        $this->assertSame(27_250_000, (int) $totals['total']);
        $this->assertSame(25, (int) $totals['count']);
        $this->assertSame(1, (int) $totals['cancelled']);
    }

    public function test_financial_report_uses_shamsi_dates(): void
    {
        $admin = User::create([
            'name' => 'مدیر',
            'email' => 'admin@test.local',
            'password' => 'secret-1234',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $owner = Owner::create(['name' => 'شرکت الف', 'is_active' => true]);

        Billing::create([
            'billing_number' => 'INV-000000-0001',
            'owner_id' => $owner->id,
            'operator_id' => $admin->id,
            'owner_name' => $owner->name,
            'billing_date' => '2025-10-17',
            'billing_date_shamsi' => '1404/07/25',
            'base_amount' => 100_000,
            'tax_percentage' => 9,
            'tax_amount' => 9_000,
            'total_amount' => 109_000,
            'status' => Billing::STATUS_ISSUED,
        ]);

        $this->actingAs($admin)
            ->get(route('reports.financial', ['from' => '1404/07/01', 'to' => '1404/07/30']))
            ->assertOk()
            ->assertSee('1404/07/25');
    }
}
