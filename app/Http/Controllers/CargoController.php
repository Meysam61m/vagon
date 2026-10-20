<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\BillingItem;
use App\Models\CargoRequest;
use App\Models\Owner;
use App\Models\Representative;
use App\Models\Tariff;
use App\Models\TaxSetting;
use App\Models\Wagon;
use App\Models\WagonOwnerLog;
use App\Support\DocumentNumber;
use App\Support\JalaliDate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CargoController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('quick_wagon')) {
            $quickTerm = trim($request->string('quick_wagon')->toString());
            if ($quickTerm !== '') {
                $matchedWagons = Wagon::query()
                    ->where('wagon_number', $quickTerm)
                    ->orWhere('previous_wagon_number', $quickTerm)
                    ->orderByDesc('id')
                    ->get(['id', 'cargo_request_id']);

                $distinctCargoIds = $matchedWagons->pluck('cargo_request_id')->filter()->unique()->values();
                if ($distinctCargoIds->count() === 1) {
                    return redirect()->route('cargo.show', $distinctCargoIds->first());
                }

                $exactRequest = CargoRequest::where('request_number', $quickTerm)->first();
                if ($exactRequest) {
                    return redirect()->route('cargo.show', $exactRequest);
                }

                $request->merge(['search' => $quickTerm]);
            }
        }

        $queueFilter = $request->string('queue')->toString();

        $cargoRequests = CargoRequest::with(['owner', 'representative.owners', 'wagons.owner'])
            ->when($queueFilter === 'my_today', function ($q) {
                $q->where('operator_id', auth()->id())
                    ->whereDate('created_at', today());
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('owner_id'), function ($q) use ($request) {
                if ($request->string('owner_id')->toString() === 'unknown') {
                    $q->whereNull('owner_id')
                        ->whereDoesntHave('wagons', fn ($w) => $w->whereNotNull('owner_id'));

                    return;
                }

                $ownerId = $request->integer('owner_id');

                $q->where(function ($sub) use ($ownerId) {
                    $sub->where('owner_id', $ownerId)
                        ->orWhereHas('wagons', fn ($w) => $w->where('owner_id', $ownerId));
                });
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.trim($request->string('search')).'%';

                $q->where(function ($inner) use ($term) {
                    $inner->where('request_number', 'like', $term)
                        ->orWhereHas('owner', fn ($owner) => $owner->where('name', 'like', $term))
                        ->orWhereHas('wagons', fn ($wagon) => $wagon
                            ->where('wagon_number', 'like', $term)
                            ->orWhere('previous_wagon_number', 'like', $term)
                            ->orWhere('cargo_description', 'like', $term)
                            ->orWhereHas('owner', fn ($wOwner) => $wOwner->where('name', 'like', $term)));
                });
            })
            ->latest('request_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $queueStats = [
            'pending_loading' => CargoRequest::where('status', CargoRequest::STATUS_PENDING)->count(),
            'ready_to_bill' => CargoRequest::where('status', CargoRequest::STATUS_LOADED)->count(),
            'unknown_owner' => CargoRequest::whereNull('owner_id')
                ->where('status', '!=', CargoRequest::STATUS_BILLED)
                ->count(),
            'my_today_requests' => CargoRequest::where('operator_id', auth()->id())
                ->whereDate('created_at', today())
                ->count(),
            'my_today_billings' => Billing::where('operator_id', auth()->id())
                ->where('status', Billing::STATUS_ISSUED)
                ->whereDate('created_at', today())
                ->count(),
            'total_count' => CargoRequest::count(),
        ];

        return view('cargo.index', [
            'cargoRequests' => $cargoRequests,
            'owners' => Owner::orderBy('name')->get(['id', 'name']),
            'statusLabels' => CargoRequest::STATUS_LABELS,
            'queueStats' => $queueStats,
            'queueFilter' => $queueFilter,
        ]);
    }

    /**
     * صفحه محاسبه‌گر تعرفه و تناژ (صرفاً جهت محاسبه و بدون ثبت در سیستم)
     */
    public function calculator()
    {
        return view('calculator.index', [
            'tariffs' => Tariff::active()->orderBy('title')->get(),
            'taxPercentage' => TaxSetting::currentPercentage(),
        ]);
    }

    public function create()
    {
        $owners = Owner::active()->orderBy('name')->get();
        $representatives = Representative::active()->with('owners')->orderBy('name')->get();
        $tariffs = Tariff::active()->orderBy('title')->get();

        // استخراج آخرین درخواست و شرح کالا/تعرفه هر صاحب کالا و هر نماینده برای پیشنهاد هوشمند به اپراتور
        $recentRequests = CargoRequest::with(['wagons' => fn ($q) => $q->latest('id')])
            ->orderByDesc('id')
            ->limit(250)
            ->get();

        $ownerDefaults = [];
        $repDefaults = [];
        foreach ($recentRequests as $req) {
            $lastWagon = $req->wagons->first();
            $desc = trim((string) ($lastWagon?->cargo_description ?? ''));
            $tariffId = $lastWagon?->tariff_id
                ?: ($desc !== '' ? $this->suggestTariffId($desc, $tariffs) : null);

            $oid = $req->owner_id ? (int) $req->owner_id : 0;
            if ($oid > 0 && ! isset($ownerDefaults[$oid])) {
                $ownerDefaults[$oid] = [
                    'representative_id' => (int) $req->representative_id,
                    'description' => $desc,
                    'tariff_id' => $tariffId ? (int) $tariffId : null,
                ];
            }

            $rid = (int) $req->representative_id;
            if ($rid > 0 && ! isset($repDefaults[$rid]) && $desc !== '') {
                $repDefaults[$rid] = [
                    'owner_id' => $oid > 0 ? $oid : null,
                    'description' => $desc,
                    'tariff_id' => $tariffId ? (int) $tariffId : null,
                ];
            }
        }

        // شماره‌واگن‌های باز (تسویه‌نشده) برای هشدار لحظه‌ای عدم تکرار
        $unsettledWagonNumbers = Wagon::where('is_settled', false)
            ->pluck('wagon_number')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return view('cargo.create', [
            'owners' => $owners,
            'representatives' => $representatives,
            'tariffs' => $tariffs,
            'ownerDefaults' => $ownerDefaults,
            'repDefaults' => $repDefaults,
            'unsettledWagonNumbers' => $unsettledWagonNumbers,
            'requestDateShamsi' => JalaliDate::today(),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->input('owner_id') === '' || $request->input('owner_id') === 'unknown') {
            $request->merge(['owner_id' => null]);
        }

        $data = $request->validate([
            'owner_id' => ['nullable', 'integer', 'exists:owners,id'],
            'representative_id' => ['required', 'integer', 'exists:representatives,id'],
            'request_date_shamsi' => ['required', 'string'],
            'wagons' => ['required', 'array', 'min:1'],
            'wagons.*.number' => ['nullable', 'string', 'max:50'],
            'wagons.*.description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'representative_id.required' => 'انتخاب نام نماینده الزامی است.',
        ], [
            'owner_id' => 'صاحب کالا',
            'representative_id' => 'نماینده',
            'request_date_shamsi' => 'تاریخ درخواست',
            'wagons' => 'واگن‌ها',
            'wagons.*.number' => 'شماره واگن',
            'wagons.*.description' => 'شرح کالا',
        ]);

        $ownerId = ! empty($data['owner_id']) ? (int) $data['owner_id'] : null;

        // اگر صاحب کالا انتخاب شده باشد، بررسی الزامی رابطه صاحب کالا و نماینده
        if ($ownerId !== null) {
            $this->assertOwnerRepresentativeLinked($ownerId, (int) $data['representative_id'], 'representative_id');
        }

        $requestDate = JalaliDate::parse($data['request_date_shamsi'], 'request_date_shamsi');
        $normalizedDate = JalaliDate::normalize($data['request_date_shamsi']);

        // فقط ردیف‌هایی که شماره واگن دارند ثبت می‌شوند؛ شرح کالا برای هر واگن اجباری است
        $wagonRows = collect($data['wagons'])
            ->map(fn ($row, $index) => [
                'index' => $index,
                'number' => trim((string) ($row['number'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
            ])
            ->filter(fn ($row) => $row['number'] !== '')
            ->values();

        if ($wagonRows->isEmpty()) {
            throw ValidationException::withMessages([
                'wagons' => 'حداقل یک شماره واگن معتبر وارد کنید.',
            ]);
        }

        $missingDescription = $wagonRows->first(fn ($row) => $row['description'] === '');

        if ($missingDescription !== null) {
            throw ValidationException::withMessages([
                'wagons.'.$missingDescription['index'].'.description' => 'شرح کالا برای واگن «'.$missingDescription['number'].'» الزامی است.',
            ]);
        }

        $duplicateNumber = $wagonRows->duplicates('number')->first();

        if ($duplicateNumber !== null) {
            throw ValidationException::withMessages([
                'wagons' => "شماره واگن «{$duplicateNumber}» تکراری است.",
            ]);
        }

        $cargoRequest = DocumentNumber::withRetry(function () use (
            $data,
            $ownerId,
            $requestDate,
            $normalizedDate,
            $wagonRows
        ) {
            $cargoRequest = CargoRequest::create([
                'request_number' => CargoRequest::generateRequestNumber(),
                'owner_id' => $ownerId,
                'representative_id' => $data['representative_id'],
                'operator_id' => auth()->id(),
                'request_date' => $requestDate->toDateString(),
                'request_date_shamsi' => $normalizedDate,
                'status' => CargoRequest::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($wagonRows as $wagonRow) {
                Wagon::create([
                    'cargo_request_id' => $cargoRequest->id,
                    'owner_id' => $ownerId,
                    'wagon_number' => $wagonRow['number'],
                    'cargo_description' => $wagonRow['description'],
                ]);
            }

            return $cargoRequest;
        });

        return redirect()
            ->route('cargo.show', $cargoRequest)
            ->with('success', 'درخواست بارگیری با موفقیت ثبت شد.');
    }

    public function show(CargoRequest $cargo)
    {
        $cargo->load([
            'owner', 'representative.owners', 'operator',
            'wagons.owner', 'wagons.previousOwner', 'wagons.ownerChangedBy',
            'wagons.tariff', 'wagons.billingItem.billing',
        ]);

        $tariffs = Tariff::orderByDesc('is_active')->orderBy('title')->get();
        $owners = Owner::active()->orderBy('name')->get(['id', 'name']);

        $suggestedTariffs = [];
        foreach ($cargo->wagons as $wagon) {
            if (! $wagon->tariff_id && $wagon->cargo_description) {
                $suggestedTariffs[$wagon->id] = $this->suggestTariffId((string) $wagon->cargo_description, $tariffs);
            }
        }

        return view('cargo.show', [
            'cargo' => $cargo,
            'tariffs' => $tariffs,
            'owners' => $owners,
            'suggestedTariffs' => $suggestedTariffs,
            'loadingDateShamsi' => JalaliDate::today(),
        ]);
    }

    /**
     * ثبت/ویرایش عملیات بارگیری واگن‌های یک درخواست (با قابلیت صدور هم‌زمان صورتحساب با ۱ کلیک).
     */
    public function storeLoading(Request $request, CargoRequest $cargo)
    {
        if ($cargo->status === CargoRequest::STATUS_BILLED) {
            return back()->with('warning', 'برای درخواست صورتحساب‌شده امکان تغییر بارگیری وجود ندارد.');
        }

        if ($request->input('owner_id') === '' || $request->input('owner_id') === 'unknown') {
            $request->merge(['owner_id' => null]);
        }

        $data = $request->validate([
            'owner_id' => ['nullable', 'integer', 'exists:owners,id'],
            'loading_date_shamsi' => ['required', 'string'],
            'loading' => ['required', 'array'],
            'loading.*.tariff_id' => ['nullable', 'integer', 'exists:tariffs,id'],
            'loading.*.weight' => ['nullable', 'numeric', 'min:0.01', 'max:200'],
            'loading.*.description' => ['nullable', 'string', 'max:255'],
        ], [], [
            'owner_id' => 'صاحب کالا',
            'loading_date_shamsi' => 'تاریخ بارگیری',
            'loading' => 'ردیف‌های بارگیری',
            'loading.*.description' => 'شرح کالا',
        ]);

        $selectedOwnerId = ! empty($data['owner_id']) ? (int) $data['owner_id'] : null;
        $issueBillingMode = (string) $request->input('issue_billing', '0');

        // اگر صاحب کالا انتخاب شده است، بررسی الزامی رابطه صاحب کالا و نماینده این درخواست
        if ($selectedOwnerId !== null) {
            $this->assertOwnerRepresentativeLinked($selectedOwnerId, (int) $cargo->representative_id, 'owner_id');
        }

        // هنگام صدور صورتحساب، نام صاحب کالا و تطابق آن با نماینده الزامی است
        $effectiveOwnerId = $selectedOwnerId ?? $cargo->owner_id;
        if (in_array($issueBillingMode, ['1', 'true', 'print'], true)) {
            if ($effectiveOwnerId === null) {
                throw ValidationException::withMessages([
                    'owner_id' => 'هنگام صدور صورتحساب، انتخاب نام صاحب کالا الزامی است. لطفاً نام صاحب کالا را مشخص کنید.',
                ]);
            }

            $this->assertOwnerRepresentativeLinked((int) $effectiveOwnerId, (int) $cargo->representative_id, 'owner_id');
        }

        $loadingDate = JalaliDate::parse($data['loading_date_shamsi'], 'loading_date_shamsi');
        $normalizedDate = JalaliDate::normalize($data['loading_date_shamsi']);

        $cargo->load('wagons');

        $tariffs = Tariff::whereIn(
            'id',
            collect($data['loading'])->pluck('tariff_id')->filter()->all()
        )->get()->keyBy('id');

        DB::transaction(function () use ($cargo, $data, $selectedOwnerId, $loadingDate, $tariffs) {
            if ($selectedOwnerId !== null && (int) $cargo->owner_id !== $selectedOwnerId) {
                $cargo->update(['owner_id' => $selectedOwnerId]);
                Wagon::where('cargo_request_id', $cargo->id)
                    ->where('is_settled', false)
                    ->whereNull('owner_id')
                    ->update(['owner_id' => $selectedOwnerId]);
            }

            /** @var Wagon $wagon */
            foreach ($cargo->wagons as $wagon) {
                $row = $data['loading'][$wagon->id] ?? null;

                // واگن تسویه‌شده یا ردیفی که برایش مقداری ارسال نشده، دست‌نخورده می‌ماند
                if (! $row || $wagon->is_settled) {
                    continue;
                }

                $weight = isset($row['weight']) && $row['weight'] !== '' ? (float) $row['weight'] : null;
                $tariffId = $row['tariff_id'] ?? null;

                // شرح کالا از همین فرم هم قابل ویرایش است؛ اگر کلید ارسال نشده باشد
                // مقدار قبلی حفظ می‌شود (سازگاری با فرم‌های قدیمی).
                $description = array_key_exists('description', $row)
                    ? trim((string) $row['description'])
                    : (string) $wagon->cargo_description;

                if ($weight === null) {
                    if ($description !== (string) $wagon->cargo_description) {
                        $wagon->update([
                            'cargo_description' => $description !== '' ? $description : null,
                        ]);
                    }

                    continue;
                }

                // برای واگنی که تازه بارگیری می‌شود، شرح کالا اجباری است
                if ($wagon->loaded_weight === null && $description === '') {
                    throw ValidationException::withMessages([
                        'loading.'.$wagon->id.'.description' => "شرح کالا برای واگن {$wagon->wagon_number} الزامی است.",
                    ]);
                }

                if (! $tariffId || ! $tariffs->has((int) $tariffId)) {
                    throw ValidationException::withMessages([
                        'loading.'.$wagon->id.'.tariff_id' => "برای واگن {$wagon->wagon_number} تعرفه انتخاب نشده است.",
                    ]);
                }

                /** @var Tariff $tariff */
                $tariff = $tariffs->get((int) $tariffId);

                if ($weight > (float) $tariff->capacity) {
                    throw ValidationException::withMessages([
                        'loading.'.$wagon->id.'.weight' => "وزن واگن {$wagon->wagon_number} از ظرفیت تعرفه ({$tariff->capacity} تن) بیشتر است.",
                    ]);
                }

                $wagon->update([
                    'owner_id' => $wagon->owner_id ?? $selectedOwnerId ?? $cargo->owner_id,
                    'tariff_id' => $tariff->id,
                    'loaded_weight' => $weight,
                    'loaded_on' => $loadingDate->toDateString(),
                    'cargo_description' => $description !== '' ? $description : null,
                ]);
            }

            $cargo->refresh()->load('wagons');

            if ($cargo->allWagonsLoaded()) {
                $cargo->update(['status' => CargoRequest::STATUS_LOADED]);
            } elseif ($cargo->status === CargoRequest::STATUS_LOADED) {
                $cargo->update(['status' => CargoRequest::STATUS_PENDING]);
            }
        });

        if (in_array($issueBillingMode, ['1', 'true', 'print'], true)) {
            $cargo->refresh()->load('wagons.tariff');
            $readyWagons = $cargo->wagons->filter(fn (Wagon $w) => ! $w->is_settled && $w->isLoaded() && $w->tariff_id);

            if ($readyWagons->isEmpty()) {
                return redirect()
                    ->route('cargo.show', $cargo)
                    ->with('warning', 'اطلاعات بارگیری ذخیره شد، اما هیچ واگن بارگیری‌شده و تسویه‌نشده‌ای برای صدور صورتحساب وجود ندارد.');
            }

            $billing = $this->issueInvoiceForCargoRequest($cargo, $normalizedDate);

            return redirect()
                ->route('billing.show', $billing)
                ->with('success', "اطلاعات بارگیری ثبت شد و صورتحساب «{$billing->billing_number}» صادر گردید. لطفاً اطلاعات فیش واریزی را وارد و تسویه حساب را ثبت کنید تا برگه ۴ بخشی چاپ شود.");
        }

        return redirect()
            ->route('cargo.show', $cargo)
            ->with('success', 'اطلاعات بارگیری ثبت شد.');
    }

    /**
     * صدور مستقیم صورتحساب یک‌کلیکی برای واگن‌های بارگیری‌شده یک درخواست بارگیری
     */
    public function quickBill(Request $request, CargoRequest $cargo)
    {
        if ($request->input('owner_id') === '' || $request->input('owner_id') === 'unknown') {
            $request->merge(['owner_id' => null]);
        }

        $validated = $request->validate([
            'owner_id' => ['nullable', 'integer', 'exists:owners,id'],
        ], [], [
            'owner_id' => 'صاحب کالا',
        ]);

        if (! empty($validated['owner_id'])) {
            $selectedOwnerId = (int) $validated['owner_id'];
            $this->assertOwnerRepresentativeLinked($selectedOwnerId, (int) $cargo->representative_id, 'owner_id');
            $cargo->update(['owner_id' => $selectedOwnerId]);
            Wagon::where('cargo_request_id', $cargo->id)
                ->where('is_settled', false)
                ->whereNull('owner_id')
                ->update(['owner_id' => $selectedOwnerId]);
        }

        $cargo->refresh()->load('wagons.tariff');
        $readyWagons = $cargo->wagons->filter(fn (Wagon $w) => ! $w->is_settled && $w->isLoaded() && $w->tariff_id);

        if ($readyWagons->isEmpty()) {
            return back()->with('warning', 'ابتدا تعرفه و وزن بارگیری واگن‌های این درخواست را ثبت کنید.');
        }

        $effectiveOwnerId = $cargo->owner_id ?? $readyWagons->first(fn (Wagon $w) => $w->effectiveOwnerId() !== null)?->effectiveOwnerId();
        if (! $effectiveOwnerId) {
            return redirect()
                ->route('cargo.show', $cargo)
                ->with('error', 'هنگام صدور صورتحساب، انتخاب نام صاحب کالا الزامی است. لطفاً ابتدا صاحب کالا را مشخص کنید.');
        }

        $this->assertOwnerRepresentativeLinked((int) $effectiveOwnerId, (int) $cargo->representative_id, 'owner_id');

        $billing = $this->issueInvoiceForCargoRequest($cargo, JalaliDate::today());

        return redirect()
            ->route('billing.show', $billing)
            ->with('success', "صورتحساب «{$billing->billing_number}» صادر شد. لطفاً اطلاعات فیش واریزی را وارد و تسویه حساب را ثبت کنید تا برگه ۴ بخشی چاپ شود.");
    }

    /**
     * تشخیص هوشمند تعرفه متناظر بر اساس شرح کالای واگن
     */
    private function suggestTariffId(string $cargoDescription, Collection $tariffs): ?int
    {
        $desc = str_replace(['ي', 'ك', "\u{200c}"], ['ی', 'ک', ' '], trim($cargoDescription));
        if ($desc === '') {
            return null;
        }

        $activeTariffs = $tariffs->where('is_active', true);

        // تطبیق دقیق کلیدواژه‌های کالا با تعرفه‌های کتابچه ۱۴۰۵
        $keywordCodeMap = [
            'T-1405-01' => ['میلگرد', 'کلاف', 'ناودونی', 'نبشی', 'تسمه', 'سوله', 'شیشه', 'آهن', 'ورق', 'تیرآهن'],
            'T-1405-02' => ['سود پرک', 'سودپرک'],
            'T-1405-03' => ['سیمان', 'جامبو'],
            'T-1405-04' => ['کاشی', 'چینی', 'سرامیک', 'شیر خشک', 'شیرخشک', 'آبمیوه', 'ابمیوه', 'شیر', 'کابل'],
            'T-1405-05' => ['سنگ ساختمانی', 'سنگ'],
            'T-1405-06' => ['کروم', 'جوش شیرین'],
            'T-1405-08' => ['ام دی اف', 'MDF', 'mdf', 'نئوپان', 'تخته'],
            'T-1405-09' => ['رزین'],
            'T-1404-02' => ['متعلقات', 'دستگاه'],
            'T-1404-05' => ['اسید استیک', 'اسید'],
        ];

        foreach ($keywordCodeMap as $code => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($desc, $kw)) {
                    $found = $activeTariffs->firstWhere('code', $code);
                    if ($found) {
                        return (int) $found->id;
                    }
                }
            }
        }

        // تطبیق عمومی با عنوان تعرفه‌ها
        foreach ($activeTariffs as $tariff) {
            $titleNorm = str_replace(['ي', 'ك', "\u{200c}"], ['ی', 'ک', ' '], (string) $tariff->title);
            if (str_contains($titleNorm, $desc)) {
                return (int) $tariff->id;
            }
        }

        return null;
    }

    /**
     * صدور خودکار صورتحساب برای تمام واگن‌های بارگیری‌شده و تسویه‌نشده یک درخواست بارگیری
     */
    private function issueInvoiceForCargoRequest(CargoRequest $cargo, ?string $shamsiDate = null): Billing
    {
        $billingDateShamsi = $shamsiDate ? JalaliDate::normalize($shamsiDate) : JalaliDate::today();
        $billingDate = JalaliDate::parse($billingDateShamsi, 'billing_date_shamsi');

        return DocumentNumber::withRetry(function () use ($cargo, $billingDate, $billingDateShamsi) {
            $cargo->load(['owner', 'representative', 'wagons.tariff']);
            $taxPercentage = TaxSetting::currentPercentage();
            $representative = $cargo->representative;

            $wagons = Wagon::with('tariff')
                ->where('cargo_request_id', $cargo->id)
                ->where('is_settled', false)
                ->whereNotNull('loaded_weight')
                ->whereNotNull('tariff_id')
                ->lockForUpdate()
                ->get();

            if ($wagons->isEmpty()) {
                throw ValidationException::withMessages([
                    'loading' => 'هیچ واگن بارگیری‌شده و تسویه‌نشده‌ای برای صدور صورتحساب یافت نشد.',
                ]);
            }

            $resolvedOwnerId = $cargo->owner_id
                ?? $wagons->first(fn (Wagon $w) => $w->effectiveOwnerId() !== null)?->effectiveOwnerId();

            if (! $resolvedOwnerId) {
                throw ValidationException::withMessages([
                    'owner_id' => 'هنگام صدور صورتحساب، انتخاب نام صاحب کالا الزامی است.',
                ]);
            }

            $owner = $cargo->owner ?? Owner::findOrFail($resolvedOwnerId);

            // بررسی الزامی رابطه صاحب کالا و نماینده هنگام صدور صورتحساب
            if ($cargo->representative_id) {
                $this->assertOwnerRepresentativeLinked((int) $owner->id, (int) $cargo->representative_id, 'owner_id');
            }

            if (! $cargo->owner_id) {
                $cargo->update(['owner_id' => $owner->id]);
            }

            $items = [];
            $baseAmount = 0;
            $taxAmount = 0;

            foreach ($wagons as $wagon) {
                $tariff = $wagon->tariff;
                if (! $tariff) {
                    continue;
                }

                $weight = (float) $wagon->loaded_weight;
                if ($weight > (float) $tariff->capacity) {
                    throw ValidationException::withMessages([
                        'wagons' => "وزن بارگیری واگن {$wagon->wagon_number} ({$weight} تن) نمی‌تواند بیشتر از ظرفیت تعرفه ({$tariff->capacity} تن) باشد.",
                    ]);
                }

                $baseCost = BillingItem::calculateBaseCost($weight, (float) $tariff->capacity, (float) $tariff->rate);
                $itemTax = BillingItem::calculateTax($baseCost, $taxPercentage);

                $baseAmount += $baseCost;
                $taxAmount += $itemTax;

                $items[] = [
                    'wagon_id' => $wagon->id,
                    'tariff_id' => $tariff->id,
                    'tariff_title' => $tariff->title,
                    'tariff_rate' => $tariff->rate,
                    'tariff_capacity' => $tariff->capacity,
                    'loaded_weight' => $weight,
                    'base_cost' => $baseCost,
                    'tax_amount' => $itemTax,
                    'total_amount' => $baseCost + $itemTax,
                ];
            }

            $billing = Billing::create([
                'billing_number' => Billing::generateBillingNumber(),
                'owner_id' => $owner->id,
                'representative_id' => $representative?->id,
                'operator_id' => auth()->id(),
                'owner_name' => $owner->name,
                'representative_name' => $representative?->name,
                'billing_date' => $billingDate->toDateString(),
                'billing_date_shamsi' => $billingDateShamsi,
                'base_amount' => $baseAmount,
                'tax_percentage' => $taxPercentage,
                'tax_amount' => $taxAmount,
                'total_amount' => $baseAmount + $taxAmount,
                'status' => Billing::STATUS_ISSUED,
                'notes' => $cargo->notes,
            ]);

            foreach ($items as $item) {
                $billing->items()->create($item);
            }

            Wagon::whereIn('id', array_column($items, 'wagon_id'))->update([
                'is_settled' => true,
                'owner_id' => $owner->id,
            ]);

            $cargo->refresh()->load('wagons');
            if ($cargo->wagons->every(fn (Wagon $w) => $w->is_settled)) {
                $cargo->update(['status' => CargoRequest::STATUS_BILLED]);
            }

            return $billing;
        });
    }

    /**
     * فرم ویرایش صاحب کالای واگن (به‌ویژه پس از صدور صورتحساب) همراه با تاریخچه ویرایش‌ها.
     */
    public function editWagonOwner(Wagon $wagon)
    {
        $wagon->load([
            'cargoRequest.owner',
            'cargoRequest.representative',
            'owner',
            'previousOwner',
            'ownerChangedBy',
            'tariff',
            'billingItem.billing',
            'ownerLogs.user',
            'ownerLogs.billing',
        ]);

        $owners = Owner::orderByDesc('is_active')->orderBy('name')->get();
        $representatives = Representative::active()->with('owners')->orderBy('name')->get();

        return view('wagons.edit-owner', [
            'wagon' => $wagon,
            'owners' => $owners,
            'representatives' => $representatives,
        ]);
    }

    /**
     * ذخیره ویرایش شماره واگن و/یا صاحب کالای واگن با الزام به ثبت علت و نمایش در داشبورد مدیر.
     */
    public function updateWagonOwner(Request $request, Wagon $wagon)
    {
        $wagon->load(['cargoRequest.owner', 'owner', 'billingItem.billing.items.wagon']);

        if (! $request->filled('owner_change_note')) {
            if ($request->filled('note')) {
                $request->merge(['owner_change_note' => $request->input('note')]);
            } elseif ($request->filled('reason')) {
                $request->merge(['owner_change_note' => $request->input('reason')]);
            } elseif ($request->filled('description')) {
                $request->merge(['owner_change_note' => $request->input('description')]);
            }
        }

        if (! $request->filled('wagon_number')) {
            $request->merge(['wagon_number' => $wagon->wagon_number]);
        }

        if ($request->input('owner_id') === '' || $request->input('owner_id') === 'unknown') {
            $request->merge(['owner_id' => null]);
        } elseif (! $request->filled('owner_id') && $wagon->effectiveOwnerId()) {
            $request->merge(['owner_id' => $wagon->effectiveOwnerId()]);
        }

        $isBilledOrSettled = $wagon->is_settled || $wagon->billingItem !== null;

        $data = $request->validate([
            'wagon_number' => ['required', 'string', 'max:50'],
            'owner_id' => [$isBilledOrSettled ? 'required' : 'nullable', 'integer', 'exists:owners,id'],
            'representative_id' => ['nullable', 'integer', 'exists:representatives,id'],
            'owner_change_note' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'wagon_number.required' => 'وارد کردن شماره واگن الزامی است.',
            'owner_id.required' => 'برای واگن صورتحساب‌شده، انتخاب صاحب کالا الزامی است.',
            'owner_change_note.required' => 'ثبت علت و توضیح برای ویرایش واگن الزامی است.',
            'owner_change_note.min' => 'علت و توضیح ویرایش واگن باید حداقل ۳ کاراکتر باشد.',
        ], [
            'wagon_number' => 'شماره واگن',
            'owner_id' => 'صاحب کالا',
            'representative_id' => 'نماینده',
            'owner_change_note' => 'علت و توضیحات ویرایش',
        ]);

        if (! empty($data['owner_id'])) {
            $repToValidate = ! empty($data['representative_id'])
                ? (int) $data['representative_id']
                : (int) ($wagon->cargoRequest?->representative_id ?? 0);

            if (! empty($data['representative_id'])) {
                $this->assertOwnerRepresentativeLinked((int) $data['owner_id'], (int) $data['representative_id'], 'representative_id');
            }
        }

        $oldWagonNumber = trim((string) $wagon->wagon_number);
        $newWagonNumber = trim((string) $data['wagon_number']);

        if ($newWagonNumber === '') {
            throw ValidationException::withMessages([
                'wagon_number' => 'وارد کردن شماره واگن معتبر الزامی است.',
            ]);
        }

        $oldOwnerId = $wagon->effectiveOwnerId();
        $oldOwner = $wagon->owner ?? $wagon->cargoRequest?->owner ?? ($oldOwnerId ? Owner::find($oldOwnerId) : null);
        $newOwner = ! empty($data['owner_id']) ? Owner::findOrFail($data['owner_id']) : null;
        $newOwnerId = $newOwner?->id;

        $numberChanged = $newWagonNumber !== $oldWagonNumber;
        $ownerChanged = (int) ($oldOwnerId ?? 0) !== (int) ($newOwnerId ?? 0);

        if (! $numberChanged && ! $ownerChanged) {
            throw ValidationException::withMessages([
                'wagon_number' => 'هیچ تغییری در شماره واگن یا صاحب کالا اعمال نشده است. لطفاً شماره واگن صحیح یا صاحب کالای جدید را وارد کنید.',
            ]);
        }

        if ($numberChanged) {
            $duplicateInRequest = Wagon::where('cargo_request_id', $wagon->cargo_request_id)
                ->where('wagon_number', $newWagonNumber)
                ->where('id', '!=', $wagon->id)
                ->exists();

            if ($duplicateInRequest) {
                throw ValidationException::withMessages([
                    'wagon_number' => "شماره واگن «{$newWagonNumber}» قبلاً در همین درخواست بارگیری ثبت شده است.",
                ]);
            }
        }

        $note = trim((string) $data['owner_change_note']);

        if ($note === '') {
            throw ValidationException::withMessages([
                'owner_change_note' => 'ثبت علت و توضیح برای ویرایش واگن الزامی است.',
            ]);
        }

        $billing = $wagon->billingItem?->billing;

        $changeType = match (true) {
            $numberChanged && $ownerChanged => 'both',
            $numberChanged => 'wagon_number',
            default => 'owner',
        };

        DB::transaction(function () use (
            $wagon,
            $oldWagonNumber,
            $newWagonNumber,
            $numberChanged,
            $oldOwnerId,
            $oldOwner,
            $newOwner,
            $ownerChanged,
            $changeType,
            $note,
            $data,
            $billing
        ) {
            $updatePayload = [
                'owner_change_note' => $note,
                'owner_changed_at' => now(),
                'owner_changed_by' => auth()->id(),
                'owner_change_count' => (int) $wagon->owner_change_count + 1,
            ];

            if ($numberChanged) {
                $updatePayload['previous_wagon_number'] = $oldWagonNumber;
                $updatePayload['wagon_number'] = $newWagonNumber;
            }

            if ($ownerChanged) {
                $updatePayload['previous_owner_id'] = $oldOwnerId;
                $updatePayload['owner_id'] = $newOwner?->id;
            }

            $wagon->update($updatePayload);

            if ($newOwner || $oldOwnerId) {
                WagonOwnerLog::create([
                    'wagon_id' => $wagon->id,
                    'billing_id' => $billing?->id,
                    'change_type' => $changeType,
                    'old_wagon_number' => $oldWagonNumber,
                    'new_wagon_number' => $newWagonNumber,
                    'old_owner_id' => $oldOwnerId,
                    'new_owner_id' => $newOwner?->id ?? $oldOwnerId,
                    'old_owner_name' => $oldOwner?->name ?? 'ناشناس',
                    'new_owner_name' => $newOwner?->name ?? 'ناشناس',
                    'user_id' => auth()->id(),
                    'note' => $note,
                ]);
            }

            if ($ownerChanged && $newOwner) {
                // اگر صورتحساب فعالی برای این واگن صادر شده و همه واگن‌های آن صورتحساب اکنون متعلق به صاحب جدید هستند،
                // مشخصات صاحب کالا در هدر صورتحساب نیز به‌روز می‌شود.
                if ($billing && ! $billing->isCancelled()) {
                    $billing->load('items.wagon.cargoRequest');

                    $allBillingWagonsSameOwner = $billing->items->isNotEmpty()
                        && $billing->items->every(fn ($item) => (int) $item->wagon?->effectiveOwnerId() === (int) $newOwner->id);

                    if ($allBillingWagonsSameOwner) {
                        $newRep = null;

                        if (! empty($data['representative_id'])) {
                            $newRep = Representative::find($data['representative_id']);
                        } elseif ($billing->representative_id && DB::table('owner_representative')
                            ->where('owner_id', $newOwner->id)
                            ->where('representative_id', $billing->representative_id)
                            ->exists()) {
                            $newRep = $billing->representative;
                        } else {
                            $newRep = $newOwner->representatives()->where('is_active', true)->orderBy('representatives.id')->first();
                        }

                        $billing->update([
                            'owner_id' => $newOwner->id,
                            'owner_name' => $newOwner->name,
                            'representative_id' => $newRep?->id,
                            'representative_name' => $newRep?->name,
                        ]);
                    }
                }

                // اگر همه واگن‌های درخواست بارگیری به صاحب جدید منتقل شده‌اند، درخواست بارگیری هم همگام شود
                $cargoRequest = $wagon->cargoRequest()->with('wagons')->first();

                if ($cargoRequest) {
                    $allRequestWagonsSameOwner = $cargoRequest->wagons->isNotEmpty()
                        && $cargoRequest->wagons->every(fn (Wagon $w) => (int) $w->effectiveOwnerId() === (int) $newOwner->id);

                    if ($allRequestWagonsSameOwner) {
                        $repId = $cargoRequest->representative_id;

                        if (! empty($data['representative_id'])) {
                            $repId = (int) $data['representative_id'];
                        } elseif (! DB::table('owner_representative')
                            ->where('owner_id', $newOwner->id)
                            ->where('representative_id', $repId)
                            ->exists()) {
                            $repId = $newOwner->representatives()
                                ->orderByDesc('is_active')
                                ->orderBy('representatives.id')
                                ->value('representatives.id') ?? $repId;
                        }

                        $cargoRequest->update([
                            'owner_id' => $newOwner->id,
                            'representative_id' => $repId,
                        ]);
                    }
                }
            }
        });

        $newOwnerLabel = $newOwner?->name ?? 'ناشناس';
        $message = match ($changeType) {
            'both' => "شماره واگن از «{$oldWagonNumber}» به «{$newWagonNumber}» و صاحب کالا به «{$newOwnerLabel}» با موفقیت ویرایش شد و با ذکر علت در داشبورد مدیر ثبت گردید.",
            'wagon_number' => "شماره واگن از «{$oldWagonNumber}» به «{$newWagonNumber}» با موفقیت اصلاح شد و با ذکر علت در داشبورد مدیر ثبت گردید.",
            default => "صاحب کالای واگن «{$wagon->wagon_number}» با موفقیت به «{$newOwnerLabel}» تغییر یافت و در آمار واگن‌های ویرایش‌شده ثبت شد.",
        };

        if ($request->filled('billing_id') && $billing) {
            return redirect()->route('billing.show', $billing)->with('success', $message);
        }

        if ($billing) {
            return redirect()->route('billing.show', $billing)->with('success', $message);
        }

        if ($wagon->cargoRequest) {
            return redirect()->route('cargo.show', $wagon->cargoRequest)->with('success', $message);
        }

        return redirect()
            ->route(auth()->user()?->isAdmin() ? 'dashboard' : 'cargo.index')
            ->with('success', $message);
    }

    /**
     * افزودن یک یا چند واگن جدید به درخواست بارگیری موجود
     */
    public function addWagon(Request $request, CargoRequest $cargo)
    {
        $normalizeDigits = static fn (string $v): string => strtr($v, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $rawWagons = $request->input('new_wagons');
        if (! is_array($rawWagons)) {
            $rawWagons = [[
                'number' => (string) $request->input('wagon_number', ''),
                'description' => (string) $request->input('cargo_description', ''),
            ]];
        }

        $normalizedWagons = collect($rawWagons)->map(fn ($row) => [
            'number' => is_array($row) ? trim($normalizeDigits((string) ($row['number'] ?? ''))) : '',
            'description' => is_array($row) ? trim((string) ($row['description'] ?? '')) : '',
        ])->values()->all();

        $request->merge(['new_wagons' => $normalizedWagons]);

        $data = $request->validate([
            'new_wagons' => ['required', 'array', 'min:1', 'max:50'],
            'new_wagons.*.number' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Za-z0-9\-\/]+$/',
                'distinct',
            ],
            'new_wagons.*.description' => ['required', 'string', 'max:255'],
        ], [
            'new_wagons.required' => 'حداقل یک واگن باید برای افزودن وارد شود.',
            'new_wagons.*.number.required' => 'شماره واگن الزامی است.',
            'new_wagons.*.number.regex' => 'شماره واگن فقط می‌تواند شامل ارقام، حروف انگلیسی، خط تیره (-) یا ممیز (/) باشد.',
            'new_wagons.*.number.distinct' => 'شماره واگن تکراری در فرم افزودن واگن مجاز نیست.',
            'new_wagons.*.description.required' => 'شرح کالا برای تمام واگن‌ها الزامی است.',
        ], [
            'new_wagons.*.number' => 'شماره واگن',
            'new_wagons.*.description' => 'شرح کالا',
        ]);

        $existingNumbers = $cargo->wagons()->pluck('wagon_number')->all();
        foreach ($data['new_wagons'] as $idx => $row) {
            if (in_array($row['number'], $existingNumbers, true)) {
                throw ValidationException::withMessages([
                    "new_wagons.{$idx}.number" => "شماره واگن «{$row['number']}» قبلاً در همین درخواست بارگیری ثبت شده است.",
                ]);
            }
        }

        DB::transaction(function () use ($cargo, $data) {
            foreach ($data['new_wagons'] as $row) {
                Wagon::create([
                    'cargo_request_id' => $cargo->id,
                    'owner_id' => $cargo->owner_id,
                    'wagon_number' => $row['number'],
                    'cargo_description' => $row['description'],
                ]);
            }

            if ($cargo->status !== CargoRequest::STATUS_PENDING) {
                $cargo->update(['status' => CargoRequest::STATUS_PENDING]);
            }
        });

        $addedCount = count($data['new_wagons']);
        $msg = $addedCount === 1
            ? "واگن «{$data['new_wagons'][0]['number']}» با موفقیت به درخواست بارگیری «{$cargo->request_number}» اضافه شد."
            : "{$addedCount} واگن جدید با موفقیت به درخواست بارگیری «{$cargo->request_number}» اضافه شد.";

        return redirect()
            ->route('cargo.show', $cargo)
            ->with('success', $msg);
    }

    /**
     * حذف واگن از درخواست بارگیری (قبل از صدور صورتحساب)
     */
    public function destroyWagon(Request $request, Wagon $wagon)
    {
        $wagon->load(['cargoRequest.wagons', 'billingItem.billing']);

        if ($wagon->is_settled || $wagon->billingItem !== null) {
            return back()->with('error', "واگن «{$wagon->wagon_number}» دارای صورتحساب صادرشده است و امکان حذف آن وجود ندارد. (در صورت نیاز ابتدا صورتحساب مربوطه را لغو کنید.)");
        }

        $wagonNumber = $wagon->wagon_number;
        $cargoRequest = $wagon->cargoRequest;
        $requestNumber = $cargoRequest?->request_number ?? '';
        $requestDeleted = false;

        DB::transaction(function () use ($wagon, $cargoRequest, &$requestDeleted) {
            $wagon->delete();

            if (! $cargoRequest) {
                return;
            }

            $cargoRequest->load('wagons');

            if ($cargoRequest->wagons->isEmpty()) {
                $cargoRequest->delete();
                $requestDeleted = true;

                return;
            }

            if ($cargoRequest->wagons->every(fn (Wagon $w) => $w->is_settled)) {
                $cargoRequest->update(['status' => CargoRequest::STATUS_BILLED]);
            } elseif ($cargoRequest->allWagonsLoaded()) {
                $cargoRequest->update(['status' => CargoRequest::STATUS_LOADED]);
            } else {
                $cargoRequest->update(['status' => CargoRequest::STATUS_PENDING]);
            }
        });

        if ($requestDeleted || ! $cargoRequest) {
            return redirect()
                ->route('cargo.index')
                ->with('success', "واگن «{$wagonNumber}» حذف شد و از آنجا که تنها واگن درخواست «{$requestNumber}» بود، درخواست خالی نیز حذف گردید.");
        }

        return redirect()
            ->route('cargo.show', $cargoRequest)
            ->with('success', "واگن «{$wagonNumber}» با موفقیت از درخواست بارگیری «{$requestNumber}» حذف شد.");
    }

    /**
     * بررسی الزامی وجود رابطه بین صاحب کالا و نماینده در جدول owner_representative
     */
    private function assertOwnerRepresentativeLinked(?int $ownerId, ?int $representativeId, string $errorField = 'representative_id'): void
    {
        if (! $ownerId || ! $representativeId) {
            return;
        }

        $linked = DB::table('owner_representative')
            ->where('owner_id', $ownerId)
            ->where('representative_id', $representativeId)
            ->exists();

        if (! $linked) {
            $repName = Representative::where('id', $representativeId)->value('name');
            $ownerName = Owner::where('id', $ownerId)->value('name');

            $message = ($repName && $ownerName)
                ? "نماینده «{$repName}» به صاحب کالای «{$ownerName}» تخصیص داده نشده است. لطفاً صاحب کالای مرتبط با این نماینده را انتخاب کنید."
                : 'این نماینده به صاحب کالای انتخاب‌شده تخصیص داده نشده است.';

            throw ValidationException::withMessages([
                $errorField => $message,
            ]);
        }
    }
}
