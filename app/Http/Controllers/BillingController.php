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
use App\Support\DocumentNumber;
use App\Support\JalaliDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $billings = Billing::with('owner', 'representative', 'operator', 'items.wagon.owner')
            ->when($request->filled('owner_id'), function ($q) use ($request) {
                $ownerId = $request->integer('owner_id');

                $q->where(function ($sub) use ($ownerId) {
                    $sub->where('owner_id', $ownerId)
                        ->orWhereHas('items.wagon', fn ($w) => $w->where('owner_id', $ownerId));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.trim($request->string('search')->toString()).'%';

                $q->where(function ($inner) use ($term) {
                    $inner->where('billing_number', 'like', $term)
                        ->orWhere('owner_name', 'like', $term)
                        ->orWhereHas('items.wagon', fn ($w) => $w
                            ->where('wagon_number', 'like', $term)
                            ->orWhere('previous_wagon_number', 'like', $term)
                            ->orWhereHas('owner', fn ($wo) => $wo->where('name', 'like', $term)));
                });
            })
            ->latest('billing_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('billing.index', [
            'billings' => $billings,
            'owners' => Owner::orderBy('name')->get(['id', 'name']),
            'statusLabels' => Billing::STATUS_LABELS,
        ]);
    }

    public function create(Request $request)
    {
        $owners = Owner::active()->orderBy('name')->get();
        $ownerId = $request->integer('owner_id') ?: null;
        $selectedCargoRequestId = $request->integer('cargo_request_id') ?: null;
        $defaultRepresentativeId = $request->integer('representative_id') ?: null;

        if ($selectedCargoRequestId && ! $ownerId) {
            $cr = CargoRequest::find($selectedCargoRequestId);
            if ($cr) {
                $ownerId = (int) $cr->owner_id;
                $defaultRepresentativeId = $defaultRepresentativeId ?: (int) $cr->representative_id;
            }
        }

        // شمارش واگن‌های بارگیری‌شده و تسویه‌نشده هر صاحب کالا برای دسترسی سریع اپراتور
        $pendingWagonsForCounts = Wagon::query()
            ->pendingSettlement()
            ->loaded()
            ->with('cargoRequest:id,owner_id')
            ->get(['id', 'owner_id', 'cargo_request_id']);

        $readyCountsByOwner = $pendingWagonsForCounts
            ->groupBy(fn (Wagon $w) => (int) $w->effectiveOwnerId())
            ->map(fn ($group) => $group->count());

        $wagons = collect();
        $representatives = collect();

        if ($ownerId) {
            $wagons = Wagon::query()
                ->pendingSettlement()
                ->loaded()
                ->where(function ($q) use ($ownerId) {
                    $q->where('owner_id', $ownerId)
                        ->orWhere(function ($sub) use ($ownerId) {
                            $sub->whereNull('owner_id')
                                ->whereHas('cargoRequest', fn ($cr) => $cr->where('owner_id', $ownerId));
                        });
                })
                ->with(['cargoRequest.representative', 'owner', 'tariff'])
                ->orderBy('wagon_number')
                ->get();

            $representatives = Representative::active()
                ->whereHas('owners', fn ($q) => $q->where('owners.id', $ownerId))
                ->orderBy('name')
                ->get();

            if (! $defaultRepresentativeId) {
                $repIdsFromWagons = $wagons
                    ->pluck('cargoRequest.representative_id')
                    ->filter()
                    ->unique();

                if ($repIdsFromWagons->count() === 1) {
                    $defaultRepresentativeId = (int) $repIdsFromWagons->first();
                } elseif ($representatives->count() === 1) {
                    $defaultRepresentativeId = (int) $representatives->first()->id;
                }
            }
        }

        return view('billing.create', [
            'owners' => $owners,
            'readyCountsByOwner' => $readyCountsByOwner,
            'ownerId' => $ownerId,
            'selectedCargoRequestId' => $selectedCargoRequestId,
            'defaultRepresentativeId' => $defaultRepresentativeId,
            'wagons' => $wagons,
            'tariffs' => Tariff::active()->orderBy('title')->get(),
            'taxPercentage' => TaxSetting::currentPercentage(),
            'representatives' => $representatives,
            'billingDateShamsi' => JalaliDate::today(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'owner_id' => ['required', 'integer', 'exists:owners,id'],
            'representative_id' => ['nullable', 'integer', 'exists:representatives,id'],
            'billing_date_shamsi' => ['required', 'string'],
            'wagons' => ['required', 'array', 'min:1'],
            'wagons.*.id' => ['required', 'integer', 'distinct', 'exists:wagons,id'],
            'wagons.*.tariff_id' => ['required', 'integer', 'exists:tariffs,id'],
            'wagons.*.weight' => ['required', 'numeric', 'min:0.01', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'owner_id' => 'صاحب کالا',
            'billing_date_shamsi' => 'تاریخ صدور',
            'wagons' => 'واگن‌ها',
        ]);

        if (! empty($data['representative_id'])) {
            $linked = DB::table('owner_representative')
                ->where('owner_id', $data['owner_id'])
                ->where('representative_id', $data['representative_id'])
                ->exists();

            if (! $linked) {
                throw ValidationException::withMessages([
                    'representative_id' => 'این نماینده به صاحب کالای انتخاب‌شده تخصیص داده نشده است.',
                ]);
            }
        }

        $billingDate = JalaliDate::parse($data['billing_date_shamsi'], 'billing_date_shamsi');
        $billingDateShamsi = JalaliDate::normalize($data['billing_date_shamsi']);

        $billing = DocumentNumber::withRetry(function () use ($data, $billingDate, $billingDateShamsi) {
            $taxPercentage = TaxSetting::currentPercentage();
            $owner = Owner::findOrFail($data['owner_id']);
            $representativeId = $data['representative_id'] ?? null;
            $representative = $representativeId ? Representative::find($representativeId) : null;

            $rows = collect($data['wagons']);
            $ids = $rows->pluck('id')->all();

            // قفل ردیف‌های واگن تا دو درخواست هم‌زمان نتوانند یک واگن را دوبار فاکتور کنند
            /** @var \Illuminate\Support\Collection<int, Wagon> $wagons */
            $wagons = Wagon::with('cargoRequest')
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($wagons->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'wagons' => 'برخی از واگن‌های انتخاب‌شده وجود ندارند.',
                ]);
            }

            $tariffs = Tariff::whereIn('id', $rows->pluck('tariff_id')->unique()->all())
                ->get()
                ->keyBy('id');

            $items = [];
            $baseAmount = 0;
            $taxAmount = 0;

            foreach ($rows as $row) {
                /** @var Wagon $wagon */
                $wagon = $wagons->get($row['id']);

                if ($wagon->is_settled) {
                    throw ValidationException::withMessages([
                        'wagons' => "واگن {$wagon->wagon_number} قبلاً تسویه/صورتحساب شده است.",
                    ]);
                }

                if ((int) $wagon->effectiveOwnerId() !== (int) $owner->id) {
                    throw ValidationException::withMessages([
                        'wagons' => "واگن {$wagon->wagon_number} متعلق به صاحب کالای انتخاب‌شده نیست.",
                    ]);
                }

                if (! $wagon->isLoaded()) {
                    throw ValidationException::withMessages([
                        'wagons' => "برای واگن {$wagon->wagon_number} هنوز وزن بارگیری ثبت نشده است.",
                    ]);
                }

                /** @var Tariff|null $tariff */
                $tariff = $tariffs->get($row['tariff_id']);

                if (! $tariff) {
                    throw ValidationException::withMessages([
                        'wagons' => 'تعرفه انتخاب‌شده معتبر نیست.',
                    ]);
                }

                $weight = (float) $row['weight'];

                if ($weight > (float) $tariff->capacity) {
                    throw ValidationException::withMessages([
                        'wagons' => "وزن واگن {$wagon->wagon_number} از ظرفیت تعرفه ({$tariff->capacity} تن) بیشتر است.",
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
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $billing->items()->create($item);
            }

            Wagon::whereIn('id', array_column($items, 'wagon_id'))->update(['is_settled' => true]);

            // به‌روزرسانی وضعیت درخواست‌های بارگیری متأثر
            $cargoIds = $wagons->pluck('cargoRequest.id')->unique()->filter();

            foreach ($cargoIds as $cargoId) {
                $cargo = CargoRequest::with('wagons')->find($cargoId);

                if (! $cargo) {
                    continue;
                }

                if ($cargo->wagons->every(fn (Wagon $wagon) => $wagon->is_settled)) {
                    $cargo->update(['status' => CargoRequest::STATUS_BILLED]);
                } elseif ($cargo->allWagonsLoaded()) {
                    $cargo->update(['status' => CargoRequest::STATUS_LOADED]);
                }
            }

            return $billing;
        });

        return redirect()
            ->route('billing.show', $billing)
            ->with('success', 'صورتحساب با موفقیت صادر شد.');
    }

    public function show(Billing $billing)
    {
        $billing->load([
            'items.wagon.owner',
            'items.wagon.previousOwner',
            'items.wagon.ownerChangedBy',
            'items.wagon.cargoRequest',
            'items.tariff',
            'representative',
        ]);

        return view('billing.show', ['billing' => $billing]);
    }

    public function print(Billing $billing)
    {
        $billing->load([
            'items.wagon.owner',
            'items.wagon.previousOwner',
            'items.wagon.cargoRequest',
            'representative',
            'operator',
        ]);

        return view('billing.print', ['billing' => $billing]);
    }

    /**
     * لغو صورتحساب: ردیف‌ها حذف و واگن‌ها آزاد می‌شوند تا امکان صدور مجدد فراهم باشد.
     */
    public function cancel(Billing $billing)
    {
        if ($billing->isCancelled()) {
            return back()->with('warning', 'این صورتحساب قبلاً لغو شده است.');
        }

        DB::transaction(function () use ($billing) {
            $billing->load('items.wagon');

            $wagonIds = $billing->items->pluck('wagon_id')->unique()->values();
            $cargoIds = $billing->items->pluck('wagon.cargo_request_id')->unique()->filter();

            $billing->items()->delete();

            Wagon::whereIn('id', $wagonIds)->update(['is_settled' => false]);

            $billing->update([
                'status' => Billing::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            foreach ($cargoIds as $cargoId) {
                $cargo = CargoRequest::with('wagons')->find($cargoId);

                if (! $cargo) {
                    continue;
                }

                $cargo->update([
                    'status' => $cargo->allWagonsLoaded()
                        ? CargoRequest::STATUS_LOADED
                        : CargoRequest::STATUS_PENDING,
                ]);
            }
        });

        return redirect()
            ->route('billing.show', $billing)
            ->with('success', 'صورتحساب لغو شد و واگن‌ها برای صدور مجدد آزاد شدند.');
    }
}
