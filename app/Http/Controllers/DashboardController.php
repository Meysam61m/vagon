<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\CargoRequest;
use App\Models\Owner;
use App\Models\Representative;
use App\Models\Wagon;
use App\Support\JalaliDate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /** حداکثر تعداد ستون نمودار برای بازه‌های طولانی */
    private const MAX_CHART_DAYS = 31;

    public function index(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);

        $kpis = [
            'total_requests' => CargoRequest::whereBetween('request_date', [$from, $to])->count(),
            'total_billings' => Billing::whereBetween('billing_date', [$from, $to])
                ->where('status', Billing::STATUS_ISSUED)->count(),
            'total_amount' => Billing::whereBetween('billing_date', [$from, $to])
                ->where('status', Billing::STATUS_ISSUED)->sum('total_amount'),
            'pending_wagons' => Wagon::where('is_settled', false)->count(),
            'loaded_wagons' => Wagon::whereNotNull('loaded_weight')->where('is_settled', false)->count(),
            'active_owners' => Owner::active()->count(),
            'active_representatives' => Representative::active()->count(),
        ];

        $loadingChart = $this->dailyLoading($from, $to);
        $revenueChart = $this->dailyRevenue($from, $to);

        $ownerShareChart = Billing::whereBetween('billing_date', [$from, $to])
            ->where('status', Billing::STATUS_ISSUED)
            ->join('owners', 'owners.id', '=', 'billings.owner_id')
            ->select('owners.name', DB::raw('SUM(billings.total_amount) as total'))
            ->groupBy('owners.id', 'owners.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $recentBillings = Billing::with('owner')->latest('billing_date')->latest('id')->limit(10)->get();

        $wagonStats = [
            'loaded' => Wagon::whereNotNull('loaded_weight')->count(),
            'settled' => Wagon::where('is_settled', true)->count(),
            'pending' => Wagon::where('is_settled', false)->count(),
        ];

        return view('dashboard.index', [
            'kpis' => $kpis,
            'loadingChart' => $loadingChart,
            'revenueChart' => $revenueChart,
            'ownerShareChart' => $ownerShareChart,
            'recentBillings' => $recentBillings,
            'wagonStats' => $wagonStats,
            'fromShamsi' => JalaliDate::fromCarbon($from),
            'toShamsi' => JalaliDate::fromCarbon($to),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(Request $request): array
    {
        $from = $request->filled('from') ? JalaliDate::parse($request->string('from')->toString(), 'from') : null;
        $to = $request->filled('to') ? JalaliDate::parse($request->string('to')->toString(), 'to') : null;

        $from ??= now()->subDays(6)->startOfDay();
        $to ??= now()->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function dailyLoading(Carbon $from, Carbon $to): array
    {
        return $this->dailySeries(
            $from,
            $to,
            fn (Carbon $start, Carbon $end) => DB::table('wagons')
                ->whereBetween('loaded_on', [$start->toDateString(), $end->toDateString()])
                ->sum('loaded_weight'),
            fn (float $value) => round($value, 1)
        );
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function dailyRevenue(Carbon $from, Carbon $to): array
    {
        return $this->dailySeries(
            $from,
            $to,
            fn (Carbon $start, Carbon $end) => Billing::whereBetween('billing_date', [$start, $end])
                ->where('status', Billing::STATUS_ISSUED)
                ->sum('total_amount'),
            fn (float $value) => $value
        );
    }

    /**
     * ساخت سری روزانه با یک کوئری تجمیعی برای هر بازه (نه یک کوئری برای هر روز).
     *
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function dailySeries(Carbon $from, Carbon $to, callable $sum, callable $cast): array
    {
        $end = $to->copy()->endOfDay();

        if ($from->diffInDays($end) > self::MAX_CHART_DAYS) {
            $from = $end->copy()->subDays(self::MAX_CHART_DAYS)->startOfDay();
        }

        $labels = [];
        $values = [];

        for ($day = $from->copy(); $day->lessThanOrEqualTo($end); $day->addDay()) {
            $labels[] = JalaliDate::fromCarbon($day);
            $values[] = $cast((float) $sum($day->copy()->startOfDay(), $day->copy()->endOfDay()));
        }

        return compact('labels', 'values');
    }
}
