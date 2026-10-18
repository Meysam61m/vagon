<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Response;

/**
 * تولیدکننده بومی فایل اکسل استاندارد (.xlsx - Office Open XML) بدون وابستگی به پکیج خارجی یا افزونه خاص PHP.
 * فایل خروجی کاملاً راست‌به‌چپ (RTL)، دارای هدر رنگی، ردیف جمع کل، قالب‌بندی اعداد و عرض خودکار ستون‌هاست.
 */
class ExcelExport
{
    /**
     * ساخت و ارسال پاسخ دانلود فایل `.xlsx`
     *
     * @param  string  $filename  نام فایل (بدون یا با پسوند .xlsx)
     * @param  string  $sheetTitle  عنوان شیت و بنر بالای جدول
     * @param  array<int, string>  $headers  عناوین ستون‌ها
     * @param  iterable<int, array<int, mixed>>  $rows  ردیف‌های داده
     * @param  array<int, mixed>|null  $TotalsRow  ردیف جمع کل در انتهای جدول (اختیاری)
     */
    public static function download(
        string $filename,
        string $sheetTitle,
        array $headers,
        iterable $rows,
        ?array $totalsRow = null
    ): Response {
        if (! str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }

        $xlsxBinary = self::buildXlsx($sheetTitle, array_values($headers), $rows, $totalsRow);

        $safeAsciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'report.xlsx';
        $encodedName = rawurlencode($filename);

        return new Response($xlsxBinary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$safeAsciiName}\"; filename*=UTF-8''{$encodedName}",
            'Content-Length' => (string) strlen($xlsxBinary),
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * ساخت و ارسال فایل اکسل «گزارش ستاد» دقیقاً مطابق قالب و فرمول‌های فایل نمونه
     * (لیست واگن‌های فاز ۲ شهید مطهری با ستون‌های B تا M، ستون سبز مبلغ تعرفه کتابچه و امضاهای انتهای صفحه).
     *
     * @param  string  $filename
     * @param  string  $fromShamsi
     * @param  string  $toShamsi
     * @param  iterable<int, array<string, mixed>>  $itemsData
     */
    public static function downloadSetadReport(
        string $filename,
        string $fromShamsi,
        string $toShamsi,
        iterable $itemsData
    ): Response {
        if (! str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }

        $xlsxBinary = self::buildSetadXlsx($fromShamsi, $toShamsi, $itemsData);

        $safeAsciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'setad-report.xlsx';
        $encodedName = rawurlencode($filename);

        return new Response($xlsxBinary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$safeAsciiName}\"; filename*=UTF-8''{$encodedName}",
            'Content-Length' => (string) strlen($xlsxBinary),
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $itemsData
     */
    public static function buildSetadXlsx(string $fromShamsi, string $toShamsi, iterable $itemsData): string
    {
        $title = "لیست واگن های فاز 2 شهید مطهری در بازه زمانی {$fromShamsi} لغایت {$toShamsi}";

        $files = [
            '[Content_Types].xml' => self::contentTypesXml(),
            '_rels/.rels' => self::rootRelsXml(),
            'xl/_rels/workbook.xml.rels' => self::workbookRelsXml(),
            'xl/workbook.xml' => self::workbookXml('Sheet1'),
            'xl/styles.xml' => self::setadStylesXml(),
            'xl/worksheets/sheet1.xml' => self::setadSheetXml($title, $itemsData),
        ];

        return self::buildZipArchive($files);
    }

    private static function setadStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="2">'
            .'<numFmt numFmtId="164" formatCode="_ * #,##0.00_-_ ;_ * #,##0.00\-_ ;_ * &quot;-&quot;??_-_ ;_ @_ "/>'
            .'<numFmt numFmtId="165" formatCode="_ * #,##0_-_ ;_ * #,##0\-_ ;_ * &quot;-&quot;??_-_ ;_ @_ "/>'
            .'</numFmts>'
            .'<fonts count="3">'
            // 0: Arial 11
            .'<font><sz val="11"/><color rgb="FF000000"/><name val="Arial"/><family val="2"/></font>'
            // 1: B Nazanin 11 Bold
            .'<font><b/><sz val="11"/><color rgb="FF000000"/><name val="B Nazanin"/><charset val="178"/></font>'
            // 2: B Nazanin 12 Bold (Title)
            .'<font><b/><sz val="12"/><color rgb="FF000000"/><name val="B Nazanin"/><charset val="178"/></font>'
            .'</fonts>'
            .'<fills count="4">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF92D050"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="3">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border>'
            .'<left style="thin"><color rgb="FF000000"/></left>'
            .'<right style="thin"><color rgb="FF000000"/></right>'
            .'<top style="thin"><color rgb="FF000000"/></top>'
            .'<bottom style="thin"><color rgb="FF000000"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'<border>'
            .'<left/><right/><top/><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="8">'
            // 0: پیش‌فرض خالی
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            // 1: عنوان بالای جدول (B1:M1) - B Nazanin 12 Bold با خط زیرین
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" readingOrder="2" wrapText="1"/>'
            .'</xf>'
            // 2: سرستون‌ها (B2:M2) - B Nazanin 11 Bold با کادر کامل
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" readingOrder="2" wrapText="1"/>'
            .'</xf>'
            // 3: سلول متنی/ردیف وسط‌چین با کادر (B, C, D, E, H, L, M)
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" readingOrder="2" wrapText="1"/>'
            .'</xf>'
            // 4: سلول عددی/مبلغ با فرمت #,##0 و کادر (F, G, J, K)
            .'<xf numFmtId="165" fontId="1" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" readingOrder="2"/>'
            .'</xf>'
            // 5: سلول سبز «مبلغ تعرفه در کتابچه» (ستون I) با پس‌زمینه #92D050 و فرمت #,##0
            .'<xf numFmtId="165" fontId="1" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" readingOrder="2"/>'
            .'</xf>'
            // 6: سلول توضیحات طولانی با wrapText (ستون M در محاسبه تناژی)
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" readingOrder="2" wrapText="1"/>'
            .'</xf>'
            // 7: امضاهای پایین جدول (B Nazanin 11 Bold بدون کادر)
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" readingOrder="2"/>'
            .'</xf>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $itemsData
     */
    private static function setadSheetXml(string $title, iterable $itemsData): string
    {
        $headers = [
            'B' => 'ردیف',
            'C' => 'شماره واگن',
            'D' => 'تعرفه',
            'E' => 'نام صاحب کالا',
            'F' => 'مبلغ با احتساب مالیات بر ارزش افزوده',
            'G' => 'مبلغ کل قابل دریافت از مشتری',
            'H' => 'توضیحات/ شماره وسیله حمل',
            'I' => 'مبلغ تعرفه در کتابچه',
            'J' => 'تناژ (عملیاتی)',
            'K' => 'محاسبه بر اساس کتابچه تعرفه',
            'L' => 'نحوه محاسبه',
            'M' => 'توضیحات',
        ];

        $widths = [
            1 => 2.25,    // A (ستون حاشیه)
            2 => 10,      // B: ردیف
            3 => 14.5,    // C: شماره واگن
            4 => 16.5,    // D: تعرفه
            5 => 24,      // E: نام صاحب کالا
            6 => 20,      // F: مبلغ با احتساب مالیات بر ارزش افزوده
            7 => 23,      // G: مبلغ کل قابل دریافت از مشتری
            8 => 29.5,    // H: توضیحات/ شماره وسیله حمل
            9 => 22,      // I: مبلغ تعرفه در کتابچه
            10 => 18.75,  // J: تناژ (عملیاتی)
            11 => 24,     // K: محاسبه بر اساس کتابچه تعرفه
            12 => 22,     // L: نحوه محاسبه
            13 => 28,     // M: توضیحات
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView rightToLeft="1" tabSelected="1" workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="19.5"/>'
            .'<cols>';

        foreach ($widths as $colIdx => $w) {
            $xml .= '<col min="'.$colIdx.'" max="'.$colIdx.'" width="'.$w.'" customWidth="1"/>';
        }

        $xml .= '</cols><sheetData>';

        // ردیف ۱: بنر عنوان (B1:M1 ادغام‌شده)
        $xml .= '<row r="1" ht="26.25" customHeight="1">';
        foreach (array_keys($headers) as $colLetter) {
            if ($colLetter === 'B') {
                $xml .= '<c r="B1" s="1" t="inlineStr"><is><t>'.self::xmlEscape($title).'</t></is></c>';
            } else {
                $xml .= '<c r="'.$colLetter.'1" s="1"/>';
            }
        }
        $xml .= '</row>';

        // ردیف ۲: سرستون‌ها (B2:M2)
        $xml .= '<row r="2" ht="19.5" customHeight="1">';
        foreach ($headers as $colLetter => $label) {
            $xml .= '<c r="'.$colLetter.'2" s="2" t="inlineStr"><is><t>'.self::xmlEscape($label).'</t></is></c>';
        }
        $xml .= '</row>';

        // ردیف‌های داده (از ردیف ۳ به بعد)
        $rowNum = 3;
        foreach ($itemsData as $row) {
            $xml .= '<row r="'.$rowNum.'" ht="19.5" customHeight="1">';

            // B: ردیف
            $xml .= '<c r="B'.$rowNum.'" s="3"><v>'.((int) $row['index']).'</v></c>';

            // C: شماره واگن
            $wagonNo = (string) ($row['wagon_number'] ?? '');
            if (ctype_digit($wagonNo) && ! str_starts_with($wagonNo, '0')) {
                $xml .= '<c r="C'.$rowNum.'" s="3"><v>'.$wagonNo.'</v></c>';
            } else {
                $xml .= '<c r="C'.$rowNum.'" s="3" t="inlineStr"><is><t>'.self::xmlEscape($wagonNo).'</t></is></c>';
            }

            // D: تعرفه (شرح کالا / نام تعرفه)
            $xml .= '<c r="D'.$rowNum.'" s="3" t="inlineStr"><is><t>'.self::xmlEscape((string) ($row['tariff_name'] ?? '')).'</t></is></c>';

            // E: نام صاحب کالا
            $xml .= '<c r="E'.$rowNum.'" s="3" t="inlineStr"><is><t>'.self::xmlEscape((string) ($row['owner_name'] ?? '')).'</t></is></c>';

            // F: مبلغ با احتساب مالیات بر ارزش افزوده
            $xml .= '<c r="F'.$rowNum.'" s="4"><v>'.((int) ($row['total_with_vat'] ?? 0)).'</v></c>';

            // G: مبلغ کل قابل دریافت از مشتری
            if (! empty($row['is_tonnage'])) {
                $gVal = (float) ($row['customer_payable'] ?? 0);
                $xml .= '<c r="G'.$rowNum.'" s="4"><f>J'.$rowNum.'*K'.$rowNum.'</f><v>'.$gVal.'</v></c>';
            } else {
                $xml .= '<c r="G'.$rowNum.'" s="4"/>';
            }

            // H: توضیحات/ شماره وسیله حمل
            $xml .= '<c r="H'.$rowNum.'" s="3" t="inlineStr"><is><t>'.self::xmlEscape((string) ($row['equipment_label'] ?? '')).'</t></is></c>';

            // I: مبلغ تعرفه در کتابچه (ستون سبز رنگ #92D050)
            $bookletRate = (float) ($row['booklet_rate'] ?? 0);
            if (! empty($row['is_tonnage'])) {
                $xml .= '<c r="I'.$rowNum.'" s="5"><v>'.round($bookletRate).'</v></c>';
            } else {
                $vatDivisor = (float) ($row['vat_divisor'] ?? 1.1);
                $xml .= '<c r="I'.$rowNum.'" s="5"><f>F'.$rowNum.'/'.$vatDivisor.'</f><v>'.$bookletRate.'</v></c>';
            }

            // J: تناژ (عملیاتی)
            if (! empty($row['is_tonnage']) && isset($row['operational_tonnage']) && $row['operational_tonnage'] !== null) {
                $xml .= '<c r="J'.$rowNum.'" s="4"><v>'.((float) $row['operational_tonnage']).'</v></c>';
            } else {
                $xml .= '<c r="J'.$rowNum.'" s="4"/>';
            }

            // K: محاسبه بر اساس کتابچه تعرفه
            if (! empty($row['is_tonnage'])) {
                $cap = (float) ($row['capacity'] ?? 65);
                $capStr = floor($cap) == $cap ? (string) ((int) $cap) : (string) $cap;
                $perTon = (float) ($row['per_ton_rate'] ?? 0);
                $xml .= '<c r="K'.$rowNum.'" s="4"><f>I'.$rowNum.'/'.$capStr.'</f><v>'.$perTon.'</v></c>';
            } else {
                $xml .= '<c r="K'.$rowNum.'" s="4"/>';
            }

            // L: نحوه محاسبه
            $xml .= '<c r="L'.$rowNum.'" s="3" t="inlineStr"><is><t>'.self::xmlEscape((string) ($row['calc_method'] ?? 'طبق قرارداد')).'</t></is></c>';

            // M: توضیحات
            $notes = trim((string) ($row['notes'] ?? ''));
            if ($notes !== '') {
                $xml .= '<c r="M'.$rowNum.'" s="6" t="inlineStr"><is><t>'.self::xmlEscape($notes).'</t></is></c>';
            } else {
                $xml .= '<c r="M'.$rowNum.'" s="3"/>';
            }

            $xml .= '</row>';
            $rowNum++;
        }

        // ردیف‌های امضا در پایین گزارش (دقیقاً مطابق ساختار فایل نمونه ستاد)
        $sigRow1 = $rowNum + 4;
        $sigRow2 = $rowNum + 7;
        $sigRow3 = $rowNum + 8;

        $xml .= '<row r="'.$sigRow1.'" ht="21" customHeight="1">'
            .'<c r="D'.$sigRow1.'" s="7" t="inlineStr"><is><t>امضاء طرف قرار داد / پیمانکار</t></is></c>'
            .'<c r="L'.$sigRow1.'" s="7" t="inlineStr"><is><t>امضاء مدیر شعبه</t></is></c>'
            .'</row>';

        $xml .= '<row r="'.$sigRow2.'" ht="21" customHeight="1">'
            .'<c r="D'.$sigRow2.'" s="7" t="inlineStr"><is><t>امضاء مدیر مجتمع </t></is></c>'
            .'</row>';

        $xml .= '<row r="'.$sigRow3.'" ht="21" customHeight="1">'
            .'<c r="L'.$sigRow3.'" s="7" t="inlineStr"><is><t>امضاء رئیس عملیات</t></is></c>'
            .'</row>';

        $xml .= '</sheetData>'
            .'<mergeCells count="1"><mergeCell ref="B1:M1"/></mergeCells>'
            .'</worksheet>';

        return $xml;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     * @param  array<int, mixed>|null  $totalsRow
     */
    public static function buildXlsx(
        string $sheetTitle,
        array $headers,
        iterable $rows,
        ?array $totalsRow = null
    ): string {
        $colCount = max(1, count($headers));
        $colWidths = array_fill(0, $colCount, 12);

        foreach ($headers as $idx => $header) {
            $len = self::utf8Len((string) $header);
            $colWidths[$idx] = max($colWidths[$idx], min(48, (int) ceil($len * 1.4) + 5));
        }

        $normalizedRows = [];
        foreach ($rows as $row) {
            $vals = array_values((array) $row);
            foreach ($vals as $idx => $val) {
                if ($idx < $colCount && $val !== null) {
                    $displayLen = is_numeric($val)
                        ? strlen(number_format((float) $val))
                        : self::utf8Len((string) $val);
                    $colWidths[$idx] = max($colWidths[$idx], min(55, (int) ceil($displayLen * 1.25) + 4));
                }
            }
            $normalizedRows[] = $vals;
        }

        if ($totalsRow !== null) {
            $totalsRow = array_values($totalsRow);
            foreach ($totalsRow as $idx => $val) {
                if ($idx < $colCount && $val !== null) {
                    $displayLen = is_numeric($val)
                        ? strlen(number_format((float) $val))
                        : self::utf8Len((string) $val);
                    $colWidths[$idx] = max($colWidths[$idx], min(55, (int) ceil($displayLen * 1.3) + 4));
                }
            }
        }

        $files = [
            '[Content_Types].xml' => self::contentTypesXml(),
            '_rels/.rels' => self::rootRelsXml(),
            'xl/_rels/workbook.xml.rels' => self::workbookRelsXml(),
            'xl/workbook.xml' => self::workbookXml($sheetTitle),
            'xl/styles.xml' => self::stylesXml(),
            'xl/worksheets/sheet1.xml' => self::sheetXml($sheetTitle, $headers, $normalizedRows, $totalsRow, $colWidths),
        ];

        return self::buildZipArchive($files);
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function workbookXml(string $sheetTitle): string
    {
        // نام تب در اکسل حداکثر ۳۱ کاراکتر و بدون کاراکترهای غیرمجاز
        $tabName = preg_replace('/[\\\\\\/?*\\[\\]:]/u', ' ', trim($sheetTitle)) ?: 'گزارش';
        $tabName = self::utf8Substr($tabName, 0, 31);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<bookViews><workbookView rightToLeft="1"/></bookViews>'
            .'<sheets><sheet name="'.self::xmlEscape($tabName).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="3">'
            .'<font><sz val="11"/><color rgb="FF111827"/><name val="Tahoma"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Tahoma"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FF1E293B"/><name val="Tahoma"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="5">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF1E3A8A"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFEFF6FF"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border>'
            .'<left style="thin"><color rgb="FFCBD5E1"/></left>'
            .'<right style="thin"><color rgb="FFCBD5E1"/></right>'
            .'<top style="thin"><color rgb="FFCBD5E1"/></top>'
            .'<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="8">'
            // 0: پیش‌فرض
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            // 1: سرستون جدول (آبی تیره، بولد سفید، وسط‌چین RTL)
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" readingOrder="2" wrapText="1"/>'
            .'</xf>'
            // 2: سلول متنی عادی (راست‌چین RTL با کادر)
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="right" vertical="center" readingOrder="2" wrapText="1"/>'
            .'</xf>'
            // 3: سلول عدد صحیح / مبلغ (با جداکننده هزارگان #,##0)
            .'<xf numFmtId="3" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" readingOrder="2"/>'
            .'</xf>'
            // 4: سلول عدد اعشاری (وزن 0.00)
            .'<xf numFmtId="2" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" readingOrder="2"/>'
            .'</xf>'
            // 5: سلول متنی ردیف جمع کل (بولد با پس‌زمینه کهربایی)
            .'<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="right" vertical="center" readingOrder="2"/>'
            .'</xf>'
            // 6: سلول عددی ردیف جمع کل (بولد با پس‌زمینه کهربایی و جداکننده هزارگان)
            .'<xf numFmtId="3" fontId="2" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" readingOrder="2"/>'
            .'</xf>'
            // 7: بنر عنوان بالای گزارش
            .'<xf numFmtId="0" fontId="2" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" readingOrder="2"/>'
            .'</xf>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, mixed>|null  $totalsRow
     * @param  array<int, int>  $colWidths
     */
    private static function sheetXml(
        string $sheetTitle,
        array $headers,
        array $rows,
        ?array $totalsRow,
        array $colWidths
    ): string {
        $colCount = max(1, count($headers));
        $lastColLetter = self::colLetter($colCount - 1);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView rightToLeft="1" tabSelected="1" workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="20"/>'
            .'<cols>';

        foreach ($colWidths as $idx => $width) {
            $colNum = $idx + 1;
            $xml .= '<col min="'.$colNum.'" max="'.$colNum.'" width="'.$width.'" customWidth="1"/>';
        }

        $xml .= '</cols><sheetData>';

        // ردیف ۱: عنوان گزارش
        $xml .= '<row r="1" ht="28" customHeight="1">';
        for ($c = 0; $c < $colCount; $c++) {
            $ref = self::colLetter($c).'1';
            if ($c === 0) {
                $xml .= '<c r="'.$ref.'" s="7" t="inlineStr"><is><t>'.self::xmlEscape($sheetTitle).'</t></is></c>';
            } else {
                $xml .= '<c r="'.$ref.'" s="7"/>';
            }
        }
        $xml .= '</row>';

        // ردیف ۲: سرستون‌ها
        $xml .= '<row r="2" ht="24" customHeight="1">';
        foreach ($headers as $c => $headerText) {
            $ref = self::colLetter($c).'2';
            $xml .= '<c r="'.$ref.'" s="1" t="inlineStr"><is><t>'.self::xmlEscape((string) $headerText).'</t></is></c>';
        }
        $xml .= '</row>';

        // ردیف‌های داده
        $rowNum = 3;
        foreach ($rows as $row) {
            $xml .= '<row r="'.$rowNum.'" ht="21" customHeight="1">';
            for ($c = 0; $c < $colCount; $c++) {
                $val = $row[$c] ?? '';
                $ref = self::colLetter($c).$rowNum;
                $xml .= self::buildCellXml($ref, $val, false);
            }
            $xml .= '</row>';
            $rowNum++;
        }

        // ردیف جمع کل (در صورت وجود)
        if ($totalsRow !== null) {
            $xml .= '<row r="'.$rowNum.'" ht="24" customHeight="1">';
            for ($c = 0; $c < $colCount; $c++) {
                $val = $totalsRow[$c] ?? '';
                $ref = self::colLetter($c).$rowNum;
                $xml .= self::buildCellXml($ref, $val, true);
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData>';

        if ($colCount > 1) {
            $xml .= '<mergeCells count="1"><mergeCell ref="A1:'.$lastColLetter.'1"/></mergeCells>';
        }

        $xml .= '</worksheet>';

        return $xml;
    }

    private static function buildCellXml(string $ref, mixed $val, bool $isTotalRow): string
    {
        if (is_int($val) || is_float($val)) {
            $isFloat = is_float($val) && floor($val) != $val;
            $style = $isTotalRow ? 6 : ($isFloat ? 4 : 3);

            return '<c r="'.$ref.'" s="'.$style.'"><v>'.$val.'</v></c>';
        }

        $str = trim((string) ($val ?? ''));

        return '<c r="'.$ref.'" s="'.($isTotalRow ? 5 : 2).'" t="inlineStr"><is><t>'.self::xmlEscape($str).'</t></is></c>';
    }

    /**
     * تبدیل اندیس ستون (۰، ۱، ۲، ...) به حروف ستون اکسل (A, B, C, ..., Z, AA, AB, ...)
     */
    private static function colLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(($index % 26) + 65).$letter;
            $index = intdiv($index, 26) - 1;
        }

        return $letter;
    }

    private static function xmlEscape(string $value): string
    {
        // حذف کاراکترهای کنترلی غیرمجاز در XML 1.0
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function utf8Len(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int) mb_strlen($value, 'UTF-8');
        }

        $count = preg_match_all('/./us', $value);

        return $count !== false ? $count : strlen($value);
    }

    private static function utf8Substr(string $value, int $start, int $length): string
    {
        if (function_exists('mb_substr')) {
            return (string) mb_substr($value, $start, $length, 'UTF-8');
        }

        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($chars)) {
            return substr($value, $start, $length);
        }

        return implode('', array_slice($chars, $start, $length));
    }

    /**
     * ساخت آرشیو ZIP استاندارد در حافظه بدون نیاز به افزونه ext-zip یا فایل موقت دیسک.
     *
     * @param  array<string, string>  $entries
     */
    private static function buildZipArchive(array $entries): string
    {
        $localParts = [];
        $centralParts = [];
        $offset = 0;
        $count = 0;

        foreach ($entries as $name => $data) {
            $name = str_replace('\\', '/', $name);
            $uncompressedSize = strlen($data);
            $crc = crc32($data);

            $compressed = function_exists('gzdeflate') ? gzdeflate($data, 6) : false;
            if ($compressed !== false && strlen($compressed) < $uncompressedSize) {
                $method = 8; // Deflate
                $payload = $compressed;
            } else {
                $method = 0; // Store
                $payload = $data;
            }

            $compressedSize = strlen($payload);
            $nameLen = strlen($name);

            // DOS timestamp (2026-01-01 12:00:00)
            $dosTime = (12 << 11) | (0 << 5) | 0;
            $dosDate = ((2026 - 1980) << 9) | (1 << 5) | 1;

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034B50,
                20,
                0,
                $method,
                $dosTime,
                $dosDate,
                $crc,
                $compressedSize,
                $uncompressedSize,
                $nameLen,
                0
            ).$name;

            $localEntry = $localHeader.$payload;
            $localParts[] = $localEntry;

            $centralParts[] = pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014B50,
                20,
                20,
                0,
                $method,
                $dosTime,
                $dosDate,
                $crc,
                $compressedSize,
                $uncompressedSize,
                $nameLen,
                0,
                0,
                0,
                0,
                32,
                $offset
            ).$name;

            $offset += strlen($localEntry);
            $count++;
        }

        $centralDir = implode('', $centralParts);
        $localData = implode('', $localParts);

        $eocd = pack(
            'VvvvvVVv',
            0x06054B50,
            0,
            0,
            $count,
            $count,
            strlen($centralDir),
            strlen($localData),
            0
        );

        return $localData.$centralDir.$eocd;
    }
}
