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
