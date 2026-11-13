<?php

namespace App\Libraries;

use Throwable;
use XMLReader;
use ZipArchive;

/**
 * Minimal Excel (.xlsx, Office Open XML) reader and writer for the sitepanel
 * bulk upload, using PHP's own zip and XML extensions instead of a
 * spreadsheet package. Only cell values are read (first worksheet; text,
 * numbers, booleans, formulas' cached results); writing makes simple sheets
 * with a bold header row. Excel 97-2003 (.xls) files are not supported.
 */
class SimpleXlsx
{
    /**
     * Largest uncompressed XML part that is read, in bytes (guards against zip bombs).
     */
    public const MAX_PART_BYTES = 50 * 1024 * 1024;

    protected const MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /**
     * Whether the file is an Excel workbook (a ZIP archive holding xl/workbook.xml).
     */
    public static function is_xlsx(string $path): bool
    {
        if (! class_exists(ZipArchive::class)) {
            return false;
        }
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }
        $ok = $zip->locateName('xl/workbook.xml') !== false;
        $zip->close();

        return $ok;
    }

    /**
     * The rows of the first worksheet, each a list of cell values (strings,
     * columns from A, empty cells ''), or an error message.
     *
     * @return list<list<string>>|string
     */
    public static function read(string $path, int $max_rows = 0)
    {
        if (! class_exists(ZipArchive::class) || ! class_exists(XMLReader::class)) {
            return 'Excel files cannot be read on this server (the PHP zip / xml extensions are missing). Upload the sheet as CSV.';
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return 'The file is not a valid Excel (.xlsx) workbook.';
        }

        try {
            $sheet = self::first_sheet_path($zip);
            if ($sheet === null) {
                return 'The workbook has no worksheet.';
            }
            $strings = self::shared_strings($zip);
            $xml     = self::part($zip, $sheet);
            if ($xml === null || $strings === null) {
                return 'The workbook could not be read. Save it again in Excel as .xlsx and retry.';
            }

            return self::sheet_rows($xml, $strings, $max_rows);
        } catch (Throwable $e) {
            log_message('error', 'SimpleXlsx::read: ' . $e->getMessage());

            return 'The workbook could not be read. Save it again in Excel as .xlsx and retry.';
        } finally {
            $zip->close();
        }
    }

    /**
     * An .xlsx workbook (binary string) of one or more sheets: name => rows.
     * The first row of each sheet is the header (bold, frozen). Values that
     * look like plain numbers are written as numbers, the rest as text.
     *
     * @param array<string, list<list<scalar|null>>> $sheets
     * @param array<string, list<int>>               $widths sheet name => column widths (characters)
     */
    public static function build(array $sheets, array $widths = []): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $names     = array_keys($sheets);
        $overrides = '';
        $book      = '';
        $rels      = '';
        foreach ($names as $i => $name) {
            $n = $i + 1;
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $book      .= '<sheet name="' . self::esc(mb_substr(preg_replace('#[\\\\/?*\[\]:]#', ' ', $name), 0, 31)) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $rels      .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
            $zip->addFromString('xl/worksheets/sheet' . $n . '.xml', self::sheet_xml($sheets[$name], $widths[$name] ?? []));
        }
        $styles_id = count($names) + 1;

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="' . self::MAIN_NS . '" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $book . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels
            . '<Relationship Id="rId' . $styles_id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        // Style 0: normal; 1: bold on light grey (header row, as the reference's sheets).
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="' . self::MAIN_NS . '">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFCCCCCC"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
            . '</styleSheet>');
        $zip->close();

        $data = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $data;
    }

    /**
     * Column letters of a 0-based index (0 => A, 26 => AA).
     */
    public static function column(int $index): string
    {
        $name = '';
        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $name = chr(65 + ($n - 1) % 26) . $name;
        }

        return $name;
    }

    protected static function sheet_xml(array $rows, array $widths): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="' . self::MAIN_NS . '">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        if ($widths) {
            $xml .= '<cols>';
            foreach ($widths as $i => $width) {
                $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (int) $width . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }
        $xml .= '<sheetData>';
        foreach (array_values($rows) as $r => $row) {
            $xml .= '<row r="' . ($r + 1) . '">';
            foreach (array_values($row) as $c => $value) {
                $value = (string) $value;
                if ($value === '') {
                    continue;
                }
                $ref   = self::column($c) . ($r + 1);
                $style = $r === 0 ? ' s="1"' : '';
                $xml  .= $r > 0 && preg_match('/^-?(0|[1-9][0-9]{0,14})(\.[0-9]+)?$/D', $value)
                    ? '<c r="' . $ref . '"' . $style . '><v>' . $value . '</v></c>'
                    : '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">' . self::esc($value) . '</t></is></c>';
            }
            $xml .= '</row>';
        }

        return $xml . '</sheetData></worksheet>';
    }

    protected static function esc(string $value): string
    {
        // Characters XML 1.0 does not allow are dropped.
        return htmlspecialchars(preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value) ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * A part of the archive as text, or NULL when missing / too large.
     */
    protected static function part(ZipArchive $zip, string $name): ?string
    {
        $stat = $zip->statName($name);
        if ($stat === false || $stat['size'] > self::MAX_PART_BYTES) {
            return null;
        }
        $xml = $zip->getFromName($name);

        return $xml === false ? null : $xml;
    }

    /**
     * Archive path of the first worksheet (from the workbook and its relationships).
     */
    protected static function first_sheet_path(ZipArchive $zip): ?string
    {
        $book = self::part($zip, 'xl/workbook.xml');
        $rels = self::part($zip, 'xl/_rels/workbook.xml.rels');
        if ($book !== null && $rels !== null
            && preg_match('/<(?:\w+:)?sheet\b[^>]*\br:id="([^"]+)"/', $book, $m)
            && preg_match('/<Relationship\b[^>]*\bId="' . preg_quote($m[1], '/') . '"[^>]*>/', $rels, $rel)
            && preg_match('/\bTarget="([^"]+)"/', $rel[0], $target)) {
            $path = html_entity_decode($target[1], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $path = str_starts_with($path, '/') ? ltrim($path, '/') : 'xl/' . $path;

            return $zip->locateName($path) !== false ? $path : null;
        }

        return $zip->locateName('xl/worksheets/sheet1.xml') !== false ? 'xl/worksheets/sheet1.xml' : null;
    }

    /**
     * The shared string table (list of strings); NULL when it can't be read.
     *
     * @return list<string>|null
     */
    protected static function shared_strings(ZipArchive $zip): ?array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }
        $xml = self::part($zip, 'xl/sharedStrings.xml');
        if ($xml === null) {
            return null;
        }

        $reader = new XMLReader();
        $reader->XML($xml, 'UTF-8', LIBXML_NONET | LIBXML_COMPACT);
        $strings = [];
        $text    = null;
        $in_ruby = 0;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                if ($reader->localName === 'si') {
                    $text = '';
                } elseif ($reader->localName === 'rPh') {
                    $in_ruby += $reader->isEmptyElement ? 0 : 1; // phonetic hints are not part of the text
                } elseif ($reader->localName === 't' && $text !== null && ! $in_ruby) {
                    $text .= $reader->readString();
                }
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                if ($reader->localName === 'rPh') {
                    $in_ruby = max(0, $in_ruby - 1);
                } elseif ($reader->localName === 'si') {
                    $strings[] = (string) $text;
                    $text      = null;
                }
            }
        }
        $reader->close();

        return $strings;
    }

    /**
     * @param list<string> $strings
     *
     * @return list<list<string>>
     */
    protected static function sheet_rows(string $xml, array $strings, int $max_rows): array
    {
        $reader = new XMLReader();
        $reader->XML($xml, 'UTF-8', LIBXML_NONET | LIBXML_COMPACT);
        $rows = [];
        $row  = null;
        $r    = 0;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'row') {
                $r   = (int) $reader->getAttribute('r') ?: $r + 1;
                $row = [];
                if ($reader->isEmptyElement) {
                    $rows[$r] = [];
                    $row      = null;
                }
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'row') {
                $rows[$r] = (array) $row;
                $row      = null;
                if ($max_rows > 0 && count($rows) > $max_rows) {
                    break; // the caller reports "too many rows"
                }
            } elseif ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'c' && $row !== null) {
                $ref   = (string) $reader->getAttribute('r');
                $type  = (string) $reader->getAttribute('t');
                $col   = preg_match('/^([A-Z]+)/', $ref, $m) ? self::column_index($m[1]) : count($row);
                $value = '';
                if (! $reader->isEmptyElement) {
                    $cell = $reader->readOuterXml();
                    $value = self::cell_value($cell, $type, $strings);
                }
                $row[$col] = $value;
            }
        }
        $reader->close();

        if (! $rows) {
            return [];
        }

        // Fill missing rows / cells so row and column positions match the sheet.
        $out = [];
        for ($i = 1, $last = max(array_keys($rows)); $i <= $last; $i++) {
            $cells = $rows[$i] ?? [];
            $list  = [];
            for ($c = 0, $end = $cells ? max(array_keys($cells)) : -1; $c <= $end; $c++) {
                $list[] = $cells[$c] ?? '';
            }
            $out[] = $list;
        }

        return $out;
    }

    protected static function cell_value(string $cell, string $type, array $strings): string
    {
        $value = preg_match('#<(?:\w+:)?v(?:\s[^>]*)?>(.*?)</(?:\w+:)?v>#s', $cell, $m) ? html_entity_decode($m[1], ENT_XML1 | ENT_QUOTES, 'UTF-8') : '';

        switch ($type) {
            case 's':
                return $strings[(int) $value] ?? '';

            case 'inlineStr':
                preg_match_all('#<(?:\w+:)?t(?:\s[^>]*)?>(.*?)</(?:\w+:)?t>#s', preg_replace('#<(?:\w+:)?rPh\b.*?</(?:\w+:)?rPh>#s', '', $cell), $parts);

                return html_entity_decode(implode('', $parts[1]), ENT_XML1 | ENT_QUOTES, 'UTF-8');

            case 'b':
                return $value === '1' ? '1' : '0';

            case 'str':
            case 'e':
                return $value;

            default:
                // A number: Excel's binary floating point (18.199999999999999) shown as typed (18.2).
                return is_numeric($value) ? self::number($value) : $value;
        }
    }

    protected static function number(string $value): string
    {
        $float = (float) $value;
        if (floor($float) === $float && abs($float) < 1e15) {
            return (string) (int) $float;
        }

        return rtrim(rtrim(sprintf('%.10F', round($float, 10)), '0'), '.');
    }

    protected static function column_index(string $letters): int
    {
        $n = 0;
        foreach (str_split($letters) as $char) {
            $n = $n * 26 + (ord($char) - 64);
        }

        return $n - 1;
    }
}
