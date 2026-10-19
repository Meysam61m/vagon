<?php

namespace App\Http\Controllers;

use App\Models\Tariff;
use App\Models\TaxSetting;
use App\Support\ExcelExport;
use App\Support\ExcelReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class TariffController extends Controller
{
    private const EQUIPMENT_TYPES = ['crane', 'labor', 'forklift'];

    public function index(Request $request)
    {
        $baseQuery = Tariff::query()
            ->search($request->string('search')->toString())
            ->when($request->filled('equipment_type'), fn ($q) => $q->where('equipment_type', $request->string('equipment_type')->toString()))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status')->toString() === 'active'))
            ->orderBy('code');

        if (in_array(strtolower((string) $request->query('export', '')), ['excel', 'xlsx', '1'], true)) {
            $allTariffs = $baseQuery->get();
            $taxPct = TaxSetting::currentPercentage();
            $rows = [];

            foreach ($allTariffs as $idx => $t) {
                $rateWithTax = (int) round($t->rate * (1 + ($taxPct / 100)));
                $rows[] = [
                    $idx + 1,
                    $t->code,
                    $t->title,
                    $t->unit,
                    (float) $t->capacity,
                    (int) $t->rate,
                    $rateWithTax,
                    $t->categoryLabel(),
                    $t->equipmentLabel(),
                    $t->is_active ? 'فعال' : 'غیرفعال',
                ];
            }

            return ExcelExport::download(
                'tarefe.xlsx',
                'فهرست تعرفه‌های بارگیری و تخلیه واگن',
                [
                    'ردیف',
                    'کد تعرفه',
                    'عنوان تعرفه / کالا',
                    'واحد',
                    'ظرفیت واگن (تن)',
                    'مبلغ تعرفه در کتابچه (ریال)',
                    'مبلغ با احتساب مالیات (ریال)',
                    'دسته عملیاتی',
                    'نوع دستگاه (وسیله حمل)',
                    'وضعیت',
                ],
                $rows
            );
        }

        $tariffs = $baseQuery->paginate(20)->withQueryString();
        $taxPercentage = TaxSetting::currentPercentage();

        return view('tariffs.index', compact('tariffs', 'taxPercentage'));
    }

    public function create()
    {
        return view('tariffs.create', [
            'equipmentLabels' => Tariff::EQUIPMENT_LABELS,
            'taxPercentage' => TaxSetting::currentPercentage(),
        ]);
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
            'taxPercentage' => TaxSetting::currentPercentage(),
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

    public function destroy(Tariff $tariff)
    {
        $title = $tariff->title;
        $code = $tariff->code;

        DB::transaction(function () use ($tariff) {
            DB::table('wagons')->where('tariff_id', $tariff->id)->update(['tariff_id' => null]);
            DB::table('billing_items')->where('tariff_id', $tariff->id)->update(['tariff_id' => null]);
            $tariff->delete();
        });

        return redirect()
            ->route('tariffs.index')
            ->with('success', "تعرفه «{$title}» ({$code}) با موفقیت حذف شد.");
    }

    /**
     * بارگذاری و استخراج هوشمند تعرفه‌ها از فایل اکسل (tarefe.xlsx)
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'tariff_file' => ['required', 'file', 'max:10240'],
        ], [
            'tariff_file.required' => 'لطفاً فایل اکسل تعرفه (tarefe.xlsx) را انتخاب کنید.',
        ]);

        $file = $request->file('tariff_file');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($ext, ['xlsx', 'zip'], true)) {
            return back()->withErrors(['tariff_file' => 'لطفاً فایل با فرمت اکسل استاندارد (.xlsx) انتخاب کنید.']);
        }

        try {
            $sheets = ExcelReader::readSheets($file->getRealPath());
        } catch (Throwable $e) {
            return back()->withErrors(['tariff_file' => 'خطا در خواندن فایل اکسل: '.$e->getMessage()]);
        }

        $importedCount = 0;

        DB::transaction(function () use ($sheets, &$importedCount) {
            foreach ($sheets as $sheet) {
                $rows = $sheet['rows'] ?? [];
                if (empty($rows)) {
                    continue;
                }

                $colMap = $this->detectHeaderMap($rows);
                $headerRowIdx = $colMap['_header_row'] ?? -1;

                foreach ($rows as $rIdx => $row) {
                    if ($rIdx <= $headerRowIdx) {
                        continue;
                    }

                    $parsed = $this->parseTariffRow($row, $colMap, $importedCount + 1);
                    if ($parsed === null) {
                        continue;
                    }

                    Tariff::updateOrCreate(
                        ['code' => $parsed['code']],
                        [
                            'title' => $parsed['title'],
                            'unit' => $parsed['unit'],
                            'rate' => $parsed['rate'],
                            'capacity' => $parsed['capacity'],
                            'category' => $parsed['category'],
                            'equipment_type' => $parsed['equipment_type'],
                            'is_active' => true,
                        ]
                    );

                    $importedCount++;
                }
            }
        });

        if ($importedCount === 0) {
            return back()->with('warning', 'هیچ ردیف تعرفه معتبری در فایل اکسل یافت نشد. لطفاً سرستون‌های فایل را بررسی کنید.');
        }

        return redirect()
            ->route('tariffs.index')
            ->with('success', "{$importedCount} ردیف تعرفه از فایل اکسل با موفقیت استخراج و در سامانه ثبت/بروزرسانی شد.");
    }

    /**
     * تشخیص خودکار ستون‌های فایل اکسل تعرفه بر اساس عنوان سرستون‌ها
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, mixed>
     */
    private function detectHeaderMap(array $rows): array
    {
        $map = [
            '_header_row' => -1,
            'code' => null,
            'title' => null,
            'rate' => null,
            'rate_with_vat' => null,
            'capacity' => null,
            'unit' => null,
            'equipment' => null,
            'category' => null,
        ];

        foreach (array_slice($rows, 0, 10, true) as $rIdx => $row) {
            $matchedCols = 0;
            $candidate = $map;
            $candidate['_header_row'] = $rIdx;

            foreach ($row as $col => $text) {
                $norm = str_replace(['ي', 'ك', "\u{200c}"], ['ی', 'ک', ' '], trim($text));

                if ($candidate['code'] === null && (str_contains($norm, 'کد تعرفه') || $norm === 'کد' || strtolower($norm) === 'code')) {
                    $candidate['code'] = $col;
                    $matchedCols++;
                } elseif ($candidate['title'] === null && (
                    str_contains($norm, 'عنوان')
                    || str_contains($norm, 'شرح تعرفه')
                    || str_contains($norm, 'شرح کالا')
                    || str_contains($norm, 'نام کالا')
                    || str_contains($norm, 'نوع کالا')
                    || $norm === 'تعرفه'
                    || $norm === 'شرح'
                )) {
                    $candidate['title'] = $col;
                    $matchedCols++;
                } elseif ($candidate['rate'] === null && (
                    str_contains($norm, 'مبلغ تعرفه در کتابچه')
                    || str_contains($norm, 'نرخ کامل')
                    || str_contains($norm, 'نرخ تعرفه')
                    || str_contains($norm, 'مبلغ پایه')
                    || str_contains($norm, 'بدون مالیات')
                    || $norm === 'نرخ'
                    || $norm === 'نرخ (ریال)'
                    || $norm === 'مبلغ'
                    || $norm === 'فی'
                )) {
                    $candidate['rate'] = $col;
                    $matchedCols++;
                } elseif ($candidate['rate_with_vat'] === null && (
                    str_contains($norm, 'با احتساب مالیات')
                    || str_contains($norm, 'با ارزش افزوده')
                    || str_contains($norm, 'مبلغ کل')
                )) {
                    $candidate['rate_with_vat'] = $col;
                    $matchedCols++;
                } elseif ($candidate['capacity'] === null && (
                    str_contains($norm, 'ظرفیت')
                    || str_contains($norm, 'تناژ')
                )) {
                    $candidate['capacity'] = $col;
                    $matchedCols++;
                } elseif ($candidate['equipment'] === null && (
                    str_contains($norm, 'دستگاه')
                    || str_contains($norm, 'وسیله حمل')
                    || str_contains($norm, 'تجهیزات')
                    || str_contains($norm, 'نحوه بارگیری')
                )) {
                    $candidate['equipment'] = $col;
                    $matchedCols++;
                } elseif ($candidate['unit'] === null && str_contains($norm, 'واحد')) {
                    $candidate['unit'] = $col;
                } elseif ($candidate['category'] === null && str_contains($norm, 'دسته')) {
                    $candidate['category'] = $col;
                }
            }

            if ($matchedCols >= 2) {
                return $candidate;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, mixed>  $colMap
     * @return array<string, mixed>|null
     */
    private function parseTariffRow(array $row, array $colMap, int $seq): ?array
    {
        $toDigits = fn (string $v) => strtr($v, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            ',' => '', '٬' => '', ' ' => '',
        ]);

        $titleCol = $colMap['title'] ?? 'B';
        $title = trim((string) ($row[$titleCol] ?? ''));

        // اگر در ستون پیش‌فرض عنوان نبود، اولین ستون متنی غیرعددی را به عنوان نام تعرفه برمی‌داریم
        if ($title === '' || is_numeric($toDigits($title))) {
            foreach ($row as $col => $val) {
                $clean = trim($val);
                if ($clean !== '' && ! is_numeric($toDigits($clean)) && ! in_array($clean, ['جرثقیل', 'لیفتراک', 'کارگری', 'فعال', 'غیرفعال', 'طبق قرارداد'], true)) {
                    $title = $clean;
                    break;
                }
            }
        }

        if ($title === '' || str_contains($title, 'امضاء') || str_contains($title, 'جمع کل')) {
            return null;
        }

        // استخراج نرخ
        $rate = 0;
        if ($colMap['rate'] && isset($row[$colMap['rate']])) {
            $num = $toDigits($row[$colMap['rate']]);
            if (is_numeric($num)) {
                $rate = (int) round((float) $num);
            }
        }

        if ($rate <= 0 && $colMap['rate_with_vat'] && isset($row[$colMap['rate_with_vat']])) {
            $num = $toDigits($row[$colMap['rate_with_vat']]);
            if (is_numeric($num)) {
                $rate = (int) round(((float) $num) / 1.1);
            }
        }

        // اگر هنوز نرخ پیدا نشده، بزرگ‌ترین عدد ردیف (بیشتر از ۱۰۰ هزار ریال) را به عنوان نرخ در نظر می‌گیریم
        if ($rate <= 0) {
            foreach ($row as $val) {
                $num = $toDigits($val);
                if (is_numeric($num) && (float) $num >= 100_000) {
                    $rate = (int) round((float) $num);
                    break;
                }
            }
        }

        if ($rate <= 0) {
            return null;
        }

        // تشخیص نوع دستگاه (جرثقیل / لیفتراک / کارگری)
        $equipmentRaw = $colMap['equipment'] ? ($row[$colMap['equipment']] ?? '') : implode(' ', $row);
        $equipmentType = 'crane';
        if (str_contains($equipmentRaw, 'لیفتراک') || stripos($equipmentRaw, 'forklift') !== false) {
            $equipmentType = 'forklift';
        } elseif (str_contains($equipmentRaw, 'کارگر') || stripos($equipmentRaw, 'labor') !== false) {
            $equipmentType = 'labor';
        }

        // ظرفیت
        $capacity = 65.0;
        if ($colMap['capacity'] && isset($row[$colMap['capacity']])) {
            $capNum = $toDigits($row[$colMap['capacity']]);
            if (is_numeric($capNum) && (float) $capNum >= 1 && (float) $capNum <= 200) {
                $capacity = (float) $capNum;
            }
        } elseif (preg_match('/(\d{2,3})\s*تن/u', $title.' '.($row[$colMap['unit'] ?? ''] ?? ''), $m)) {
            $capVal = (float) $toDigits($m[1]);
            if ($capVal >= 10 && $capVal <= 200) {
                $capacity = $capVal;
            }
        }

        $equipLabel = Tariff::EQUIPMENT_LABELS[$equipmentType] ?? 'جرثقیل';
        if (! str_contains($title, $equipLabel)) {
            $fullTitle = "{$title} ({$equipLabel})";
        } else {
            $fullTitle = $title;
        }

        $code = $colMap['code'] && ! empty($row[$colMap['code']])
            ? substr(trim($row[$colMap['code']]), 0, 20)
            : 'T-'.substr(strtoupper(md5($fullTitle.'-'.$equipmentType.'-'.$rate)), 0, 8);

        $capDisplay = floor($capacity) == $capacity ? (int) $capacity : $capacity;
        $unit = ($colMap['unit'] && ! empty($row[$colMap['unit']]))
            ? trim($row[$colMap['unit']])
            : "واگن - {$capDisplay} تن";

        return [
            'code' => $code,
            'title' => $fullTitle,
            'unit' => $unit,
            'rate' => $rate,
            'capacity' => $capacity,
            'category' => $equipmentType === 'labor' ? 2 : 1,
            'equipment_type' => $equipmentType,
        ];
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
