<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ProductsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $totalRecords = 0;
    public int $successfulRecords = 0;
    public int $duplicateRecords = 0;
    public int $invalidRecords = 0;

    public array $errors = [];

    /**
     * Process every Excel row.
     */
    public function collection(Collection $rows)
    {
        $this->totalRecords = $rows->count();

        /*
        |--------------------------------------------------------------------------
        | Track duplicate names inside the uploaded Excel file
        |--------------------------------------------------------------------------
        */
        $fileProducts = [];

        foreach ($rows as $index => $row) {

            // Excel row number = collection index + heading row + 1
            $excelRow = $index + 2;

            $name = isset($row['name'])
                ? trim((string) $row['name'])
                : '';

            $price = $row['price'] ?? null;
            $qty = $row['qty'] ?? null;

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $rowErrors = [];

            if ($name === '') {
                $rowErrors[] = 'Product name is required.';
            }

            if ($price === null || $price === '') {
                $rowErrors[] = 'Price is required.';
            } elseif (!is_numeric($price)) {
                $rowErrors[] = 'Price must be numeric.';
            } elseif ((float) $price < 0) {
                $rowErrors[] = 'Price cannot be negative.';
            }

            if ($qty === null || $qty === '') {
                $rowErrors[] = 'Quantity is required.';
            } elseif (
                filter_var(
                    $qty,
                    FILTER_VALIDATE_INT
                ) === false
            ) {
                $rowErrors[] = 'Quantity must be an integer.';
            } elseif ((int) $qty < 0) {
                $rowErrors[] = 'Quantity cannot be negative.';
            }

            /*
            |--------------------------------------------------------------------------
            | Invalid row
            |--------------------------------------------------------------------------
            */

            if (!empty($rowErrors)) {
                $this->invalidRecords++;

                $this->errors[] = [
                    'row' => $excelRow,
                    'name' => $name ?: '-',
                    'reason' => implode(' ', $rowErrors),
                    'type' => 'Invalid',
                ];

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Duplicate inside uploaded file
            |--------------------------------------------------------------------------
            */

            $normalizedName = strtolower($name);

            if (isset($fileProducts[$normalizedName])) {
                $this->duplicateRecords++;

                $this->errors[] = [
                    'row' => $excelRow,
                    'name' => $name,
                    'reason' => 'Duplicate product found in uploaded file.',
                    'type' => 'Duplicate',
                ];

                continue;
            }

            $fileProducts[$normalizedName] = true;

            /*
            |--------------------------------------------------------------------------
            | Duplicate against existing database products
            |--------------------------------------------------------------------------
            */

            $existingProduct = Product::whereRaw(
                'LOWER(name) = ?',
                [$normalizedName]
            )->first();

            if ($existingProduct) {
                $this->duplicateRecords++;

                $this->errors[] = [
                    'row' => $excelRow,
                    'name' => $name,
                    'reason' => 'Product already exists in database.',
                    'type' => 'Duplicate',
                ];

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Insert valid product
            |--------------------------------------------------------------------------
            */

            Product::create([
                'name' => $name,
                'price' => (float) $price,
                'qty' => (int) $qty,
            ]);

            $this->successfulRecords++;
        }
    }

    /**
     * Return import summary.
     */
    public function getSummary(): array
    {
        if ($this->successfulRecords === $this->totalRecords) {
            $status = 'success';
        } elseif ($this->successfulRecords > 0) {
            $status = 'partial';
        } else {
            $status = 'failed';
        }

        return [
            'total' => $this->totalRecords,
            'successful' => $this->successfulRecords,
            'duplicates' => $this->duplicateRecords,
            'invalid' => $this->invalidRecords,
            'status' => $status,
            'errors' => $this->errors,
        ];
    }
}