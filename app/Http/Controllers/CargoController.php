<?php

namespace App\Http\Controllers;

use App\Models\CargoRequest;
use App\Models\Owner;
use App\Models\Representative;
use App\Models\Tariff;
use App\Models\Wagon;
use App\Support\DocumentNumber;
use App\Support\JalaliDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CargoController extends Controller
{
    public function index(Request $request)
    {
        $cargoRequests = CargoRequest::with(['owner', 'representative', 'wagons'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('owner_id'), fn ($q) => $q->where('owner_id', $request->integer('owner_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.trim($request->string('search')).'%';

                $q->where(function ($inner) use ($term) {
                    $inner->where('request_number', 'like', $term)
                        ->orWhereHas('owner', fn ($owner) => $owner->where('name', 'like', $term))
                        ->orWhereHas('wagons', fn ($wagon) => $wagon
                            ->where('wagon_number', 'like', $term)
                            ->orWhere('cargo_description', 'like', $term));
                });
            })
            ->latest('request_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('cargo.index', [
            'cargoRequests' => $cargoRequests,
            'owners' => Owner::orderBy('name')->get(['id', 'name']),
            'statusLabels' => CargoRequest::STATUS_LABELS,
        ]);
    }

    public function create()
    {
        $owners = Owner::active()->orderBy('name')->get();
        $representatives = Representative::active()->with('owners')->orderBy('name')->get();

        return view('cargo.create', [
            'owners' => $owners,
            'representatives' => $representatives,
            'requestDateShamsi' => JalaliDate::today(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'owner_id' => ['required', 'integer', 'exists:owners,id'],
            'representative_id' => ['required', 'integer', 'exists:representatives,id'],
            'request_date_shamsi' => ['required', 'string'],
            'wagons' => ['required', 'array', 'min:1'],
            'wagons.*.number' => ['nullable', 'string', 'max:50'],
            'wagons.*.description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'owner_id' => 'صاحب کالا',
            'representative_id' => 'نماینده',
            'request_date_shamsi' => 'تاریخ درخواست',
            'wagons' => 'واگن‌ها',
            'wagons.*.number' => 'شماره واگن',
            'wagons.*.description' => 'شرح کالا',
        ]);

        // نماینده باید به همین صاحب کالا تخصیص داده شده باشد
        $linked = DB::table('owner_representative')
            ->where('owner_id', $data['owner_id'])
            ->where('representative_id', $data['representative_id'])
            ->exists();

        if (! $linked) {
            throw ValidationException::withMessages([
                'representative_id' => 'این نماینده به صاحب کالای انتخاب‌شده تخصیص داده نشده است.',
            ]);
        }

        $requestDate = JalaliDate::parse($data['request_date_shamsi'], 'request_date_shamsi');
        $normalizedDate = JalaliDate::normalize($data['request_date_shamsi']);

        // فقط ردیف‌هایی که شماره واگن دارند ثبت می‌شوند؛ شرح کالا اختیاری است
        $wagonRows = collect($data['wagons'])
            ->map(fn ($row) => [
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

        $duplicateNumber = $wagonRows->duplicates('number')->first();

        if ($duplicateNumber !== null) {
            throw ValidationException::withMessages([
                'wagons' => "شماره واگن «{$duplicateNumber}» تکراری است.",
            ]);
        }

        $cargoRequest = DocumentNumber::withRetry(function () use ($data, $requestDate, $normalizedDate, $wagonRows) {
            $cargoRequest = CargoRequest::create([
                'request_number' => CargoRequest::generateRequestNumber(),
                'owner_id' => $data['owner_id'],
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
                    'wagon_number' => $wagonRow['number'],
                    'cargo_description' => $wagonRow['description'] !== '' ? $wagonRow['description'] : null,
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
            'owner', 'representative', 'operator',
            'wagons.tariff', 'wagons.billingItem.billing',
        ]);

        $tariffs = Tariff::orderByDesc('is_active')->orderBy('title')->get();

        return view('cargo.show', [
            'cargo' => $cargo,
            'tariffs' => $tariffs,
            'loadingDateShamsi' => JalaliDate::today(),
        ]);
    }

    /**
     * ثبت/ویرایش عملیات بارگیری واگن‌های یک درخواست.
     */
    public function storeLoading(Request $request, CargoRequest $cargo)
    {
        if ($cargo->status === CargoRequest::STATUS_BILLED) {
            return back()->with('warning', 'برای درخواست صورتحساب‌شده امکان تغییر بارگیری وجود ندارد.');
        }

        $data = $request->validate([
            'loading_date_shamsi' => ['required', 'string'],
            'loading' => ['required', 'array'],
            'loading.*.tariff_id' => ['nullable', 'integer', 'exists:tariffs,id'],
            'loading.*.weight' => ['nullable', 'numeric', 'min:0.01', 'max:200'],
            'loading.*.description' => ['nullable', 'string', 'max:255'],
        ], [], [
            'loading_date_shamsi' => 'تاریخ بارگیری',
            'loading' => 'ردیف‌های بارگیری',
            'loading.*.description' => 'شرح کالا',
        ]);

        $loadingDate = JalaliDate::parse($data['loading_date_shamsi'], 'loading_date_shamsi');

        $cargo->load('wagons');

        $tariffs = Tariff::whereIn(
            'id',
            collect($data['loading'])->pluck('tariff_id')->filter()->all()
        )->get()->keyBy('id');

        DB::transaction(function () use ($cargo, $data, $loadingDate, $tariffs) {
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

        return redirect()
            ->route('cargo.show', $cargo)
            ->with('success', 'اطلاعات بارگیری ثبت شد.');
    }
}
