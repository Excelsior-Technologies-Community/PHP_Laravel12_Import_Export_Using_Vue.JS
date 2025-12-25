<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Imports\ProductsImport;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    /**
     * Vue page load
     */
    public function index()
    {
        return view('products'); // Vue mount page
    }

    /**
     * Fetch products (READ)
     */
    public function fetch()
    {
        return response()->json(
            Product::latest()->get()
        );
    }

    /**
     * Store product (CREATE)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required',
            'price' => 'required|numeric',
            'qty'   => 'required|integer',
        ]);

        Product::create($request->all());

        return response()->json([
            'message' => 'Product Added Successfully'
        ]);
    }

    /**
     * Update product (UPDATE)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'  => 'required',
            'price' => 'required|numeric',
            'qty'   => 'required|integer',
        ]);

        $product = Product::findOrFail($id);
        $product->update($request->all());

        return response()->json([
            'message' => 'Product Updated Successfully'
        ]);
    }

    /**
     * Delete product (DELETE)
     */
    public function destroy($id)
    {
        Product::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Product Deleted Successfully'
        ]);
    }

    /**
     * Import products (Excel)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv'
        ]);

        Excel::import(new ProductsImport, $request->file('file'));

        return response()->json([
            'message' => 'Products Imported Successfully'
        ]);
    }

    /**
     * Export products (Excel)
     */
    public function export()
    {
        return Excel::download(
            new ProductsExport,
            'products.xlsx'
        );
    }
}
