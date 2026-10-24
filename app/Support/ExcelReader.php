<?php

namespace App\Support;

use RuntimeException;

/**
 * خواننده بومی فایل‌های اکسل (.xlsx) در PHP خالص بدون نیاز به افزونه ext-zip یا ext-dom یا پکیج خارجی.
 */
class ExcelReader
{
    /** حداکثر حجم مجاز هر فایل XML بازشده در حافظه (جلوگیری از Zip Bomb) */
    private const MAX_UNCOMPRESSED_ENTRY_BYTES = 16 * 1024 * 1024;

    /** حداکثر مجموع حجم بازشده کل آرشیو اکسل در حافظه */
    private const MAX_TOTAL_UNCOMPRESSED_BYTES = 32 * 1024 * 1024;

    /**
     * خواندن تمام شیت‌ها و ردیف‌های یک فایل `.xlsx`
     *
     * @return array<int, array{name: string, rows: array<int, array<string, string>>}>
     */
    public static function readSheets(string $filePath): array
    {
        $binary = @file_get_contents($filePath);
        if ($binary === false || strlen($binary) < 22) {
            throw new RuntimeException('فایل اکسل قابل خواندن نیست یا خالی است.');
        }

        $entries = self::extractZipEntries($binary);
        if (empty($entries)) {
            throw new RuntimeException('فرمت فایل انتخاب‌شده یک فایل اکسل معتبر (.xlsx) نیست.');
        }

        $sharedStrings = [];
        if (isset($entries['xl/sharedStrings.xml'])) {
            $sharedStrings = self::parseSharedStrings($entries['xl/sharedStrings.xml']);
        }

        $sheetNames = [];
        if (isset($entries['xl/workbook.xml'])) {
            if (preg_match_all('/<sheet\b[^>]*name="([^"]+)"[^>]*>/u', $entries['xl/workbook.xml'], $matches)) {
                foreach ($matches[1] as $idx => $name) {
                    $sheetNames[$idx + 1] = html_entity_decode($name, ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
            }
        }

        $sheets = [];
        ksort($entries);

        $sheetIdx = 1;
        foreach ($entries as $entryName => $xml) {
            if (! preg_match('#^xl/worksheets/sheet(\d+)\.xml$#', $entryName, $m)) {
                continue;
            }

            $num = (int) $m[1];
            $rows = self::parseWorksheetRows($xml, $sharedStrings);

            $sheets[] = [
                'name' => $sheetNames[$num] ?? ('Sheet'.$sheetIdx),
                'rows' => $rows,
            ];
            $sheetIdx++;
        }

        return $sheets;
    }

    /**
     * استخراج محتوای فایل‌های درون آرشیو ZIP در حافظه بدون نیاز به ext-zip
     *
     * @return array<string, string>
     */
    private static function extractZipEntries(string $zipData): array
    {
        $entries = [];
        $len = strlen($zipData);
        $offset = 0;
        $totalUncompressed = 0;

        while ($offset + 30 <= $len) {
            $sig = unpack('V', substr($zipData, $offset, 4))[1];
            if ($sig !== 0x04034B50) {
                break;
            }

            $header = unpack(
                'vver/vflag/vmethod/vmtime/vmdate/Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen',
                substr($zipData, $offset + 4, 26)
            );

            $name = substr($zipData, $offset + 30, $header['nameLen']);
            $dataStart = $offset + 30 + $header['nameLen'] + $header['extraLen'];
            $compSize = $header['compSize'];

            // اگر بیت ۳ از پرچم (Data Descriptor) روشن باشد، اندازه واقعی را از فهرست مرکزی می‌خوانیم
            if (($header['flag'] & 0x0008) !== 0 && $compSize === 0) {
                return self::extractViaCentralDirectory($zipData);
            }

            if ($header['uncompSize'] > self::MAX_UNCOMPRESSED_ENTRY_BYTES) {
                throw new RuntimeException('حجم بازشده فایل اکسل بیش از حد مجاز است.');
            }

            $rawPayload = substr($zipData, $dataStart, $compSize);

            if ($header['method'] === 0) {
                if (strlen($rawPayload) > self::MAX_UNCOMPRESSED_ENTRY_BYTES) {
                    throw new RuntimeException('حجم بازشده فایل اکسل بیش از حد مجاز است.');
                }
                $entries[$name] = $rawPayload;
                $totalUncompressed += strlen($rawPayload);
            } elseif ($header['method'] === 8 && function_exists('gzinflate')) {
                $inflated = @gzinflate($rawPayload, self::MAX_UNCOMPRESSED_ENTRY_BYTES);
                if ($inflated !== false) {
                    $entries[$name] = $inflated;
                    $totalUncompressed += strlen($inflated);
                }
            }

            if ($totalUncompressed > self::MAX_TOTAL_UNCOMPRESSED_BYTES) {
                throw new RuntimeException('مجموع حجم داده‌های فایل اکسل بیش از سقف مجاز است.');
            }

            $offset = $dataStart + $compSize;
        }

        if (empty($entries)) {
            return self::extractViaCentralDirectory($zipData);
        }

        return $entries;
    }

    /**
     * @return array<string, string>
     */
    private static function extractViaCentralDirectory(string $zipData): array
    {
        $entries = [];
        $eocdPos = strrpos($zipData, "\x50\x4b\x05\x06");
        if ($eocdPos === false || strlen($zipData) < $eocdPos + 22) {
            return $entries;
        }

        $eocd = unpack('vdisk/vcdDisk/vdiskEntries/vtotalEntries/VcdSize/VcdOffset', substr($zipData, $eocdPos + 4, 16));
        $pos = $eocd['cdOffset'];
        $end = $pos + $eocd['cdSize'];
        $totalUncompressed = 0;

        while ($pos + 46 <= $end && $pos + 46 <= strlen($zipData)) {
            $sig = unpack('V', substr($zipData, $pos, 4))[1];
            if ($sig !== 0x02014B50) {
                break;
            }

            $cd = unpack(
                'vverMade/vverNeed/vflag/vmethod/vmtime/vmdate/Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen/vcommentLen/vdiskStart/vintAttr/VextAttr/VlocalOffset',
                substr($zipData, $pos + 4, 42)
            );

            if ($cd['uncompSize'] > self::MAX_UNCOMPRESSED_ENTRY_BYTES) {
                throw new RuntimeException('حجم بازشده فایل اکسل بیش از حد مجاز است.');
            }

            $name = substr($zipData, $pos + 46, $cd['nameLen']);
            $localOffset = $cd['localOffset'];

            if ($localOffset + 30 <= strlen($zipData)) {
                $localLengths = unpack('vnameLen/vextraLen', substr($zipData, $localOffset + 26, 4));
                $dataStart = $localOffset + 30 + $localLengths['nameLen'] + $localLengths['extraLen'];
                $rawPayload = substr($zipData, $dataStart, $cd['compSize']);

                if ($cd['method'] === 0) {
                    if (strlen($rawPayload) > self::MAX_UNCOMPRESSED_ENTRY_BYTES) {
                        throw new RuntimeException('حجم بازشده فایل اکسل بیش از حد مجاز است.');
                    }
                    $entries[$name] = $rawPayload;
                    $totalUncompressed += strlen($rawPayload);
                } elseif ($cd['method'] === 8 && function_exists('gzinflate')) {
                    $inflated = @gzinflate($rawPayload, self::MAX_UNCOMPRESSED_ENTRY_BYTES);
                    if ($inflated !== false) {
                        $entries[$name] = $inflated;
                        $totalUncompressed += strlen($inflated);
                    }
                }

                if ($totalUncompressed > self::MAX_TOTAL_UNCOMPRESSED_BYTES) {
                    throw new RuntimeException('مجموع حجم داده‌های فایل اکسل بیش از سقف مجاز است.');
                }
            }

            $pos += 46 + $cd['nameLen'] + $cd['extraLen'] + $cd['commentLen'];
        }

        return $entries;
    }

    /**
     * @return array<int, string>
     */
    private static function parseSharedStrings(string $xml): array
    {
        $strings = [];
        if (preg_match_all('#<si\b[^>]*>(.*?)</si>#su', $xml, $siMatches)) {
            foreach ($siMatches[1] as $siContent) {
                if (preg_match_all('#<t(?:\s[^>]*)?>(.*?)</t>#su', $siContent, $tMatches)) {
                    $text = implode('', array_map(
                        fn ($t) => html_entity_decode($t, ENT_QUOTES | ENT_XML1, 'UTF-8'),
                        $tMatches[1]
                    ));
                    $strings[] = $text;
                } else {
                    $strings[] = '';
                }
            }
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<string, string>>
     */
    private static function parseWorksheetRows(string $xml, array $sharedStrings): array
    {
        $rows = [];
        if (! preg_match_all('#<row\b[^>]*>(.*?)</row>#su', $xml, $rowMatches)) {
            return $rows;
        }

        foreach ($rowMatches[1] as $rowXml) {
            if (! preg_match_all('#<c\b([^/>]*)(?:/>|>(.*?)</c>)#su', $rowXml, $cellMatches, PREG_SET_ORDER)) {
                continue;
            }

            $rowCells = [];
            foreach ($cellMatches as $cellMatch) {
                $attrs = $cellMatch[1];
                $inner = $cellMatch[2] ?? '';

                $colLetter = '';
                if (preg_match('/\br="([A-Z]+)\d+"/i', $attrs, $rm)) {
                    $colLetter = strtoupper($rm[1]);
                }

                $type = '';
                if (preg_match('/\bt="([^"]+)"/i', $attrs, $tm)) {
                    $type = $tm[1];
                }

                $val = '';
                if ($type === 's') {
                    if (preg_match('#<v>(.*?)</v>#su', $inner, $vm)) {
                        $idx = (int) trim($vm[1]);
                        $val = $sharedStrings[$idx] ?? '';
                    }
                } elseif ($type === 'inlineStr') {
                    if (preg_match_all('#<t(?:\s[^>]*)?>(.*?)</t>#su', $inner, $tm2)) {
                        $val = implode('', array_map(
                            fn ($t) => html_entity_decode($t, ENT_QUOTES | ENT_XML1, 'UTF-8'),
                            $tm2[1]
                        ));
                    }
                } else {
                    if (preg_match('#<v>(.*?)</v>#su', $inner, $vm)) {
                        $val = html_entity_decode(trim($vm[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
                    } elseif (preg_match_all('#<t(?:\s[^>]*)?>(.*?)</t>#su', $inner, $tm2)) {
                        $val = implode('', array_map(
                            fn ($t) => html_entity_decode($t, ENT_QUOTES | ENT_XML1, 'UTF-8'),
                            $tm2[1]
                        ));
                    }
                }

                $val = trim($val);
                if ($colLetter !== '' && $val !== '') {
                    $rowCells[$colLetter] = $val;
                }
            }

            if (! empty($rowCells)) {
                $rows[] = $rowCells;
            }
        }

        return $rows;
    }
}
