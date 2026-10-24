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

    public function test_setad_report_calculates_booklet_vat_customer_payable_and_customer_tax_correctly(): void
    {
        $operator = User::create([
            'name' => 'اپراتور',
            'email' => 'operator@test.local',
            'password' => 'secret-1234',
            'role' => User::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $owner = Owner::create(['name' => 'شرکت فولاد', 'is_active' => true]);
        $rep = Representative::create(['name' => 'نماینده فولاد', 'is_active' => true]);
        $rep->owners()->attach($owner);

        $tariff = Tariff::create([
            'code' => 'T-SETAD-1',
            'title' => 'جرثقیل ۶۵ تنی',
            'unit' => 'واگن - ۶۵ تن',
            'rate' => 6_500_000,
            'category' => 1,
            'capacity' => 65,
            'equipment_type' => 'crane',
            'is_active' => true,
        ]);

        TaxSetting::create(['percentage' => 10, 'is_current' => true]);

        $cargo = CargoRequest::create([
            'request_number' => 'CR-000001-0001',
            'owner_id' => $owner->id,
            'representative_id' => $rep->id,
            'operator_id' => $operator->id,
            'request_date' => '2025-10-17',
            'request_date_shamsi' => '1404/07/25',
            'status' => CargoRequest::STATUS_LOADED,
        ]);

        Wagon::create([
            'cargo_request_id' => $cargo->id,
            'owner_id' => $owner->id,
            'wagon_number' => '778899',
            'cargo_description' => 'میلگرد',
            'line_number' => '3',
            'tariff_id' => $tariff->id,
            'loaded_weight' => 30,
            'loaded_on' => '2025-10-17',
            'is_settled' => false,
        ]);

        $response = $this->actingAs($operator)->get(route('reports.setad', [
            'from' => '1404/07/01',
            'to' => '1404/07/30',
            'scope' => 'all',
        ]));

        $response->assertOk();
        $totals = $response->viewData('totals');

        // کتابچه با مالیات ۱۰٪: 6,500,000 + 650,000 = 7,150,000
        $this->assertSame(7_150_000, (int) $totals['total_with_vat']);
        // پایه ۳۰ تن از ۶۵ تن: 3,000,000 + مالیات مشتری 300,000 = 3,300,000
        $this->assertSame(300_000, (int) $totals['customer_tax']);
        $this->assertSame(3_300_000, (int) $totals['customer_payable']);
    }

    public function test_pending_loading_report_includes_unbilled_wagons(): void
    {
        $operator = User::create([
            'name' => 'اپراتور',
            'email' => 'operator2@test.local',
            'password' => 'secret-1234',
            'role' => User::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $rep = Representative::create(['name' => 'نماینده الف', 'is_active' => true]);

        $cargo = CargoRequest::create([
            'request_number' => 'CR-000002-0001',
            'owner_id' => null,
            'representative_id' => $rep->id,
            'operator_id' => $operator->id,
            'request_date' => now()->toDateString(),
            'request_date_shamsi' => '1404/07/25',
            'status' => CargoRequest::STATUS_PENDING,
        ]);

        Wagon::create([
            'cargo_request_id' => $cargo->id,
            'wagon_number' => '554433',
            'cargo_description' => 'ورق فولادی',
            'line_number' => '2',
            'is_settled' => false,
        ]);

        $this->actingAs($operator)
            ->get(route('reports.pending-loading'))
            ->assertOk()
            ->assertSee('554433')
            ->assertSee('ورق فولادی');
    }
}
