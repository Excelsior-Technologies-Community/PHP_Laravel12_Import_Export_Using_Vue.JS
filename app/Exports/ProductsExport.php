<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Fetch all products.
     */
    public function collection()
    {
        return Product::latest()->get();
    }

    /**
     * Excel headings.
     */
    public function headings(): array
    {
        return [
            'Name',
            'Price',
            'Qty',
        ];
    }

    /**
     * Map database fields to Excel columns.
     */
    public function map($product): array
    {
        return [
            $product->name,
            $product->price,
            $product->qty,
        ];
    }
}