<?php

namespace App\Http\Controllers;

use App\Models\TaxSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxController extends Controller
{
    public function edit()
    {
        $current = TaxSetting::current();
        $history = TaxSetting::with('updatedBy')->latest('id')->limit(10)->get();

        return view('tax.edit', compact('current', 'history'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [], [
            'percentage' => 'درصد مالیات',
        ]);

        DB::transaction(function () use ($data) {
            // رکورد قبلی غیرجاری می‌شود و رکورد جدید ثبت می‌شود تا تاریخچه حفظ شود
            TaxSetting::where('is_current', true)->lockForUpdate()->update(['is_current' => false]);

            TaxSetting::create([
                'percentage' => $data['percentage'],
                'is_current' => true,
                'updated_by' => auth()->id(),
            ]);
        });

        return back()->with('success', 'درصد مالیات با موفقیت بروزرسانی شد.');
    }
}
