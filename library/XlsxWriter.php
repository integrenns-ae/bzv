<?php
declare(strict_types=1);

/**
 * Minimaler XLSX-Writer (Office Open XML) ohne Composer-Dependencies.
 * Schreibt eine einzelne Tabelle (Header + Datenzeilen) als echtes .xlsx —
 * funktioniert in Excel, LibreOffice, Numbers, Google Sheets.
 *
 * Verwendung:
 *   XlsxWriter::send('foo.xlsx', ['Spalte A', 'Spalte B'], [
 *       ['Wert', 1.23],
 *       ['Text', 42],
 *   ]);
 */
final class XlsxWriter
{
    /**
     * Sendet eine XLSX-Datei als Download an den Browser.
     *
     * @param string $filename  Dateiname (mit .xlsx)
     * @param string[] $headers Spalten-Header (Zeile 1)
     * @param array[] $rows     Datenzeilen (jede ein Array aus Strings/Zahlen)
     */
    public static function send(string $filename, array $headers, array $rows): void
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('PHP-Extension "zip" ist nicht verfügbar.');
        }
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('XLSX-Datei konnte nicht angelegt werden.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels',         self::rootRelsXml());
        $zip->addFromString('xl/workbook.xml',     self::workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/styles.xml',       self::stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml($headers, $rows));
        $zip->close();

        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        readfile($tmp);
        @unlink($tmp);
    }

    /**
     * Spalten-Index → Excel-Spaltenbuchstabe: 0 → A, 25 → Z, 26 → AA, …
     */
    private static function colLetter(int $i): string
    {
        $s = '';
        $i += 1;
        while ($i > 0) {
            $r = ($i - 1) % 26;
            $s = chr(65 + $r) . $s;
            $i = (int)(($i - 1) / 26);
        }
        return $s;
    }

    private static function sheetXml(array $headers, array $rows): string
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        // Header (Stil-Index 1 = fett)
        $xml .= '<row r="1">';
        foreach ($headers as $i => $h) {
            $ref = self::colLetter($i) . '1';
            $xml .= '<c r="' . $ref . '" s="1" t="inlineStr"><is><t>' . self::esc((string)$h) . '</t></is></c>';
        }
        $xml .= '</row>';

        // Datenzeilen
        $rowNum = 2;
        foreach ($rows as $row) {
            $xml .= '<row r="' . $rowNum . '">';
            foreach ($row as $i => $val) {
                $ref = self::colLetter($i) . $rowNum;
                if ($val === null || $val === '') {
                    // leere Zelle weglassen ist OK; aber Referenz behalten falls Zelle gewünscht
                    continue;
                }
                if (is_int($val) || (is_float($val) && is_finite($val))) {
                    $xml .= '<c r="' . $ref . '"><v>' . $val . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . self::esc((string)$val) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
            $rowNum++;
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private static function stylesXml(): string
    {
        // 2 Cell-Styles: 0 = Standard, 1 = Fett (für Header)
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>' .
            '<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>' .
            '<fills count="2"><fill><patternFill patternType="none"/></fill>' .
            '<fill><patternFill patternType="gray125"/></fill></fills>' .
            '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>' .
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' .
            '<cellXfs count="2">' .
              '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' .
              '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>' .
            '</cellXfs>' .
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' .
            '</styleSheet>';
    }

    private static function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' .
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets><sheet name="Bestellungen" sheetId="1" r:id="rId1"/></sheets>' .
            '</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
            '</Relationships>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>';
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '</Types>';
    }

    private static function esc(string $s): string
    {
        // XML-Escape + Steuerzeichen entfernen (Excel mag sie nicht)
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? $s;
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
