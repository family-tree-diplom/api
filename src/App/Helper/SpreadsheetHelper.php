<?php

namespace OpenCCK\App\Helper;

use OpenCCK\App\Service\ServiceInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;

final class SpreadsheetHelper implements ServiceInterface {
    /**
     * @var IWriter
     */
    private $writer;
    /**
     * @var Spreadsheet
     */
    private $spreadsheet;

    protected $col = 1;
    protected $row = 1;

    /**
     * @param $firstSheetTitle
     * @param $writerType
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function __construct($firstSheetTitle = 'Worksheet', $writerType = 'Xlsx') {
        $this->spreadsheet = new Spreadsheet();
        $this->writer = IOFactory::createWriter($this->spreadsheet, $writerType);
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle($firstSheetTitle);

        // $this->writer->setIncludeCharts(true);
    }

    private function cords($col = null, $row = null) {
        if (is_null($col)) {
            $col = $this->col;
        }
        if (is_null($row)) {
            $row = $this->row;
        }

        return Coordinate::stringFromColumnIndex($col) . $row;
    }

    /**
     * @param Worksheet $sheet
     * @param $cords
     * @param array $options
     */
    private function styles(&$sheet, $cords, $options = []) {
        $style = $sheet->getStyle($cords);

        $style->getAlignment()->setWrapText(true);
        $style->getAlignment()->setVertical('top');

        foreach ($options as $key => $value) {
            switch ($key) {
                case 'fill':
                    $style
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB($value);
                    break;
                case 'border':
                    $style
                        ->getBorders()
                        ->getTop()
                        ->setBorderStyle(Border::BORDER_THIN);
                    $style
                        ->getBorders()
                        ->getRight()
                        ->setBorderStyle(Border::BORDER_THIN);
                    $style
                        ->getBorders()
                        ->getBottom()
                        ->setBorderStyle(Border::BORDER_THIN);
                    $style
                        ->getBorders()
                        ->getLeft()
                        ->setBorderStyle(Border::BORDER_THIN);
                    break;
                case 'align':
                    $style->getAlignment()->setHorizontal($value);
                    break;
                case 'valign':
                    $style->getAlignment()->setVertical($value);
                    break;
                case 'color':
                    $style
                        ->getFont()
                        ->getColor()
                        ->setRGB($value);
                    break;
                case 'weight':
                    $style->getFont()->setBold($value == 'bold');
                    break;
                case 'font-size':
                    $style->getFont()->setSize($value);
                    break;
            }
        }
    }

    private function dateFormat($from, $to = null) {
        if (is_null($to)) {
            $to = $from;
        }

        $to = date_create($to);
        date_add($to, date_interval_create_from_date_string('7 days'));
        return date_format(date_create($from), 'd.m') . '-' . date_format($to, 'd.m');
    }

    public function addSheet($name) {
        $this->spreadsheet->addSheet(new Worksheet($this->spreadsheet, $name));
        $this->spreadsheet->setActiveSheetIndex($this->spreadsheet->getActiveSheetIndex() + 1);
        $this->row = 1;
        $this->col = 1;
    }

    public function save($filePath) {
        $this->spreadsheet->setActiveSheetIndex(0);

        if (is_file($filePath)) {
            unlink($filePath);
        }
        $this->writer->save($filePath);
    }

    public function output($fileName = 'details') {
        $this->spreadsheet->setActiveSheetIndex(0);

        ob_start();
        $this->writer->save('php://output');

        return ob_get_clean();
    }

    /**
     * @param $title
     * @param $rows
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function addIntroBlock($title = '', $rows = []) {
        $sheet = $this->spreadsheet->getActiveSheet();

        // заголовок
        $pRange = $this->cords() . ':' . $this->cords($this->col + 3);
        $sheet->mergeCells($pRange);
        $this->styles($sheet, $pRange, ['fill' => '34A853', 'border' => true, 'color' => 'FFFFFF', 'font-size' => 18]);
        $sheet->setCellValue($this->cords(), $title);
        $this->row++;

        foreach (array_chunk($rows, 2) as $chunk) {
            $this->styles($sheet, $this->cords(), ['fill' => 'D1F1DA', 'border' => true]);
            $sheet->setCellValue($this->cords(), $chunk[0][0]);

            $this->styles($sheet, $this->cords($this->col + 1), ['border' => true]);
            $sheet->setCellValue($this->cords($this->col + 1), $chunk[0][1]);

            $this->styles($sheet, $this->cords($this->col + 2), ['fill' => 'D1F1DA', 'border' => true]);
            $sheet->setCellValue($this->cords($this->col + 2), $chunk[1][0]);

            $this->styles($sheet, $this->cords($this->col + 3), ['border' => true]);
            $sheet->setCellValue($this->cords($this->col + 3), $chunk[1][1]);
            $this->row++;
        }

        $this->row++;
    }

    /**
     * @param $title
     * @param $description
     * @param $headers
     * @param $items
     * @param $showTotal
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function addTable($title = '', $description = '', $headers = [], $items = [], $showTotal = true) {
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setShowSummaryBelow(false);
        $sheet->setShowSummaryRight(false);

        for ($j = 1; $j <= count($headers); $j++) {
            $sheet
                ->getColumnDimension(Coordinate::stringFromColumnIndex($j))
                ->setWidth(isset($headers[$j - 1]['width']) ? $headers[$j - 1]['width'] : 20);
        }
        //->setAutoSize(true);

        // шапка
        if ($title) {
            $pRange = $this->cords() . ':' . $this->cords($this->col + count($headers) - 1);
            $sheet->mergeCells($pRange);
            $this->styles($sheet, $pRange, [
                'fill' => '34A853',
                'border' => true,
                'color' => 'FFFFFF',
                'font-size' => 18,
            ]);
            $sheet->setCellValue($this->cords(), $title);
            $this->row++;
        }

        // описание
        if ($description) {
            $sheet
                ->getStyle($pRange)
                ->getBorders()
                ->getBottom()
                ->setBorderStyle(Border::BORDER_NONE);

            $pRange = $this->cords() . ':' . $this->cords($this->col + count($headers) - 1);
            $sheet->mergeCells($pRange);
            $this->styles($sheet, $pRange, [
                'fill' => '34A853',
                'border' => true,
                'color' => 'FFFFFF',
                'font-size' => 12,
            ]);
            $sheet->setCellValue($this->cords(), $description);

            $sheet
                ->getStyle($pRange)
                ->getBorders()
                ->getTop()
                ->setBorderStyle(Border::BORDER_NONE);

            $this->row++;
        }

        // заголовки
        foreach ($headers as $i => $header) {
            $this->styles($sheet, $this->cords($this->col + $i), [
                'fill' => isset($header['fill']) ? $header['fill'] : '34A853',
                'color' => isset($header['color']) ? $header['color'] : 'FFFFFF',
                'border' => true,
                'align' => 'center',
                'valign' => 'center',
            ]);
            $sheet->setCellValue($this->cords($this->col + $i), $header['name']);
            if (isset($header['comment']) && $header['comment']) {
                $sheet
                    ->getComment($this->cords($this->col + $i))
                    ->getText()
                    ->createTextRun($header['comment']);
            }

            if (isset($header['level'])) {
                $sheet
                    ->getColumnDimension(Coordinate::stringFromColumnIndex($this->col + $i))
                    ->setOutlineLevel($header['level'])
                    ->setVisible(isset($header['collapsed']) ? !$header['collapsed'] : false)
                    ->setCollapsed(isset($header['collapsed']) ? !!$header['collapsed'] : true);
            }
        }
        $sheet->getRowDimension($this->row)->setOutlineLevel(0);
        $sheet->setAutoFilter($this->cords($this->col) . ':' . $this->cords($this->col + count($headers) - 1));
        $this->row++;

        // данные
        $total = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $item = (object) $item;
            }
            foreach ($headers as $i => $header) {
                $this->styles($sheet, $this->cords($this->col + $i), ['border' => true]);

                switch ($header['type'] ?? '') {
                    case 'string':
                        $sheet->setCellValueExplicit(
                            $this->cords($this->col + $i),
                            $item->{$header['field']},
                            DataType::TYPE_STRING
                        );
                        break;
                    default:
                        $sheet->setCellValue($this->cords($this->col + $i), $item->{$header['field']});
                }

                if (isset($header['total']) && $header['total']) {
                    if (!isset($total[$header['field']])) {
                        $total[$header['field']] = 0;
                    }
                    $total[$header['field']] += $item->{$header['field']};
                }

                if (isset($header['count']) && $header['count']) {
                    if (!isset($total[$header['field']])) {
                        $total[$header['field']] = 0;
                    }
                    $total[$header['field']] += 1;
                }
            }

            $this->row++;

            // вывод группы данных
            if (isset($item->{'_group'})) {
                foreach ($item->{'_group'} as $groupItem) {
                    foreach ($headers as $i => $header) {
                        if ($header['field'] == 'range') {
                            $this->styles($sheet, $this->cords($this->col + $i), [
                                'border' => true,
                                'fill' => 'FEF2CD',
                            ]);
                            $sheet->setCellValue($this->cords($this->col + $i), $groupItem->range);
                        } else {
                            $this->styles($sheet, $this->cords($this->col + $i), ['border' => true]);
                            if ($header['total']) {
                                $sheet->setCellValue($this->cords($this->col + $i), $groupItem->{$header['field']});
                            }
                            if ($header['count']) {
                                $sheet->setCellValue($this->cords($this->col + $i), $groupItem->{$header['field']});
                            }
                        }
                    }
                    $sheet
                        ->getRowDimension($this->row)
                        ->setOutlineLevel(1)
                        ->setVisible(false)
                        ->setCollapsed(true);

                    $this->row++;
                }
            }

            // вывод групп данных
            if (isset($item->{'_groups'})) {
                foreach ($item->{'_groups'} as $range) {
                    foreach ($headers as $i => $header) {
                        if ($header['field'] == 'range') {
                            $this->styles($sheet, $this->cords($this->col + $i), [
                                'border' => true,
                                'fill' => 'FEF2CD',
                            ]);
                            $sheet->setCellValue($this->cords($this->col + $i), $range['label']);
                        } else {
                            $this->styles($sheet, $this->cords($this->col + $i), ['border' => true]);
                        }
                    }
                    $sheet
                        ->getRowDimension($this->row)
                        ->setOutlineLevel(1)
                        ->setVisible(false)
                        ->setCollapsed(true);

                    $this->row++;

                    // вывод данных группы
                    $groupTotal = []; // локальный total и count для группы данных
                    $groupRow = $this->row;
                    foreach ($range['items'] as $date => $item) {
                        foreach ($headers as $i => $header) {
                            if ($header['field'] == 'range') {
                                $this->styles($sheet, $this->cords($this->col + $i), [
                                    'border' => true,
                                    'fill' => 'DAF1F3',
                                ]);
                                $sheet->setCellValue($this->cords($this->col + $i), $date);
                            } else {
                                $this->styles($sheet, $this->cords($this->col + $i), ['border' => true]);
                                if ($header['total']) {
                                    // для подгрупп отображаем данные только с total
                                    $sheet->setCellValue($this->cords($this->col + $i), $item->{$header['field']});
                                    if (!isset($groupTotal[$header['field']])) {
                                        $groupTotal[$header['field']] = 0;
                                    }
                                    $groupTotal[$header['field']] += $item->{$header['field']};
                                }
                                if ($header['count']) {
                                    // для подгрупп отображаем данные только с count
                                    $sheet->setCellValue($this->cords($this->col + $i), $item->{$header['field']});
                                    if (!isset($groupTotal[$header['field']])) {
                                        $groupTotal[$header['field']] = 0;
                                    }
                                    $groupTotal[$header['field']] += 1;
                                }
                            }
                        }
                        $sheet
                            ->getRowDimension($this->row)
                            ->setOutlineLevel(2)
                            ->setVisible(false)
                            ->setCollapsed(true);
                        $this->row++;
                    }

                    // вывод total группы данных
                    foreach ($headers as $i => $header) {
                        if ($header['total'] || $header['count']) {
                            $sheet->setCellValue(
                                $this->cords($this->col + $i, $groupRow - 1),
                                $groupTotal[$header['field']]
                            );
                        } else {
                            if ($header['field'] != 'range') {
                                $sheet->mergeCells(
                                    $this->cords($this->col + $i, $groupRow) .
                                        ':' .
                                        $this->cords($this->col + $i, $groupRow + count($range['items']) - 1)
                                );
                            }
                        }
                    }
                }
            }
        }

        // вывод подсчёта сумм
        if ($showTotal) {
            $this->styles($sheet, $this->cords($this->col), [
                'border' => true,
                'fill' => 'F2F2F2',
                'align' => 'right',
                'weight' => 'bold',
            ]);
            $sheet->setCellValue($this->cords($this->col), 'Сумма:');
            $merged = false;
            foreach ($headers as $i => $header) {
                $this->styles($sheet, $this->cords($this->col + $i), ['border' => true, 'fill' => 'F2F2F2']);
                if ($header['total'] || $header['count']) {
                    if (!$merged) {
                        $sheet->mergeCells($this->cords($this->col) . ':' . $this->cords($this->col + $i - 1));
                        $merged = true;
                    }
                    $sheet->setCellValue($this->cords($this->col + $i), $total[$header['field']]);
                }
            }
        }
    }
}
