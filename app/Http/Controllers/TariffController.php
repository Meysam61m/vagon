<?php

namespace App\Http\Controllers;

use App\Models\Tariff;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TariffController extends Controller
{
    private const EQUIPMENT_TYPES = ['crane', 'labor', 'forklift'];

    public function index(Request $request)
    {
        $tariffs = Tariff::query()
            ->search($request->string('search')->toString())
            ->when($request->filled('equipment_type'), fn ($q) => $q->where('equipment_type', $request->string('equipment_type')->toString()))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status')->toString() === 'active'))
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('tariffs.index', compact('tariffs'));
    }

    public function create()
    {
        return view('tariffs.create', ['equipmentLabels' => Tariff::EQUIPMENT_LABELS]);
    }

    public function store(Request $request)
    {
        Tariff::create($this->validated($request));

        return redirect()->route('tariffs.index')->with('success', 'تعرفه جدید افزوده شد.');
    }

    public function edit(Tariff $tariff)
    {
        return view('tariffs.edit', [
            'tariff' => $tariff,
            'equipmentLabels' => Tariff::EQUIPMENT_LABELS,
        ]);
    }

    public function update(Request $request, Tariff $tariff)
    {
        $tariff->update($this->validated($request, $tariff->id));

        return redirect()->route('tariffs.index')->with('success', 'تعرفه بروزرسانی شد.');
    }

    public function toggleActive(Tariff $tariff)
    {
        $tariff->update(['is_active' => ! $tariff->is_active]);

        return back()->with('success', $tariff->is_active ? 'تعرفه فعال شد.' : 'تعرفه غیرفعال شد.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('tariffs', 'code')->ignore($ignoreId)],
            'rate' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'integer', Rule::in(array_keys(Tariff::CATEGORY_LABELS))],
            'capacity' => ['required', 'numeric', 'min:0.1', 'max:200'],
            'equipment_type' => ['required', Rule::in(self::EQUIPMENT_TYPES)],
        ], [], [
            'code' => 'کد تعرفه',
            'rate' => 'نرخ',
            'unit' => 'واحد',
            'title' => 'عنوان',
            'category' => 'دسته',
            'capacity' => 'ظرفیت',
            'equipment_type' => 'نوع دستگاه',
        ]);
    }
}
