<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        return Client::with('bonusCard')->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Имя',
            'Телефон',
            'Бонусная карта',
            'Потрачено за всё время (руб.)',
            'Бонусные баллы',
            'Дата рождения',
            'Комментарий',
            'Дата создания',
            'Дата обновления'
        ];
    }

    public function map($client): array
    {
        return [
            $client->id,
            $client->name,
            $client->phone,
            $client->bonusCard ? $client->bonusCard->Name : '—',
            number_format($client->total_spent, 2),
            $client->bonus_points,
            $client->birth_date ? $client->birth_date->format('d.m.Y') : '—',
            $client->comment ?? '—',
            $client->created_at->format('d.m.Y H:i'),
            $client->updated_at->format('d.m.Y H:i')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Стиль для заголовков
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2c3e50']
            ]
        ]);

        // Автофильтр
        $sheet->setAutoFilter('A1:J1');

        // Выравнивание для денежных полей
        $sheet->getStyle('E2:E' . ($sheet->getHighestRow()))->getNumberFormat()
            ->setFormatCode('#,##0.00 ₽');

        return [
            // Заголовок таблицы
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => '2c3e50']
                ]
            ],
        ];
    }
}