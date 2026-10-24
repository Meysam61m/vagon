<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\BillingItem;
use App\Models\CargoRequest;
use App\Models\Owner;
use App\Models\Representative;
use App\Models\TaxSetting;
use App\Models\Wagon;
use App\Support\ExcelExport;
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

    private function wantsExcel(Request $request): bool
    {
        return in_array(strtolower((string) $request->query('export', '')), ['excel', 'xlsx', '1', 'true'], true);
    }

    private function fileDateRange(string $fromShamsi, string $toShamsi): string
    {
        return str_replace('/', '-', $fromShamsi).'-to-'.str_replace('/', '-', $toShamsi);
    }

    /**
     * گزارش واگن‌های در انتظار بارگیری و صورتحساب‌نشده (تمام واگن‌های باز که هنوز صورتحساب برای آن‌ها صادر نشده است)
     */
    public function pendingLoading(Request $request)
    {
        $fromShamsi = $request->filled('from') ? JalaliDate::normalize($request->string('from')->toString()) : '';
        $toShamsi = $request->filled('to') ? JalaliDate::normalize($request->string('to')->toString()) : '';

        $from = $fromShamsi !== '' ? JalaliDate::parse($fromShamsi, 'from')->startOfDay() : null;
        $to = $toShamsi !== '' ? JalaliDate::parse($toShamsi, 'to')->endOfDay() : null;

        if ($from && $to && $from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            [$fromShamsi, $toShamsi] = [JalaliDate::fromCarbon($from), JalaliDate::fromCarbon($to)];
        }

        $stage = $request->string('stage')->toString();

        $filterQuery = Wagon::query()
            ->join('cargo_requests', 'cargo_requests.id', '=', 'wagons.cargo_request_id')
            ->where('wagons.is_settled', false)
            ->where('cargo_requests.status', '!=', 'cancelled')
            ->when($stage === 'pending_loading', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('wagons.loaded_weight')
                        ->orWhereNull('wagons.tariff_id');
                });
            })
            ->when($stage === 'unbilled', function ($q) {
                $q->whereNotNull('wagons.loaded_weight')
                    ->whereNotNull('wagons.tariff_id');
            })
            ->when($from !== null, fn ($q) => $q->whereDate('cargo_requests.request_date', '>=', $from->toDateString()))
            ->when($to !== null, fn ($q) => $q->whereDate('cargo_requests.request_date', '<=', $to->toDateString()))
            ->when($request->filled('owner_id'), function ($q) use ($request) {
                if ($request->string('owner_id')->toString() === 'unknown') {
                    $q->whereNull('wagons.owner_id')->whereNull('cargo_requests.owner_id');

                    return;
                }
                $ownerId = $request->integer('owner_id');
                $q->where(function ($sub) use ($ownerId) {
                    $sub->where('wagons.owner_id', $ownerId)
                        ->orWhere(function ($s2) use ($ownerId) {
                            $s2->whereNull('wagons.owner_id')->where('cargo_requests.owner_id', $ownerId);
                        });
                });
            })
            ->when($request->filled('representative_id'), fn ($q) => $q->where('cargo_requests.representative_id', $request->integer('representative_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.trim($request->string('search')->toString()).'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('wagons.wagon_number', 'like', $term)
                        ->orWhere('wagons.previous_wagon_number', 'like', $term)
                        ->orWhere('wagons.cargo_description', 'like', $term)
                        ->orWhere('wagons.line_number', 'like', $term)
                        ->orWhere('cargo_requests.request_number', 'like', $term)
                        ->orWhereHas('owner', fn ($ow) => $ow->where('name', 'like', $term))
                        ->orWhereHas('cargoRequest.owner', fn ($ow) => $ow->where('name', 'like', $term))
                        ->orWhereHas('cargoRequest.representative', fn ($rep) => $rep->where('name', 'like', $term));
                });
            });

        $summary = [
            'total_wagons' => (clone $filterQuery)->count('wagons.id'),
            'pending_loading_wagons' => (clone $filterQuery)
                ->where(function ($q) {
                    $q->whereNull('wagons.loaded_weight')
                        ->orWhereNull('wagons.tariff_id');
                })
                ->count('wagons.id'),
            'unbilled_wagons' => (clone $filterQuery)
                ->whereNotNull('wagons.loaded_weight')
                ->whereNotNull('wagons.tariff_id')
                ->count('wagons.id'),
            'total_requests' => (clone $filterQuery)->distinct()->count('wagons.cargo_request_id'),
            'unknown_owner_wagons' => (clone $filterQuery)
                ->whereNull('wagons.owner_id')
                ->whereNull('cargo_requests.owner_id')
                ->count('wagons.id'),
        ];

        $baseQuery = (clone $filterQuery)
            ->select('wagons.*')
            ->with(['owner', 'tariff', 'cargoRequest.owner', 'cargoRequest.representative', 'cargoRequest.operator'])
            ->orderByDesc('cargo_requests.request_date')
            ->orderByDesc('wagons.cargo_request_id')
            ->orderBy('wagons.id');

        if ($this->wantsExcel($request)) {
            $allWagons = (clone $baseQuery)->get();
            $rows = [];

            foreach ($allWagons as $idx => $wagon) {
                $cr = $wagon->cargoRequest;
                $ownerLabel = $wagon->owner?->name ?? $cr?->owner?->name ?? 'ناشناس';
                $isLoadedUnbilled = $wagon->loaded_weight !== null && $wagon->tariff_id !== null;
                $statusText = $isLoadedUnbilled ? 'بارگیری‌شده (صورتحساب‌نشده)' : 'در انتظار بارگیری';

                $rows[] = [
                    $idx + 1,
                    $cr?->request_number ?? '—',
                    $cr?->request_date_shamsi ?? '—',
                    $wagon->wagon_number,
                    $wagon->cargo_description ?: '—',
                    $wagon->line_number ?: '—',
                    $ownerLabel,
                    $cr?->representative?->name ?? '—',
                    $wagon->tariff?->title ?? '—',
                    $wagon->loaded_weight !== null ? round((float) $wagon->loaded_weight, 2) : '—',
                    $statusText,
                ];
            }

            $dateSuffix = ($fromShamsi !== '' || $toShamsi !== '')
                ? $this->fileDateRange($fromShamsi ?: 'all', $toShamsi ?: 'all')
                : str_replace('/', '-', JalaliDate::today());

            return ExcelExport::download(
                'pending-loading-and-unbilled-'.$dateSuffix.'.xlsx',
                'گزارش واگن‌های در انتظار بارگیری و صورتحساب‌نشده',
                [
                    'ردیف',
                    'شماره درخواست',
                    'تاریخ درخواست (شمسی)',
                    'شماره واگن',
                    'شرح کالا',
                    'شماره خط',
                    'صاحب کالا',
                    'نماینده',
                    'تعرفه ثبت‌شده',
                    'وزن بارگیری (تن)',
                    'وضعیت واگن',
                ],
                $rows,
                [
                    'جمع کل',
                    $summary['total_requests'].' درخواست',
                    '',
                    $summary['total_wagons'].' واگن',
                    'در انتظار بارگیری: '.$summary['pending_loading_wagons'],
                    'صورتحساب‌نشده: '.$summary['unbilled_wagons'],
                    $summary['unknown_owner_wagons'] > 0 ? ($summary['unknown_owner_wagons'].' واگن ناشناس') : '',
                    '',
                    '',
                    '',
                    '',
                ]
            );
        }

        $wagons = $baseQuery->paginate(25)->withQueryString();

        return view('reports.pending-loading', [
            'wagons' => $wagons,
            'summary' => $summary,
            'fromShamsi' => $fromShamsi,
            'toShamsi' => $toShamsi,
            'owners' => Owner::orderBy('name')->get(['id', 'name']),
            'representatives' => Representative::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** گزارش درخواست‌ها و بارگیری‌های روزانه */
    public function dailyLoading(Request $request)
    {
        [$from, $to, $fromShamsi, $toShamsi] = $this->dateRange($request);

        $baseQuery = CargoRequest::with(['owner', 'representative', 'wagons'])
            ->whereBetween('request_date', [$from, $to])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->orderBy('request_date')
            ->orderBy('id');

        $summary = DB::table('wagons')
            ->join('cargo_requests', 'cargo_requests.id', '=', 'wagons.cargo_request_id')
            ->whereBetween('cargo_requests.request_date', [$from, $to])
            ->when($request->filled('status'), fn ($q) => $q->where('cargo_requests.status', $request->string('status')->toString()))
            ->selectRaw('COUNT(wagons.id) as wagon_count')
            ->selectRaw('COUNT(wagons.loaded_weight) as loaded_count')
            ->selectRaw('COALESCE(SUM(wagons.loaded_weight), 0) as total_weight')
            ->first();

        if ($this->wantsExcel($request)) {
            $allRequests = $baseQuery->get();
            $rows = [];

            foreach ($allRequests as $idx => $req) {
                $cargoNames = $req->wagons->pluck('cargo_description')->filter()->unique()->values();
                $lineNames = $req->wagons->pluck('line_number')->filter()->unique()->values();
                $wagonNumbers = $req->wagons->pluck('wagon_number')->filter()->values();
                $loadedCount = $req->wagons->filter(fn ($w) => $w->loaded_weight !== null)->count();
                $totalWeight = round((float) $req->wagons->sum('loaded_weight'), 2);

                $rows[] = [
                    $idx + 1,
                    $req->request_number,
                    $req->request_date_shamsi,
                    $req->owner?->name ?? '—',
                    $req->representative?->name ?? '—',
                    $cargoNames->isEmpty() ? '—' : $cargoNames->implode('، '),
                    $lineNames->isEmpty() ? '—' : $lineNames->implode('، '),
                    $wagonNumbers->isEmpty() ? '—' : $wagonNumbers->implode('، '),
                    (int) $req->wagons->count(),
                    (int) $loadedCount,
                    $totalWeight,
                    $req->statusLabel(),
                ];
            }

            return ExcelExport::download(
                'daily-loading-'.$this->fileDateRange($fromShamsi, $toShamsi).'.xlsx',
                "گزارش روزانه بارگیری‌ها ({$fromShamsi} تا {$toShamsi})",
                [
                    'ردیف',
                    'شماره درخواست',
                    'تاریخ درخواست (شمسی)',
                    'صاحب کالا',
                    'نماینده',
                    'شرح کالا',
                    'شماره خط',
                    'شماره واگن‌ها',
                    'تعداد کل واگن',
                    'واگن بارگیری‌شده',
                    'مجموع وزن بارگیری (تن)',
                    'وضعیت',
                ],
                $rows,
                [
                    'جمع کل',
                    count($rows).' درخواست',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    (int) ($summary->wagon_count ?? 0),
                    (int) ($summary->loaded_count ?? 0),
                    round((float) ($summary->total_weight ?? 0), 2),
                    '',
                ]
            );
        }

        $requests = $baseQuery->paginate(20)->withQueryString();

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

        $issuedQuery = (clone $baseQuery)->where('status', Billing::STATUS_ISSUED);

        $totals = [
            'base' => (clone $issuedQuery)->sum('base_amount'),
            'tax' => (clone $issuedQuery)->sum('tax_amount'),
            'total' => (clone $issuedQuery)->sum('total_amount'),
            'count' => (clone $issuedQuery)->count(),
            'cancelled' => (clone $baseQuery)->where('status', Billing::STATUS_CANCELLED)->count(),
        ];

        if ($this->wantsExcel($request)) {
            $allBillings = (clone $baseQuery)
                ->with(['owner', 'representative', 'operator', 'items.wagon'])
                ->orderBy('billing_date')
                ->orderBy('id')
                ->get();

            $rows = [];
            foreach ($allBillings as $idx => $billing) {
                $cargoNames = $billing->items
                    ->map(fn ($item) => $item->wagon?->cargo_description)
                    ->filter()
                    ->unique()
                    ->values();

                $rows[] = [
                    $idx + 1,
                    $billing->billing_number,
                    $billing->ownerName(),
                    $billing->representativeName() ?? '—',
                    $cargoNames->isEmpty() ? '—' : $cargoNames->implode('، '),
                    (int) $billing->items->count(),
                    $billing->operator?->name ?? '—',
                    $billing->billing_date_shamsi,
                    (int) $billing->base_amount,
                    (float) $billing->tax_percentage,
                    (int) $billing->tax_amount,
                    (int) $billing->total_amount,
                    $billing->statusLabel(),
                ];
            }

            return ExcelExport::download(
                'billings-report-'.$this->fileDateRange($fromShamsi, $toShamsi).'.xlsx',
                "گزارش صورتحساب‌های صادرشده ({$fromShamsi} تا {$toShamsi})",
                [
                    'ردیف',
                    'شماره صورتحساب',
                    'صاحب کالا',
                    'نماینده',
                    'شرح کالا',
                    'تعداد واگن',
                    'اپراتور',
                    'تاریخ صدور (شمسی)',
                    'مبلغ پایه (ریال)',
                    'درصد مالیات',
                    'مالیات (ریال)',
                    'مبلغ نهایی (ریال)',
                    'وضعیت',
                ],
                $rows,
                [
                    'جمع صورتحساب‌های فعال',
                    ((int) $totals['count']).' فاکتور',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    (int) $totals['base'],
                    '',
                    (int) $totals['tax'],
                    (int) $totals['total'],
                    $totals['cancelled'] > 0 ? ($totals['cancelled'].' لغوشده') : '',
                ]
            );
        }

        $billings = (clone $baseQuery)
            ->with(['owner', 'representative', 'operator', 'items.wagon'])
            ->orderBy('billing_date')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('reports.billings', [
            'billings' => $billings,
            'totals' => $totals,
            'fromShamsi' => $fromShamsi,
            'toShamsi' => $toShamsi,
            'statusLabels' => Billing::STATUS_LABELS,
        ]);
    }

    /** گزارش عملکرد اپراتورها (ویژه مدیر سیستم) */
    public function operators(Request $request)
    {
        if (! auth()->user()?->isAdmin()) {
            return redirect()->route('reports.setad')->with('warning', 'دسترسی به این گزارش ویژه مدیر سیستم است.');
        }

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

        if ($this->wantsExcel($request)) {
            $rows = [];
            foreach ($stats as $idx => $row) {
                $rows[] = [
                    $idx + 1,
                    $row->name,
                    (int) $row->billing_count,
                    (int) $row->total_amount,
                ];
            }

            return ExcelExport::download(
                'operators-report-'.$this->fileDateRange($fromShamsi, $toShamsi).'.xlsx',
                "گزارش عملکرد اپراتورها ({$fromShamsi} تا {$toShamsi})",
                ['ردیف', 'نام اپراتور', 'تعداد صورتحساب صادرشده', 'جمع مبلغ (ریال)'],
                $rows,
                [
                    'جمع کل',
                    $stats->count().' اپراتور',
                    (int) $stats->sum('billing_count'),
                    (int) $stats->sum('total_amount'),
                ]
            );
        }

        return view('reports.operators', compact('stats', 'fromShamsi', 'toShamsi'));
    }

    /** گزارش مالی روزانه (ویژه مدیر سیستم) */
    public function financial(Request $request)
    {
        if (! auth()->user()?->isAdmin()) {
            return redirect()->route('reports.setad')->with('warning', 'دسترسی به این گزارش ویژه مدیر سیستم است.');
        }

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

        if ($this->wantsExcel($request)) {
            $rows = [];
            foreach ($daily as $idx => $row) {
                $rows[] = [
                    $idx + 1,
                    $row->billing_date_shamsi,
                    (int) $row->billing_count,
                    (int) $row->base_amount,
                    (int) $row->tax_amount,
                    (int) $row->total_amount,
                ];
            }

            return ExcelExport::download(
                'financial-report-'.$this->fileDateRange($fromShamsi, $toShamsi).'.xlsx',
                "گزارش مالی روزانه ({$fromShamsi} تا {$toShamsi})",
                [
                    'ردیف',
                    'تاریخ (شمسی)',
                    'تعداد فاکتور',
                    'مبلغ پایه (ریال)',
                    'مالیات (ریال)',
                    'مبلغ نهایی (ریال)',
                ],
                $rows,
                [
                    'جمع کل',
                    $daily->count().' روز',
                    (int) $daily->sum('billing_count'),
                    (int) $summary['base'],
                    (int) $summary['tax'],
                    (int) $summary['total'],
                ]
            );
        }

        return view('reports.financial', compact('daily', 'summary', 'fromShamsi', 'toShamsi'));
    }

    /** گزارش تفصیلی هر واگن (با مالیات و مبلغ نهایی همان ردیف) */
    public function wagonDetail(Request $request)
    {
        [$from, $to, $fromShamsi, $toShamsi] = $this->dateRange($request);

        $baseQuery = BillingItem::with([
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
            ->orderByDesc('billing_id');

        $totals = BillingItem::query()
            ->whereHas('billing', fn ($q) => $q
                ->whereBetween('billing_date', [$from, $to])
                ->where('status', Billing::STATUS_ISSUED))
            ->selectRaw('COALESCE(SUM(loaded_weight), 0) as weight')
            ->selectRaw('COALESCE(SUM(base_cost), 0) as base')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as tax')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total')
            ->first();

        if ($this->wantsExcel($request)) {
            $allItems = $baseQuery->get();
            $rows = [];

            foreach ($allItems as $idx => $item) {
                $wagon = $item->wagon;
                $rows[] = [
                    $idx + 1,
                    $wagon?->wagon_number ?? '—',
                    $wagon?->isNumberEdited() ? $wagon->previous_wagon_number : '—',
                    $wagon?->cargo_description ?: '—',
                    $wagon?->line_number ?: '—',
                    $wagon?->ownerName() ?? $item->billing?->ownerName() ?? '—',
                    $wagon?->isOwnerChanged() ? ($wagon->previousOwner?->name ?? '—') : '—',
                    $wagon?->owner_change_note ?: '—',
                    $item->billing?->billing_number ?? '—',
                    $item->billing?->billing_date_shamsi ?? '—',
                    $item->tariff_title,
                    round((float) $item->loaded_weight, 2),
                    (int) $item->base_cost,
                    (int) $item->tax_amount,
                    (int) $item->total_amount,
                ];
            }

            return ExcelExport::download(
                'wagon-detail-report-'.$this->fileDateRange($fromShamsi, $toShamsi).'.xlsx',
                "گزارش تفصیلی واگن‌ها ({$fromShamsi} تا {$toShamsi})",
                [
                    'ردیف',
                    'شماره واگن',
                    'شماره واگن قبلی (اصلاحی)',
                    'شرح کالا',
                    'شماره خط',
                    'صاحب کالا',
                    'صاحب کالای قبلی',
                    'علت ویرایش واگن',
                    'شماره صورتحساب',
                    'تاریخ صورتحساب',
                    'تعرفه',
                    'وزن (تن)',
                    'هزینه پایه (ریال)',
                    'مالیات ردیف (ریال)',
                    'جمع ردیف (ریال)',
                ],
                $rows,
                [
                    'جمع کل',
                    count($rows).' واگن',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    round((float) ($totals->weight ?? 0), 2),
                    (int) ($totals->base ?? 0),
                    (int) ($totals->tax ?? 0),
                    (int) ($totals->total ?? 0),
                ]
            );
        }

        $items = $baseQuery->paginate(25)->withQueryString();

        return view('reports.wagon-detail', compact('items', 'totals', 'fromShamsi', 'toShamsi'));
    }

    /** گزارش عملکرد صاحبان کالا (ویژه مدیر سیستم) */
    public function owners(Request $request)
    {
        if (! auth()->user()?->isAdmin()) {
            return redirect()->route('reports.setad')->with('warning', 'دسترسی به این گزارش ویژه مدیر سیستم است.');
        }

        [$from, $to, $fromShamsi, $toShamsi] = $this->dateRange($request);

        $stats = DB::table('billing_items')
            ->join('billings', 'billings.id', '=', 'billing_items.billing_id')
            ->leftJoin('wagons', 'wagons.id', '=', 'billing_items.wagon_id')
            ->join('owners', function ($join) {
                $join->on('owners.id', '=', DB::raw('COALESCE(wagons.owner_id, billings.owner_id)'));
            })
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

        if ($this->wantsExcel($request)) {
            $rows = [];
            foreach ($stats as $idx => $row) {
                $rows[] = [
                    $idx + 1,
                    $row->name,
                    (int) $row->billing_count,
                    (int) $row->base_amount,
                    (int) $row->tax_amount,
                    (int) $row->total_amount,
                ];
            }

            return ExcelExport::download(
                'owners-report-'.$this->fileDateRange($fromShamsi, $toShamsi).'.xlsx',
                "گزارش عملکرد صاحبان کالا ({$fromShamsi} تا {$toShamsi})",
                [
                    'ردیف',
                    'صاحب کالا',
                    'تعداد صورتحساب',
                    'جمع مبلغ پایه (ریال)',
                    'جمع مالیات (ریال)',
                    'جمع مبلغ پرداختی (ریال)',
                ],
                $rows,
                [
                    'جمع کل',
                    $stats->count().' صاحب کالا',
                    (int) $stats->sum('billing_count'),
                    (int) $stats->sum('base_amount'),
                    (int) $stats->sum('tax_amount'),
                    (int) $stats->sum('total_amount'),
                ]
            );
        }

        return view('reports.owners', compact('stats', 'fromShamsi', 'toShamsi'));
    }

    /** گزارش عملکرد نمایندگان: تعداد درخواست و مبلغ صورتحساب (ویژه مدیر سیستم) */
    public function representatives(Request $request)
    {
        if (! auth()->user()?->isAdmin()) {
            return redirect()->route('reports.setad')->with('warning', 'دسترسی به این گزارش ویژه مدیر سیستم است.');
        }

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

        if ($this->wantsExcel($request)) {
            $rows = [];
            foreach ($stats as $idx => $row) {
                $rows[] = [
                    $idx + 1,
                    $row->name,
                    (int) $row->request_count,
                    (int) $row->total_amount,
                ];
            }

            return ExcelExport::download(
                'representatives-report-'.$this->fileDateRange($fromShamsi, $toShamsi).'.xlsx',
                "گزارش عملکرد نمایندگان ({$fromShamsi} تا {$toShamsi})",
                [
                    'ردیف',
                    'نام نماینده',
                    'تعداد درخواست بارگیری',
                    'مبلغ صورتحساب‌های صادرشده (ریال)',
                ],
                $rows,
                [
                    'جمع کل',
                    $stats->count().' نماینده',
                    (int) $stats->sum('request_count'),
                    (int) $stats->sum('total_amount'),
                ]
            );
        }

        return view('reports.representatives', compact('stats', 'fromShamsi', 'toShamsi'));
    }

    /**
     * گزارش ستاد (لیست واگن‌های فاز ۲ شهید مطهری مطابق فرمت اکسل ستاد)
     */
    public function setad(Request $request)
    {
        [$from, $to, $fromShamsi, $toShamsi] = $this->dateRange($request);

        $baseQuery = BillingItem::query()
            ->select('billing_items.*')
            ->join('billings', 'billings.id', '=', 'billing_items.billing_id')
            ->leftJoin('wagons', 'wagons.id', '=', 'billing_items.wagon_id')
            ->leftJoin('cargo_requests', 'cargo_requests.id', '=', 'wagons.cargo_request_id')
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('billings.billing_date', [$from, $to])
                    ->orWhereBetween('wagons.loaded_on', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('cargo_requests.request_date', [$from->toDateString(), $to->toDateString()]);
            })
            ->where('billings.status', Billing::STATUS_ISSUED)
            ->whereNull('billings.deleted_at')
            ->with([
                'wagon.owner',
                'wagon.previousOwner',
                'wagon.cargoRequest.owner',
                'wagon.tariff',
                'billing.owner',
                'tariff',
            ])
            ->orderBy('billings.billing_date')
            ->orderBy('billing_items.id');

        $allItems = $baseQuery->get();
        $billedWagonIds = $allItems->pluck('wagon_id')->filter()->unique()->all();

        // واگن‌های بارگیری‌شده در همین بازه که هنوز صورتحساب نشده‌اند نیز در گزارش ستاد نمایش داده شوند
        $unbilledWagons = Wagon::query()
            ->where('is_settled', false)
            ->whereNotNull('loaded_weight')
            ->whereNotNull('tariff_id')
            ->when(! empty($billedWagonIds), fn ($q) => $q->whereNotIn('id', $billedWagonIds))
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('loaded_on', [$from->toDateString(), $to->toDateString()])
                    ->orWhereHas('cargoRequest', fn ($cr) => $cr->whereBetween('request_date', [$from->toDateString(), $to->toDateString()]));
            })
            ->with(['owner', 'previousOwner', 'cargoRequest.owner', 'tariff'])
            ->orderBy('loaded_on')
            ->orderBy('id')
            ->get();

        $defaultTaxPct = (float) TaxSetting::currentPercentage();

        $rows = [];
        $totals = [
            'count' => 0,
            'tonnage_count' => 0,
            'tariff_rate' => 0.0,
            'total_with_vat' => 0,
            'customer_payable' => 0.0,
            'customer_tax' => 0.0,
            'booklet_rate' => 0.0,
            'operational_tonnage' => 0.0,
            'per_ton_rate' => 0.0,
        ];

        $rowIndex = 1;
        foreach ($allItems as $item) {
            $mapped = $this->mapSetadRow($item, $rowIndex++, $defaultTaxPct);
            $rows[] = $mapped;

            $totals['count']++;
            $totals['tariff_rate'] += (float) $mapped['tariff_rate'];
            $totals['total_with_vat'] += (int) round($mapped['total_with_vat']);
            $totals['customer_payable'] += (float) $mapped['customer_payable'];
            $totals['customer_tax'] += (float) $mapped['customer_tax'];
            $totals['booklet_rate'] += (float) $mapped['booklet_rate'];
            $totals['operational_tonnage'] += (float) $mapped['operational_tonnage'];
            $totals['per_ton_rate'] += (float) $mapped['per_ton_rate'];

            if ($mapped['is_tonnage']) {
                $totals['tonnage_count']++;
            }
        }

        foreach ($unbilledWagons as $wagon) {
            $mapped = $this->mapUnbilledWagonSetadRow($wagon, $rowIndex++, $defaultTaxPct);
            $rows[] = $mapped;

            $totals['count']++;
            $totals['tariff_rate'] += (float) $mapped['tariff_rate'];
            $totals['total_with_vat'] += (int) round($mapped['total_with_vat']);
            $totals['customer_payable'] += (float) $mapped['customer_payable'];
            $totals['customer_tax'] += (float) $mapped['customer_tax'];
            $totals['booklet_rate'] += (float) $mapped['booklet_rate'];
            $totals['operational_tonnage'] += (float) $mapped['operational_tonnage'];
            $totals['per_ton_rate'] += (float) $mapped['per_ton_rate'];

            if ($mapped['is_tonnage']) {
                $totals['tonnage_count']++;
            }
        }

        if ($this->wantsExcel($request)) {
            return ExcelExport::downloadSetadReport(
                'setad-report-'.$this->fileDateRange($fromShamsi, $toShamsi).'.xlsx',
                $fromShamsi,
                $toShamsi,
                $rows
            );
        }

        return view('reports.setad', [
            'rows' => $rows,
            'totals' => $totals,
            'fromShamsi' => $fromShamsi,
            'toShamsi' => $toShamsi,
        ]);
    }

    /**
     * تبدیل یک ردیف صورتحساب به ساختار ۱۲ ستونی گزارش ستاد (محاسبه و نمایش تناژی برای تمام واگن‌ها)
     *
     * @return array<string, mixed>
     */
    private function mapSetadRow(BillingItem $item, int $index, float $defaultTaxPct = 10.0): array
    {
        $wagon = $item->wagon;
        $billing = $item->billing;
        $tariff = $item->tariff;

        $taxPct = (float) ($billing?->tax_percentage ?? $defaultTaxPct);
        $vatDivisor = $taxPct >= 0 ? round(1 + ($taxPct / 100), 6) : 1.0;
        if ($vatDivisor <= 0) {
            $vatDivisor = 1.0;
        }

        $capacity = (float) ($item->tariff_capacity ?: ($tariff?->capacity ?: 65));
        if ($capacity <= 0) {
            $capacity = 65.0;
        }

        $loadedWeight = (float) ($item->loaded_weight ?: $capacity);
        if ($loadedWeight <= 0) {
            $loadedWeight = $capacity;
        }

        // مبلغ تعرفه در کتابچه: نرخ پایه کامل تعرفه برای ظرفیت کامل واگن
        $bookletRate = (float) ($item->tariff_rate ?: ($tariff?->rate ?: ($wagon?->tariff?->rate ?: 0)));
        if ($bookletRate <= 0) {
            $baseCost = (float) ($item->base_cost ?: (($item->total_amount ?? 0) / $vatDivisor));
            $bookletRate = $loadedWeight > 0 ? round(($baseCost / $loadedWeight) * $capacity) : $baseCost;
        }

        // محاسبه بر اساس کتابچه تعرفه (نرخ هر تن = مبلغ تعرفه در کتابچه ÷ ظرفیت) برای همه واگن‌ها
        $perTonRate = $bookletRate / $capacity;

        // تناژ عملیاتی برای همه واگن‌ها
        $operationalTonnage = $loadedWeight;

        // مبلغ با احتساب مالیات بر ارزش افزوده = مبلغ تعرفه در کتابچه با احتساب مالیات
        $bookletBaseRounded = round($bookletRate);
        $bookletTaxRounded = round($bookletBaseRounded * ($taxPct / 100));
        $totalWithVat = (int) ($bookletBaseRounded + $bookletTaxRounded);

        // هزینه پایه تناژی (تناژ عملیاتی × نرخ هر تن)، مالیات دریافتی و مبلغ کل قابل دریافت از مشتری با احتساب مالیات
        $basePayable = $operationalTonnage * $perTonRate;
        $baseRounded = round($basePayable);
        $taxRounded = round($baseRounded * ($taxPct / 100));
        $customerTax = (float) ($item->tax_amount ?: $taxRounded);
        $customerPayable = (float) ($item->total_amount ?: ($baseRounded + $taxRounded));

        $isTonnage = ($capacity - $operationalTonnage) >= 0.01;
        $equipmentLabel = $tariff?->equipmentLabel() ?? ($wagon?->tariff?->equipmentLabel() ?? 'جرثقیل');
        $calcMethod = $isTonnage ? 'طبق قرارداد-محاسبه تناژی' : 'طبق قرارداد';

        $usedTariffTitle = $item->tariff_title ?: ($tariff?->title ?: ($wagon?->tariff?->title ?: '—'));
        $cargoDescription = $wagon?->cargo_description ?: $usedTariffTitle;

        $notes = collect([
            $billing?->notes,
            $wagon?->cargoRequest?->notes,
        ])->map(fn ($n) => trim((string) $n))->filter()->unique()->implode(' - ');

        return [
            'index' => $index,
            'wagon_id' => $wagon?->id,
            'billing_id' => $billing?->id,
            'wagon_number' => $wagon?->wagon_number ?? '—',
            'cargo_description' => $cargoDescription,
            'used_tariff_title' => $usedTariffTitle,
            'tariff_rate' => $bookletRate,
            'tariff_name' => $cargoDescription,
            'owner_name' => $wagon?->ownerName() ?? $billing?->ownerName() ?? '—',
            'total_with_vat' => $totalWithVat,
            'customer_payable' => $customerPayable,
            'customer_tax' => $customerTax,
            'equipment_label' => $equipmentLabel,
            'booklet_rate' => $bookletRate,
            'vat_divisor' => $vatDivisor,
            'operational_tonnage' => $operationalTonnage,
            'capacity' => $capacity,
            'per_ton_rate' => $perTonRate,
            'calc_method' => $calcMethod,
            'notes' => $notes,
            'is_tonnage' => $isTonnage,
        ];
    }

    /**
     * تبدیل یک واگن بارگیری‌شده (قبل از صدور صورتحساب) به ساختار گزارش ستاد
     *
     * @return array<string, mixed>
     */
    private function mapUnbilledWagonSetadRow(Wagon $wagon, int $index, float $taxPct): array
    {
        $tariff = $wagon->tariff;
        $vatDivisor = $taxPct >= 0 ? round(1 + ($taxPct / 100), 6) : 1.0;
        if ($vatDivisor <= 0) {
            $vatDivisor = 1.0;
        }

        $capacity = (float) ($tariff?->capacity ?: 65);
        if ($capacity <= 0) {
            $capacity = 65.0;
        }

        $operationalTonnage = (float) ($wagon->loaded_weight ?: $capacity);
        if ($operationalTonnage <= 0) {
            $operationalTonnage = $capacity;
        }

        $bookletRate = (float) ($tariff?->rate ?: 0);
        $perTonRate = $bookletRate / $capacity;

        // مبلغ با احتساب مالیات بر ارزش افزوده = مبلغ تعرفه در کتابچه با احتساب مالیات
        $bookletBaseRounded = round($bookletRate);
        $bookletTaxRounded = round($bookletBaseRounded * ($taxPct / 100));
        $totalWithVat = (int) ($bookletBaseRounded + $bookletTaxRounded);

        // هزینه پایه تناژی، مالیات دریافتی و مبلغ کل قابل دریافت از مشتری با احتساب مالیات بر ارزش افزوده
        $basePayable = $operationalTonnage * $perTonRate;
        $baseRounded = round($basePayable);
        $taxRounded = round($baseRounded * ($taxPct / 100));
        $customerTax = (float) $taxRounded;
        $customerPayable = (float) ($baseRounded + $taxRounded);

        $isTonnage = ($capacity - $operationalTonnage) >= 0.01;
        $equipmentLabel = $tariff?->equipmentLabel() ?? 'جرثقیل';
        $calcMethod = $isTonnage ? 'طبق قرارداد-محاسبه تناژی' : 'طبق قرارداد';
        $usedTariffTitle = $tariff?->title ?? '—';
        $cargoDescription = $wagon->cargo_description ?: $usedTariffTitle;

        return [
            'index' => $index,
            'wagon_id' => $wagon->id,
            'billing_id' => null,
            'wagon_number' => $wagon->wagon_number ?? '—',
            'cargo_description' => $cargoDescription,
            'used_tariff_title' => $usedTariffTitle,
            'tariff_rate' => $bookletRate,
            'tariff_name' => $cargoDescription,
            'owner_name' => $wagon->ownerName() ?? '—',
            'total_with_vat' => $totalWithVat,
            'customer_payable' => $customerPayable,
            'customer_tax' => $customerTax,
            'equipment_label' => $equipmentLabel,
            'booklet_rate' => $bookletRate,
            'vat_divisor' => $vatDivisor,
            'operational_tonnage' => $operationalTonnage,
            'capacity' => $capacity,
            'per_ton_rate' => $perTonRate,
            'calc_method' => $calcMethod,
            'notes' => trim((string) ($wagon->cargoRequest?->notes ?? '')),
            'is_tonnage' => $isTonnage,
        ];
    }
}
