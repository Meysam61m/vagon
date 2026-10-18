<?php

namespace App\Http\Controllers;

use App\Models\CargoRequest;
use App\Models\Owner;
use App\Models\Representative;
use App\Models\Tariff;
use App\Models\Wagon;
use App\Models\WagonOwnerLog;
use App\Support\DocumentNumber;
use App\Support\JalaliDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CargoController extends Controller
{
    public function index(Request $request)
    {
        $cargoRequests = CargoRequest::with(['owner', 'representative', 'wagons.owner'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('owner_id'), function ($q) use ($request) {
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
                    'owner_id' => $data['owner_id'],
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
            'owner', 'representative', 'operator',
            'wagons.owner', 'wagons.previousOwner', 'wagons.ownerChangedBy',
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

        if (! $request->filled('owner_id') && $wagon->effectiveOwnerId()) {
            $request->merge(['owner_id' => $wagon->effectiveOwnerId()]);
        }

        $data = $request->validate([
            'wagon_number' => ['required', 'string', 'max:50'],
            'owner_id' => ['required', 'integer', 'exists:owners,id'],
            'representative_id' => ['nullable', 'integer', 'exists:representatives,id'],
            'owner_change_note' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'wagon_number.required' => 'وارد کردن شماره واگن الزامی است.',
            'owner_id.required' => 'انتخاب صاحب کالا الزامی است.',
            'owner_change_note.required' => 'ثبت علت و توضیح برای ویرایش واگن الزامی است.',
            'owner_change_note.min' => 'علت و توضیح ویرایش واگن باید حداقل ۳ کاراکتر باشد.',
        ], [
            'wagon_number' => 'شماره واگن',
            'owner_id' => 'صاحب کالا',
            'representative_id' => 'نماینده',
            'owner_change_note' => 'علت و توضیحات ویرایش',
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

        $oldWagonNumber = trim((string) $wagon->wagon_number);
        $newWagonNumber = trim((string) $data['wagon_number']);

        if ($newWagonNumber === '') {
            throw ValidationException::withMessages([
                'wagon_number' => 'وارد کردن شماره واگن معتبر الزامی است.',
            ]);
        }

        $oldOwnerId = $wagon->effectiveOwnerId();
        $oldOwner = $wagon->owner ?? $wagon->cargoRequest?->owner ?? ($oldOwnerId ? Owner::find($oldOwnerId) : null);
        $newOwner = Owner::findOrFail($data['owner_id']);

        $numberChanged = $newWagonNumber !== $oldWagonNumber;
        $ownerChanged = (int) $oldOwnerId !== (int) $newOwner->id;

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
                $updatePayload['owner_id'] = $newOwner->id;
            }

            $wagon->update($updatePayload);

            if ($oldOwnerId) {
                WagonOwnerLog::create([
                    'wagon_id' => $wagon->id,
                    'billing_id' => $billing?->id,
                    'change_type' => $changeType,
                    'old_wagon_number' => $oldWagonNumber,
                    'new_wagon_number' => $newWagonNumber,
                    'old_owner_id' => $oldOwnerId,
                    'new_owner_id' => $newOwner->id,
                    'old_owner_name' => $oldOwner?->name ?? '—',
                    'new_owner_name' => $newOwner->name,
                    'user_id' => auth()->id(),
                    'note' => $note,
                ]);
            }

            if ($ownerChanged) {
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

        $message = match ($changeType) {
            'both' => "شماره واگن از «{$oldWagonNumber}» به «{$newWagonNumber}» و صاحب کالا به «{$newOwner->name}» با موفقیت ویرایش شد و با ذکر علت در داشبورد مدیر ثبت گردید.",
            'wagon_number' => "شماره واگن از «{$oldWagonNumber}» به «{$newWagonNumber}» با موفقیت اصلاح شد و با ذکر علت در داشبورد مدیر ثبت گردید.",
            default => "صاحب کالای واگن «{$wagon->wagon_number}» با موفقیت به «{$newOwner->name}» تغییر یافت و در آمار واگن‌های ویرایش‌شده ثبت شد.",
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

        return redirect()->route('dashboard')->with('success', $message);
    }
}
