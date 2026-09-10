<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Fetch filtered products for Excel export.
     */
    public function query()
    {
        $query = Product::query();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Minimum Price
        |--------------------------------------------------------------------------
        */

        if (
            isset($this->filters['min_price']) &&
            $this->filters['min_price'] !== ''
        ) {
            $query->where(
                'price',
                '>=',
                (float) $this->filters['min_price']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Maximum Price
        |--------------------------------------------------------------------------
        */

        if (
            isset($this->filters['max_price']) &&
            $this->filters['max_price'] !== ''
        ) {
            $query->where(
                'price',
                '<=',
                (float) $this->filters['max_price']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Stock Status
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['stock_status'])) {

            switch ($this->filters['stock_status']) {

                case 'in_stock':
                    $query->where('qty', '>', 5);
                    break;

                case 'low_stock':
                    $query->where('qty', '>', 0)
                        ->where('qty', '<=', 5);
                    break;

                case 'out_of_stock':
                    $query->where('qty', '=', 0);
                    break;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'id',
            'name',
            'price',
            'qty',
            'created_at',
        ];

        $sortBy = in_array(
            $this->filters['sort_by'] ?? '',
            $allowedSorts
        )
            ? $this->filters['sort_by']
            : 'id';

        $sortDirection =
            ($this->filters['sort_direction'] ?? 'asc') === 'desc'
                ? 'desc'
                : 'asc';

        return $query->orderBy(
            $sortBy,
            $sortDirection
        );
    }

    /**
     * Excel headings.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Price',
            'Qty',
            'Stock Status',
            'Created Date',
        ];
    }

    /**
     * Map product to Excel row.
     */
    public function map($product): array
    {
        if ($product->qty == 0) {
            $stockStatus = 'Out of Stock';
        } elseif ($product->qty <= 5) {
            $stockStatus = 'Low Stock';
        } else {
            $stockStatus = 'In Stock';
        }

        return [
            $product->id,
            $product->name,
            $product->price,
            $product->qty,
            $stockStatus,
            optional($product->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}