<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ImportHistory;
use Illuminate\Http\Request;
use App\Imports\ProductsImport;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProductController extends Controller
{
    /**
     * Low stock threshold.
     */
    private const LOW_STOCK_LIMIT = 5;

    /**
     * Vue page load.
     */
    public function index()
    {
        return view('products');
    }

    /**
     * Fetch products with:
     *
     * 1. Search
     * 2. Sorting
     * 3. Pagination
     * 4. Price filter
     * 5. Stock filter
     * 6. Dashboard statistics
     */
    public function fetch(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        $search = trim(
            (string) $request->input('search', '')
        );

        /*
        |--------------------------------------------------------------------------
        | Price filters
        |--------------------------------------------------------------------------
        */

        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');

        /*
        |--------------------------------------------------------------------------
        | Stock filter
        |--------------------------------------------------------------------------
        */

        $stockStatus = $request->input(
            'stock_status',
            ''
        );

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

        $sortBy = $request->input(
            'sort_by',
            'id'
        );

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $sortDirection = $request->input(
            'sort_direction',
            'asc'
        );

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        /*
        |--------------------------------------------------------------------------
        | Product Query
        |--------------------------------------------------------------------------
        */

        $query = Product::query();

        /*
        | Search
        */

        if ($search !== '') {

            $query->where(function ($q) use ($search) {

                $q->where(
                    'name',
                    'like',
                    '%' . $search . '%'
                );

                if (is_numeric($search)) {
                    $q->orWhere(
                        'id',
                        (int) $search
                    );
                }
            });
        }

        /*
        | Minimum price
        */

        if (
            $minPrice !== null &&
            $minPrice !== ''
        ) {
            $query->where(
                'price',
                '>=',
                (float) $minPrice
            );
        }

        /*
        | Maximum price
        */

        if (
            $maxPrice !== null &&
            $maxPrice !== ''
        ) {
            $query->where(
                'price',
                '<=',
                (float) $maxPrice
            );
        }

        /*
        | Stock filter
        */

        switch ($stockStatus) {

            case 'in_stock':

                $query->where(
                    'qty',
                    '>',
                    self::LOW_STOCK_LIMIT
                );

                break;

            case 'low_stock':

                $query->where(
                    'qty',
                    '>',
                    0
                )->where(
                    'qty',
                    '<=',
                    self::LOW_STOCK_LIMIT
                );

                break;

            case 'out_of_stock':

                $query->where(
                    'qty',
                    '=',
                    0
                );

                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = (int) $request->input(
            'per_page',
            5
        );

        if (!in_array($perPage, [5, 10, 20, 50])) {
            $perPage = 5;
        }

        $products = $query
            ->orderBy(
                $sortBy,
                $sortDirection
            )
            ->paginate($perPage)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Dashboard Statistics
        |--------------------------------------------------------------------------
        */

        $totalProducts = Product::count();

        $totalQuantity = Product::sum('qty');

        $inventoryValue = Product::selectRaw(
            'SUM(price * qty) as total'
        )->value('total') ?? 0;

        $lowStockProducts = Product::where(
            'qty',
            '>',
            0
        )->where(
            'qty',
            '<=',
            self::LOW_STOCK_LIMIT
        )->count();

        $outOfStockProducts = Product::where(
            'qty',
            '=',
            0
        )->count();

        $inStockProducts = Product::where(
            'qty',
            '>',
            self::LOW_STOCK_LIMIT
        )->count();

        return response()->json([
            'success' => true,

            'products' => $products,

            'filters' => [
                'search' => $search,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'stock_status' => $stockStatus,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => $perPage,
            ],

            'statistics' => [
                'total_products' => $totalProducts,
                'total_quantity' => $totalQuantity,
                'inventory_value' => round(
                    (float) $inventoryValue,
                    2
                ),
                'in_stock' => $inStockProducts,
                'low_stock' => $lowStockProducts,
                'out_of_stock' => $outOfStockProducts,
            ],
        ]);
    }


    /**
     * Store product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'qty' => 'required|integer|min:0',
        ]);

        Product::create($validated);

        return response()->json([
            'message' => 'Product Added Successfully',
        ]);
    }

    /**
     * Update product.
     */
    public function update(
        Request $request,
        $id
    ) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'qty' => 'required|integer|min:0',
        ]);

        $product = Product::findOrFail($id);

        $product->update($validated);

        return response()->json([
            'message' => 'Product Updated Successfully',
        ]);
    }

    /**
     * Delete single product.
     */
    public function destroy($id)
    {
        Product::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Product Deleted Successfully',
        ]);
    }

    /**
     * Bulk delete products.
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
        ]);

        $deletedCount = Product::whereIn(
            'id',
            $validated['ids']
        )->delete();

        return response()->json([
            'success' => true,
            'message' => $deletedCount .
                ' product(s) deleted successfully.',
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * Import products from Excel/CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv|max:10240',
        ]);

        $file = $request->file('file');

        try {

            $import = new ProductsImport();

            Excel::import(
                $import,
                $file
            );

            $summary = $import->getSummary();

            ImportHistory::create([
                'operation' => 'import',
                'file_name' =>
                    $file->getClientOriginalName(),
                'total_records' =>
                    $summary['total'],
                'successful_records' =>
                    $summary['successful'],
                'duplicate_records' =>
                    $summary['duplicates'],
                'invalid_records' =>
                    $summary['invalid'],
                'status' =>
                    $summary['status'],
                'details' =>
                    json_encode(
                        $summary['errors'],
                        JSON_PRETTY_PRINT
                    ),
            ]);

            return response()->json([
                'success' => true,
                'message' =>
                    'Import completed successfully.',
                'summary' => $summary,
            ]);

        } catch (Throwable $e) {

            ImportHistory::create([
                'operation' => 'import',
                'file_name' =>
                    $file->getClientOriginalName(),
                'total_records' => 0,
                'successful_records' => 0,
                'duplicate_records' => 0,
                'invalid_records' => 0,
                'status' => 'failed',
                'details' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed.',
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ], 500);
        }
    }

    /**
     * Export filtered products to Excel.
     */
    public function export(Request $request)
    {
        try {

            $filters = [
                'search' =>
                    trim(
                        (string) $request->input(
                            'search',
                            ''
                        )
                    ),

                'min_price' =>
                    $request->input(
                        'min_price'
                    ),

                'max_price' =>
                    $request->input(
                        'max_price'
                    ),

                'stock_status' =>
                    $request->input(
                        'stock_status',
                        ''
                    ),

                'sort_by' =>
                    $request->input(
                        'sort_by',
                        'id'
                    ),

                'sort_direction' =>
                    $request->input(
                        'sort_direction',
                        'asc'
                    ),
            ];

            /*
            |--------------------------------------------------------------------------
            | Count filtered records
            |--------------------------------------------------------------------------
            */

            $exportQuery = Product::query();

            if ($filters['search'] !== '') {

                $search = $filters['search'];

                $exportQuery->where(function ($q) use ($search) {

                    $q->where(
                        'name',
                        'like',
                        '%' . $search . '%'
                    );

                    if (is_numeric($search)) {
                        $q->orWhere(
                            'id',
                            (int) $search
                        );
                    }
                });
            }

            if (
                $filters['min_price'] !== null &&
                $filters['min_price'] !== ''
            ) {
                $exportQuery->where(
                    'price',
                    '>=',
                    (float) $filters['min_price']
                );
            }

            if (
                $filters['max_price'] !== null &&
                $filters['max_price'] !== ''
            ) {
                $exportQuery->where(
                    'price',
                    '<=',
                    (float) $filters['max_price']
                );
            }

            switch ($filters['stock_status']) {

                case 'in_stock':

                    $exportQuery->where(
                        'qty',
                        '>',
                        self::LOW_STOCK_LIMIT
                    );

                    break;

                case 'low_stock':

                    $exportQuery->where(
                        'qty',
                        '>',
                        0
                    )->where(
                        'qty',
                        '<=',
                        self::LOW_STOCK_LIMIT
                    );

                    break;

                case 'out_of_stock':

                    $exportQuery->where(
                        'qty',
                        '=',
                        0
                    );

                    break;
            }

            $totalProducts = $exportQuery->count();

            /*
            |--------------------------------------------------------------------------
            | Save export history
            |--------------------------------------------------------------------------
            */

            ImportHistory::create([
                'operation' => 'export',
                'file_name' => 'products.xlsx',
                'total_records' => $totalProducts,
                'successful_records' => $totalProducts,
                'duplicate_records' => 0,
                'invalid_records' => 0,
                'status' => 'success',
                'details' =>
                    'Filtered products exported successfully.',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Download Excel
            |--------------------------------------------------------------------------
            */

            return Excel::download(
                new ProductsExport($filters),
                'products.xlsx'
            );

        } catch (Throwable $e) {

            ImportHistory::create([
                'operation' => 'export',
                'file_name' => 'products.xlsx',
                'total_records' => 0,
                'successful_records' => 0,
                'duplicate_records' => 0,
                'invalid_records' => 0,
                'status' => 'failed',
                'details' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch import/export history
     * with search and filters.
     */
    public function history(Request $request)
    {
        $query = ImportHistory::query();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        $search = trim(
            (string) $request->input(
                'search',
                ''
            )
        );

        if ($search !== '') {

            $query->where(function ($q) use ($search) {

                $q->where(
                    'file_name',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'status',
                    'like',
                    '%' . $search . '%'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Operation filter
        |--------------------------------------------------------------------------
        */

        $operation = $request->input(
            'operation',
            ''
        );

        if (
            in_array(
                $operation,
                ['import', 'export']
            )
        ) {
            $query->where(
                'operation',
                $operation
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status filter
        |--------------------------------------------------------------------------
        */

        $status = $request->input(
            'status',
            ''
        );

        if (
            in_array(
                $status,
                ['success', 'partial', 'failed']
            )
        ) {
            $query->where(
                'status',
                $status
            );
        }

        return response()->json(
            $query
                ->latest()
                ->get()
        );
    }
}