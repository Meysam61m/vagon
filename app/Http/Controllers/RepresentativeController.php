<?php

namespace App\Http\Controllers;

use App\Models\CargoRequest;
use App\Models\Owner;
use App\Models\Representative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RepresentativeController extends Controller
{
    public function index(Request $request)
    {
        $representatives = Representative::query()
            ->with('owners')
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status')->toString() === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('representatives.index', compact('representatives'));
    }

    public function create()
    {
        $owners = Owner::active()->orderBy('name')->get();

        return view('representatives.create', compact('owners'));
    }

    public function store(Request $request)
    {
        $validatedData = $this->validated($request);
        $ownerIds = array_values(array_filter(array_map('intval', (array) ($validatedData['owner_ids'] ?? []))));

        $representative = DB::transaction(function () use ($validatedData, $ownerIds) {
            $rep = Representative::create([
                'name' => $validatedData['name'],
                'national_id' => $validatedData['national_id'] ?? null,
                'phone' => $validatedData['phone'] ?? null,
            ]);

            $rep->owners()->sync($ownerIds);

            return $rep;
        });

        if ($request->input('redirect_to') === 'cargo.create') {
            $redirect = redirect()->route('cargo.create')
                ->with('selected_representative_id', $representative->id)
                ->with('success', "نماینده جدید «{$representative->name}» ثبت شد و در فرم انتخاب گردید.");

            if (count($ownerIds) === 1) {
                $redirect->with('selected_owner_id', $ownerIds[0]);
            }

            return $redirect;
        }

        return redirect()->route('representatives.index')->with('success', 'نماینده جدید افزوده شد.');
    }

    public function edit(Representative $representative)
    {
        $owners = Owner::active()->orderBy('name')->get();
        $representative->load('owners');

        return view('representatives.edit', compact('representative', 'owners'));
    }

    public function update(Request $request, Representative $representative)
    {
        $validatedData = $this->validated($request, $representative->id);
        $ownerIds = array_values(array_filter(array_map('intval', (array) ($validatedData['owner_ids'] ?? []))));

        DB::transaction(function () use ($representative, $validatedData, $ownerIds) {
            $representative->update([
                'name' => $validatedData['name'],
                'national_id' => $validatedData['national_id'] ?? null,
                'phone' => $validatedData['phone'] ?? null,
            ]);

            $representative->owners()->sync($ownerIds);
        });

        return redirect()->route('representatives.index')->with('success', 'اطلاعات نماینده بروزرسانی شد.');
    }

    public function toggleActive(Representative $representative)
    {
        $representative->update(['is_active' => ! $representative->is_active]);

        return back()->with('success', $representative->is_active ? 'نماینده فعال شد.' : 'نماینده غیرفعال شد.');
    }

    public function destroy(Representative $representative)
    {
        if ($representative->cargoRequests()->exists() || $representative->billings()->exists()) {
            return back()->with('warning', 'این نماینده سابقه درخواست یا صورتحساب دارد و حذف نمی‌شود؛ آن را غیرفعال کنید.');
        }

        $representative->delete();

        return redirect()->route('representatives.index')->with('success', 'نماینده حذف شد.');
    }

    /** تاریخچه فعالیت‌های یک نماینده */
    public function history(Representative $representative)
    {
        $cargoRequests = $representative->cargoRequests()
            ->with('owner')
            ->latest('request_date')
            ->latest('id')
            ->paginate(10, ['*'], 'requests')
            ->withQueryString();

        $billings = $representative->billings()
            ->with('owner')
            ->latest('billing_date')
            ->latest('id')
            ->paginate(10, ['*'], 'billings')
            ->withQueryString();

        return view('representatives.history', [
            'representative' => $representative,
            'cargoRequests' => $cargoRequests,
            'billings' => $billings,
            'statusLabels' => CargoRequest::STATUS_LABELS,
        ]);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'national_id' => [
                'nullable', 'string', 'max:20',
                Rule::unique('representatives', 'national_id')->ignore($ignoreId),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'owner_ids' => ['nullable', 'array'],
            'owner_ids.*' => ['integer', 'exists:owners,id'],
        ], [], [
            'name' => 'نام',
            'national_id' => 'کد ملی',
            'phone' => 'تلفن',
            'owner_ids' => 'صاحبان کالا',
        ]);
    }
}
