<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow
{
    // This method runs for each row in the Excel file
    public function model(array $row)
    {
        // Create a new product using Excel row data
        return new Product([
            'name'  => $row['name'],  
            'price'=> $row['price'], 
            'qty'  => $row['qty'],    
        ]);
    }
}
