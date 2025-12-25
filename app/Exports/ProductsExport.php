<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    // Fetch all products from database
    public function collection()
    {
        return Product::all();
    }

    // Define Excel header row titles
    public function headings(): array
    {
        return [
            'Name',
            'Price',
            'Qty',
        ];
    }

    // Map product fields to Excel columns
    public function map($product): array
    {
        return [
            $product->name,   
            $product->price,  
            $product->qty,    
        ];
    }
}
