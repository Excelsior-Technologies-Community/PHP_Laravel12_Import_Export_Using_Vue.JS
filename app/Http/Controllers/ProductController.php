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
     * Vue page load.
     */
    public function index()
    {
        return view('products');
    }

    /**
     * Fetch product list.
     */
    public function fetch()
    {
        return response()->json(
            Product::latest()->get()
        );
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
            'message' => 'Product Added Successfully'
        ]);
    }

    /**
     * Update product.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'qty' => 'required|integer|min:0',
        ]);

        $product = Product::findOrFail($id);

        $product->update($validated);

        return response()->json([
            'message' => 'Product Updated Successfully'
        ]);
    }

    /**
     * Delete product.
     */
    public function destroy($id)
    {
        Product::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Product Deleted Successfully'
        ]);
    }

    /**
     * Import products from Excel/CSV.
     *
     * Includes:
     * - Validation
     * - Duplicate detection
     * - Import summary
     * - Error reporting
     * - Import history
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
                'file_name' => $file->getClientOriginalName(),
                'total_records' => $summary['total'],
                'successful_records' => $summary['successful'],
                'duplicate_records' => $summary['duplicates'],
                'invalid_records' => $summary['invalid'],
                'status' => $summary['status'],
                'details' => json_encode(
                    $summary['errors'],
                    JSON_PRETTY_PRINT
                ),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully.',
                'summary' => $summary,
            ]);
        } catch (Throwable $e) {

            ImportHistory::create([
                'operation' => 'import',
                'file_name' => $file->getClientOriginalName(),
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
     * Export products to Excel.
     *
     * Also creates export history.
     */
    public function export()
    {
        try {

            $totalProducts = Product::count();

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
                'details' => 'Products exported successfully.',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Download Excel
            |--------------------------------------------------------------------------
            */

            return Excel::download(
                new ProductsExport,
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
            ], 500);
        }
    }

    /**
     * Fetch import/export history.
     */
    public function history()
    {
        $history = ImportHistory::latest()->get();

        return response()->json($history);
    }
}
