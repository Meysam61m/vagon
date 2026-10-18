<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use Illuminate\Http\Request;

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
        return view('owners.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Owner::create($data);

        return redirect()->route('owners.index')->with('success', 'صاحب کالای جدید افزوده شد.');
    }

    public function edit(Owner $owner)
    {
        return view('owners.edit', compact('owner'));
    }

    public function update(Request $request, Owner $owner)
    {
        $owner->update($this->validated($request, $owner->id));

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
        ], [], [
            'name' => 'نام',
            'national_id' => 'کد ملی',
            'economic_code' => 'کد اقتصادی',
            'phone' => 'تلفن',
        ]);
    }
}
