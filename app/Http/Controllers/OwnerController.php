<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerController extends Controller
{
    public function index(Request $request)
    {
        $owners = Owner::query()
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status')->toString() === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('owners.index', compact('owners'));
    }

    public function create()
    {
        $representatives = \App\Models\Representative::active()->orderBy('name')->get();

        return view('owners.create', compact('representatives'));
    }

    public function store(Request $request)
    {
        $validatedData = $this->validated($request);
        $repIds = array_values(array_filter(array_map('intval', (array) ($validatedData['representative_ids'] ?? []))));
        unset($validatedData['representative_ids']);

        $cargoRequestId = $request->integer('cargo_request_id') ?: null;

        $owner = DB::transaction(function () use ($validatedData, $repIds, $cargoRequestId, $request) {
            $owner = Owner::create($validatedData);

            if (! empty($repIds)) {
                $owner->representatives()->syncWithoutDetaching($repIds);
            }

            if ($cargoRequestId && $request->boolean('assign_to_cargo_request', true)) {
                $cargoReq = \App\Models\CargoRequest::lockForUpdate()->find($cargoRequestId);
                if ($cargoReq && ($cargoReq->owner_id === null || $request->boolean('force_assign_owner'))) {
                    if (! $cargoReq->representative_id || in_array((int) $cargoReq->representative_id, $repIds, true)) {
                        $cargoReq->update(['owner_id' => $owner->id]);
                        \App\Models\Wagon::where('cargo_request_id', $cargoReq->id)
                            ->where('is_settled', false)
                            ->whereNull('owner_id')
                            ->update(['owner_id' => $owner->id]);
                    }
                }
            }

            return $owner;
        });

        $redirectTo = (string) $request->input('redirect_to', '');

        if ($redirectTo === 'cargo.create') {
            $redirect = redirect()->route('cargo.create')
                ->with('selected_owner_id', $owner->id)
                ->with('success', "صاحب کالای جدید «{$owner->name}» ثبت شد و در فرم انتخاب گردید.");

            if (count($repIds) === 1) {
                $redirect->with('selected_representative_id', $repIds[0]);
            }

            return $redirect;
        }

        if ($redirectTo === 'cargo.show' && $cargoRequestId) {
            return redirect()->route('cargo.show', $cargoRequestId)
                ->with('selected_owner_id', $owner->id)
                ->with('success', "صاحب کالای جدید «{$owner->name}» ثبت شد و به درخواست بارگیری اختصاص یافت.");
        }

        if ($redirectTo === 'cargo.index') {
            return redirect()->route('cargo.index')
                ->with('selected_owner_id', $owner->id)
                ->with('success', "صاحب کالای جدید «{$owner->name}» ثبت شد و در درخواست بارگیری قرار گرفت.");
        }

        if ($redirectTo === 'billing.create') {
            return redirect()->route('billing.create', array_filter([
                'owner_id' => $owner->id,
                'cargo_request_id' => $cargoRequestId,
                'representative_id' => $repIds[0] ?? null,
            ]))->with('success', "صاحب کالای جدید «{$owner->name}» ثبت شد و برای صدور صورتحساب انتخاب گردید.");
        }

        return redirect()->route('owners.index')->with('success', 'صاحب کالای جدید افزوده شد.');
    }

    public function edit(Owner $owner)
    {
        $representatives = \App\Models\Representative::active()->orderBy('name')->get();
        $owner->load('representatives');

        return view('owners.edit', compact('owner', 'representatives'));
    }

    public function update(Request $request, Owner $owner)
    {
        $validatedData = $this->validated($request, $owner->id);
        $hasRepsField = $request->has('representative_ids') || $request->boolean('sync_representatives');
        $repIds = array_values(array_filter(array_map('intval', (array) ($validatedData['representative_ids'] ?? []))));
        unset($validatedData['representative_ids']);

        DB::transaction(function () use ($owner, $validatedData, $hasRepsField, $repIds) {
            $owner->update($validatedData);

            if ($hasRepsField) {
                $owner->representatives()->sync($repIds);
            }
        });

        return redirect()->route('owners.index')->with('success', 'اطلاعات صاحب کالا بروزرسانی شد.');
    }

    public function toggleActive(Owner $owner)
    {
        $owner->update(['is_active' => ! $owner->is_active]);

        return back()->with('success', $owner->is_active ? 'صاحب کالا فعال شد.' : 'صاحب کالا غیرفعال شد.');
    }

    public function destroy(Owner $owner)
    {
        $hasWagonLinks = \App\Models\Wagon::where('owner_id', $owner->id)
            ->orWhere('previous_owner_id', $owner->id)
            ->exists()
            || \App\Models\WagonOwnerLog::where('old_owner_id', $owner->id)
                ->orWhere('new_owner_id', $owner->id)
                ->exists();

        if ($owner->cargoRequests()->exists() || $owner->billings()->exists() || $hasWagonLinks) {
            return back()->with('warning', 'این صاحب کالا سابقه درخواست یا صورتحساب دارد و حذف نمی‌شود؛ آن را غیرفعال کنید.');
        }

        $owner->delete();

        return redirect()->route('owners.index')->with('success', 'صاحب کالا حذف شد.');
    }

    /** تاریخچه بارگیری و صورتحساب‌های یک صاحب کالا */
    public function history(Owner $owner)
    {
        $cargoRequests = $owner->cargoRequests()
            ->with('wagons', 'representative')
            ->latest('request_date')
            ->latest('id')
            ->paginate(10, ['*'], 'requests')
            ->withQueryString();

        $billings = $owner->billings()
            ->latest('billing_date')
            ->latest('id')
            ->paginate(10, ['*'], 'billings')
            ->withQueryString();

        return view('owners.history', [
            'owner' => $owner,
            'cargoRequests' => $cargoRequests,
            'billings' => $billings,
            'statusLabels' => \App\Models\CargoRequest::STATUS_LABELS,
        ]);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'national_id' => [
                'nullable', 'string', 'max:20',
                \Illuminate\Validation\Rule::unique('owners', 'national_id')->ignore($ignoreId),
            ],
            'economic_code' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'representative_ids' => ['nullable', 'array'],
            'representative_ids.*' => ['integer', 'exists:representatives,id'],
        ], [], [
            'name' => 'نام',
            'national_id' => 'کد ملی',
            'economic_code' => 'کد اقتصادی',
            'phone' => 'تلفن',
            'representative_ids' => 'نمایندگان مرتبط',
        ]);
    }
}
