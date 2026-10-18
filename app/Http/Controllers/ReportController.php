<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\BillingItem;
use App\Models\CargoRequest;
use App\Models\Representative;
use App\Support\JalaliDate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * بازه شمسی درخواستی را به بازه میلادی تبدیل می‌کند.
     * ورودی نامعتبر → خطای اعتبارسنجی فارسی (نه تاریخ جایگزین خاموش).
     *
     * @return array{0: Carbon, 1: Carbon, 2: string, 3: string}
     */
    private function dateRange(Request $request): array
    {
        $fromShamsi = $request->filled('from') ? JalaliDate::normalize($request->string('from')->toString()) : null;
        $toShamsi = $request->filled('to') ? JalaliDate::normalize($request->string('to')->toString()) : null;

        $from = $fromShamsi
            ? JalaliDate::parse($fromShamsi, 'from')
            : Carbon::instance(\Morilog\Jalali\Jalalian::fromCarbon(now())->getFirstDayOfMonth()->toCarbon())->startOfDay();
        $to = $toShamsi ? JalaliDate::parse($toShamsi, 'to') : now()->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to, JalaliDate::fromCarbon($from), JalaliDate::fromCarbon($to)];
    }

    /** گزارش درخواست‌ها و بارگیری‌های روزانه */
    public function dailyLoading(Request $request)
    {
        [$from, $to, $fromShamsi, $toShamsi] = $this->dateRange($request);

        $requests = CargoRequest::with(['owner', 'representative', 'wagons'])
            ->whereBetween('request_date', [$from, $to])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->orderBy('request_date')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $summary = DB::table('wagons')
            ->join('cargo_requests', 'cargo_requests.id', '=', 'wagons.cargo_request_id')
            ->whereBetween('cargo_requests.request_date', [$from, $to])
            ->selectRaw('COUNT(wagons.id) as wagon_count')
            ->selectRaw('COUNT(wagons.loaded_weight) as loaded_count')
            ->selectRaw('COALESCE(SUM(wagons.loaded_weight), 0) as total_weight')
            ->first();

        return view('reports.daily-loading', [
            'requests' => $requests,
            'summary' => $summary,
            'fromShamsi' => $fromShamsi,
            'toShamsi' => $toShamsi,
            'statusLabels' => CargoRequest::STATUS_LABELS,
        ]);
    }

    /** گزارش صورتحساب‌های صادرشده با جمع کل صحیح (مستقل از صفحه‌بندی) */
    public function billings(Request $request)
    {
        [$from, $to, $fromShamsi, $toShamsi] = $this->dateRange($request);

        $baseQuery = Billing::query()->whereBetween('billing_date', [$from, $to]);

        if ($request->filled('status')) {
            $baseQuery->where('status', $request->string('status')->toString());
        }

        $billings = (clone $baseQuery)
            ->with(['owner', 'representative', 'operator', 'items.wagon'])
            ->orderBy('billing_date')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $issuedQuery = (clone $baseQuery)->where('status', Billing::STATUS_ISSUED);

        $totals = [
            'base' => (clone $issuedQuery)->sum('base_amount'),
            'tax' => (clone $issuedQuery)->sum('tax_amount'),
            'total' => (clone $issuedQuery)->sum('total_amount'),
            'count' => (clone $issuedQuery)->count(),
            'cancelled' => (clone $baseQuery)->where('status', Billing::STATUS_CANCELLED)->count(),
        ];

        return view('reports.billings', [
            'billings' => $billings,
            'totals' => $totals,
            'fromShamsi' => $fromShamsi,
            'toShamsi' => $toShamsi,
            'statusLabels' => Billing::STATUS_LABELS,
        ]);
    }

    /** گزارش عملکرد اپراتورها */
    public function operators(Request $request)
    {
        [$from, $to, $fromShamsi, $toShamsi] = $this->dateRange($request);

        $stats = DB::table('billings')
            ->join('users', 'users.id', '=', 'billings.operator_id')
            ->whereBetween('billings.billing_date', [$from, $to])
            ->where('billings.status', Billing::STATUS_ISSUED)
            ->whereNull('billings.deleted_at')
            ->select('users.id', 'users.name')
            ->selectRaw('COUNT(*) as billing_count')
            ->selectRaw('COALESCE(SUM(billings.total_amount), 0) as total_amount')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_amount')
            ->get();

        return view('reports.operators', compact('stats', 'fromShamsi', 'toShamsi'));
    }

    /** گزارش مالی روزانه */
    public function financial(Request $request)
    {
        [$from, $to, $fromShamsi, $toShamsi] = $this->dateRange($request);

        $daily = DB::table('billings')
            ->whereBetween('billing_date', [$from, $to])
            ->where('status', Billing::STATUS_ISSUED)
            ->whereNull('deleted_at')
            ->selectRaw('billing_date')
            ->selectRaw('COUNT(*) as billing_count')
            ->selectRaw('COALESCE(SUM(base_amount), 0) as base_amount')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as tax_amount')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->groupBy('billing_date')
            ->orderBy('billing_date')
            ->get()
            ->map(function ($row) {
                $row->billing_date_shamsi = JalaliDate::display($row->billing_date);

                return $row;
            });

        $summary = [
            'base' => DB::table('billings')->whereBetween('billing_date', [$from, $to])
                ->where('status', Billing::STATUS_ISSUED)->whereNull('deleted_at')->sum('base_amount'),
            'tax' => DB::table('billings')->whereBetween('billing_date', [$from, $to])
                ->where('status', Billing::STATUS_ISSUED)->whereNull('deleted_at')->sum('tax_amount'),
            'total' => DB::table('billings')->whereBetween('billing_date', [$from, $to])
                ->where('status', Billing::STATUS_ISSUED)->whereNull('deleted_at')->sum('total_amount'),
        ];

        return view('reports.financial', compact('daily', 'summary', 'fromShamsi', 'toShamsi'));
    }

    /** گزارش تفصیلی هر واگن (با مالیات و مبلغ نهایی همان ردیف) */
    public function wagonDetail(Request $request)
    {
        [$from, $to, $fromShamsi, $toShamsi] = $this->dateRange($request);

        $items = BillingItem::with([
            'wagon.owner',
            'wagon.previousOwner',
            'wagon.ownerChangedBy',
            'wagon.cargoRequest.owner',
            'billing.owner',
            'tariff',
        ])
            ->whereHas('billing', fn ($q) => $q
                ->whereBetween('billing_date', [$from, $to])
                ->where('status', Billing::STATUS_ISSUED))
            ->orderByDesc('billing_id')
            ->paginate(25)
            ->withQueryString();

        $totals = BillingItem::query()
            ->whereHas('billing', fn ($q) => $q
                ->whereBetween('billing_date', [$from, $to])
                ->where('status', Billing::STATUS_ISSUED))
            ->selectRaw('COALESCE(SUM(loaded_weight), 0) as weight')
            ->selectRaw('COALESCE(SUM(base_cost), 0) as base')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as tax')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total')
            ->first();

        return view('reports.wagon-detail', compact('items', 'totals', 'fromShamsi', 'toShamsi'));
    }

    /** گزارش عملکرد صاحبان کالا */
    public function owners(Request $request)
    {
        [$from, $to, $fromShamsi, $toShamsi] = $this->dateRange($request);

        $stats = DB::table('billing_items')
            ->join('billings', 'billings.id', '=', 'billing_items.billing_id')
            ->leftJoin('wagons', 'wagons.id', '=', 'billing_items.wagon_id')
            ->join('owners', 'owners.id', '=', DB::raw('COALESCE(wagons.owner_id, billings.owner_id)'))
            ->whereBetween('billings.billing_date', [$from, $to])
            ->where('billings.status', Billing::STATUS_ISSUED)
            ->whereNull('billings.deleted_at')
            ->select('owners.id', 'owners.name')
            ->selectRaw('COUNT(DISTINCT billings.id) as billing_count')
            ->selectRaw('COALESCE(SUM(billing_items.base_cost), 0) as base_amount')
            ->selectRaw('COALESCE(SUM(billing_items.tax_amount), 0) as tax_amount')
            ->selectRaw('COALESCE(SUM(billing_items.total_amount), 0) as total_amount')
            ->groupBy('owners.id', 'owners.name')
            ->orderByDesc('total_amount')
            ->get();

        return view('reports.owners', compact('stats', 'fromShamsi', 'toShamsi'));
    }

    /** گزارش عملکرد نمایندگان: تعداد درخواست و مبلغ صورتحساب */
    public function representatives(Request $request)
    {
        [$from, $to, $fromShamsi, $toShamsi] = $this->dateRange($request);

        $requestCount = CargoRequest::query()
            ->selectRaw('COUNT(*)')
            ->whereColumn('representative_id', 'representatives.id')
            ->whereBetween('request_date', [$from, $to]);

        $billedTotal = Billing::query()
            ->selectRaw('COALESCE(SUM(total_amount), 0)')
            ->whereColumn('representative_id', 'representatives.id')
            ->where('status', Billing::STATUS_ISSUED)
            ->whereBetween('billing_date', [$from, $to]);

        $stats = Representative::query()
            ->select('representatives.id', 'representatives.name')
            ->selectSub($requestCount, 'request_count')
            ->selectSub($billedTotal, 'total_amount')
            ->orderByDesc('request_count')
            ->get();

        return view('reports.representatives', compact('stats', 'fromShamsi', 'toShamsi'));
    }
}
