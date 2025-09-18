<?php
/**
 * Excel Helper Class using PhpSpreadsheet
 * Professional Excel import/export functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExcelHelper {

    /**
     * Export data to Excel
     */
    public static function exportToExcel($data, $filename, $headers = [], $title = '') {
        // Check if zip extension is available for xlsx format
        if (!extension_loaded('zip')) {
            // Fallback to CSV export
            return self::exportToCSV($data, $filename, $headers, $title);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title if provided
        if (!empty($title)) {
            $sheet->setTitle(substr($title, 0, 31)); // Excel sheet name limit
        }

        $currentRow = 1;

        // Add title row if provided
        if (!empty($title)) {
            $sheet->setCellValue('A1', $title);
            $sheet->mergeCells('A1:' . self::getColumnLetter(count($headers)) . '1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 16,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD'],
                ],
            ]);
            $currentRow = 3; // Leave a blank row
        }

        // Add headers
        if (!empty($headers)) {
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $currentRow, $header);
                $col++;
            }

            // Style headers
            $headerRange = 'A' . $currentRow . ':' . self::getColumnLetter(count($headers)) . $currentRow;
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1976D2'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);

            $currentRow++;
        }

        $dataCells = [];

        // Add data rows
        foreach ($data as $row) {
            $colIndex = 0;
            foreach ($row as $cell) {
                $colLetter = self::getColumnLetter($colIndex + 1);
                $dataCells[(string)($colLetter . $currentRow)] = $cell;
                $sheet->setCellValue($colLetter . $currentRow, $cell);
                $colIndex++;
            }
            $currentRow++;
        }

        // return print_r($dataCells);

        // Auto-size columns
        if (!empty($headers)) {
            foreach (range('A', self::getColumnLetter(count($headers))) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        // Add borders to data
        if ($currentRow > 1 && !empty($headers)) {
            $dataRange = 'A' . ($currentRow - count($data)) . ':' . self::getColumnLetter(count($headers)) . ($currentRow - 1);
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);
        }

        // Generate output
        if (!str_ends_with($filename, '.xlsx') && !str_ends_with($filename, '.xls')) {
            $filename .= '.xlsx';
        }

        // Clean output buffer to prevent corruption
        if (ob_get_length()) {
            ob_clean();
        }

        // Set proper headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * CSV Export fallback method
     */
    private static function exportToCSV($data, $filename, $headers = [], $title = '') {
        // Ensure filename has .csv extension
        if (!str_ends_with($filename, '.csv')) {
            $filename .= '.csv';
        }

        // Clean output buffer to prevent corruption
        if (ob_get_length()) {
            ob_clean();
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Add BOM for UTF-8
        echo "\xEF\xBB\xBF";

        // Create file pointer connected to the output stream
        $output = fopen('php://output', 'w');

        // Add title if provided
        if (!empty($title)) {
            fputcsv($output, [$title]);
            fputcsv($output, []); // Empty row
        }

        // Write headers if provided
        if (!empty($headers)) {
            fputcsv($output, $headers);
        }

        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Import Excel file
     */
    public static function importFromExcel($file_path) {
        if (!file_exists($file_path)) {
            throw new Exception('File tidak ditemukan');
        }

        // Check if zip extension is available
        if (!extension_loaded('zip')) {
            // Fallback to CSV parsing for better compatibility
            return self::importFromCSV($file_path);
        }

        try {
            $spreadsheet = IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = [];

            foreach ($worksheet->getRowIterator() as $row) {
                $rowData = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    $value = $cell->getCalculatedValue();
                    $rowData[] = trim((string)$value);
                }

                // Skip completely empty rows
                if (!empty(array_filter($rowData, function($cell) { return $cell !== ''; }))) {
                    $data[] = $rowData;
                }
            }

            return $data;

        } catch (Exception $e) {
            // If PhpSpreadsheet fails, try CSV fallback
            return self::importFromCSV($file_path);
        }
    }

    /**
     * CSV Import fallback method
     */
    private static function importFromCSV($file_path) {
        $data = [];

        // Try to detect file encoding
        $content = file_get_contents($file_path);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

        // Try different delimiters
        $delimiters = ["\t", ",", ";"];  // Tab, comma, semicolon
        $found_delimiter = ",";
        $max_cols = 0;

        // Detect the best delimiter
        foreach ($delimiters as $delimiter) {
            if (($handle = fopen($file_path, 'r')) !== FALSE) {
                $first_row = fgetcsv($handle, 1000, $delimiter);
                if ($first_row && count($first_row) > $max_cols) {
                    $max_cols = count($first_row);
                    $found_delimiter = $delimiter;
                }
                fclose($handle);
            }
        }

        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, $found_delimiter)) !== FALSE) {
                // Convert encoding if needed
                if ($encoding !== 'UTF-8') {
                    $row = array_map(function($cell) use ($encoding) {
                        return mb_convert_encoding($cell, 'UTF-8', $encoding);
                    }, $row);
                }

                // Clean empty cells and trim whitespace
                $row = array_map('trim', $row);

                // Skip completely empty rows
                if (!empty(array_filter($row, function($cell) { return $cell !== ''; }))) {
                    $data[] = $row;
                }
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Validate import data structure
     */
    public static function validateImportData($data, $required_columns) {
        $errors = [];

        if (empty($data)) {
            $errors[] = 'File kosong atau tidak dapat dibaca';
            return $errors;
        }

        // Check if we have header row
        $headers = $data[0];

        // Check required columns
        foreach ($required_columns as $column) {
            if (!in_array($column, $headers)) {
                $errors[] = "Kolom '$column' tidak ditemukan";
            }
        }

        return $errors;
    }

    /**
     * Process import data rows
     */
    public static function processImportRows($data, $processor_callback) {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        if (empty($data)) {
            return $results;
        }

        // First row is headers
        $headers = array_shift($data);

        foreach ($data as $index => $row) {
            $row_number = $index + 2; // +2 because we start from row 2 (after header)

            try {
                // Combine headers with row data
                $row_data = array_combine($headers, $row);

                // Process row using callback
                $result = $processor_callback($row_data, $row_number);

                if ($result['success']) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Baris $row_number: " . $result['message'];
                }

            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Baris $row_number: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Generate Excel template
     */
    public static function generateTemplate($headers, $sample_data = [], $filename = 'template.xlsx', $title = '') {
        // Check if zip extension is available for xlsx format
        if (!extension_loaded('zip')) {
            // Fallback to CSV template
            return self::generateCSVTemplate($headers, $sample_data, $filename, $title);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $currentRow = 1;

        // Add title if provided
        if (!empty($title)) {
            $sheet->setCellValue('A1', $title);
            $sheet->mergeCells('A1:' . self::getColumnLetter(count($headers)) . '1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 14,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8F5E8'],
                ],
            ]);
            $currentRow = 3;
        }

        // Add headers
        $colIndex = 0;
        foreach ($headers as $header) {
            $colLetter = self::getColumnLetter($colIndex + 1);
            $sheet->setCellValue($colLetter . $currentRow, $header);
            $colIndex++;
        }

        // Style headers
        $headerRange = 'A' . $currentRow . ':' . self::getColumnLetter(count($headers)) . $currentRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4CAF50'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $currentRow++;

        // Add sample data
        foreach ($sample_data as $row) {
            $colIndex = 0;
            foreach ($row as $cell) {
                $colLetter = self::getColumnLetter($colIndex + 1);
                $sheet->setCellValue($colLetter . $currentRow, $cell);
                $colIndex++;
            }
            $currentRow++;
        }

        // Auto-size columns
        foreach (range('A', self::getColumnLetter(count($headers))) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Generate output
        if (!str_ends_with($filename, '.xlsx') && !str_ends_with($filename, '.xls')) {
            $filename .= '.xlsx';
        }

        // Clean output buffer to prevent corruption
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Generate CSV template fallback
     */
    private static function generateCSVTemplate($headers, $sample_data = [], $filename = 'template.csv', $title = '') {
        // Ensure filename has .csv extension
        if (!str_ends_with($filename, '.csv')) {
            $filename = str_replace(['.xlsx', '.xls'], '.csv', $filename);
            if (!str_ends_with($filename, '.csv')) {
                $filename .= '.csv';
            }
        }

        // Clean output buffer to prevent corruption
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Add BOM for UTF-8
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Add title if provided
        if (!empty($title)) {
            fputcsv($output, [$title]);
            fputcsv($output, []); // Empty row
        }

        // Write headers
        fputcsv($output, $headers);

        // Write sample data
        foreach ($sample_data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Log import activity
     */
    public static function logImportActivity($type, $results, $user_id, $filename) {
        global $pdo;

        try {
            $sql = "INSERT INTO import_logs (type, filename, total_rows, success_rows, failed_rows, user_id, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $total_rows = $results['success'] + $results['failed'];
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $type,
                $filename,
                $total_rows,
                $results['success'],
                $results['failed'],
                $user_id
            ]);

            return $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Failed to log import activity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get column letter from number (A, B, C, ..., Z, AA, AB, etc.)
     */
    private static function getColumnLetter($number) {
        $letter = '';
        while ($number > 0) {
            $number--;
            $letter = chr(65 + ($number % 26)) . $letter;
            $number = intval($number / 26);
        }
        return $letter;
    }
}