# PHP_Laravel12_Import_Export_Using_Vue.JS

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel">
  <img src="https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js">
  <img src="https://img.shields.io/badge/Vite-Build-blueviolet?style=for-the-badge&logo=vite">
  <img src="https://img.shields.io/badge/Excel-Import%20%26%20Export-success?style=for-the-badge">
  <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql">
</p>

---

##  Overview

 It shows how to build a real-world admin-style system where products can be:
- Created, updated, and deleted using Vue.js CRUD UI
- Imported in bulk from Excel files (CSV / XLSX)
- Exported back to Excel directly from Laravel



---

##  Features

- Laravel 12
- Vue 3 (Vite)
- Axios
- Product CRUD
- Excel Import (CSV / XLSX)
- Excel Export
- MySQL Database

---

##  Folder Structure

```text
laravel-vue-import-export/
│
├── app/
│   ├── Models/Product.php
│   ├── Imports/ProductsImport.php
│   ├── Exports/ProductsExport.php
│   └── Http/Controllers/ProductController.php
│
├── database/migrations/create_products_table.php
│
├── resources/
│   ├── views/products.blade.php
│   └── js/
│       ├── app.js
│       └── components/ProductCrud.vue
│
├── routes/web.php
└── README.md
```

---

##  STEP 1: Laravel Installation

```bash
composer create-project laravel/laravel laravel-vue-import-export

php artisan serve
```

---

##  STEP 2: Database Configuration

.env

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=imp_exp
DB_USERNAME=root
DB_PASSWORD=
```

Create DB:

```sql
CREATE DATABASE imp_exp;
```

---

##  STEP 3: Install Vue + Vite

```bash
npm install

npm install vue axios

npm install @vitejs/plugin-vue

npm run dev
```

---

##  STEP 4: Configure Vite

vite.config.js

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
});
```

---

##  STEP 5: Install Excel Package

```bash
composer require maatwebsite/excel
```

---

##  STEP 6: Model & Migration

```bash
php artisan make:model Product -m
```

Migration:

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->decimal('price',10,2);
    $table->integer('qty');
    $table->timestamps();
});
```

```bash
php artisan migrate
```

Model:

```php
class Product extends Model
{
    protected $fillable = ['name','price','qty'];
}
```

---

##  STEP 7: Import & Export Classes

```bash
php artisan make:import ProductsImport
php artisan make:export ProductsExport
```

ProductsImport.php

```php
class ProductsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Product([
            'name'=>$row['name'],
            'price'=>$row['price'],
            'qty'=>$row['qty'],
        ]);
    }
}
```

ProductsExport.php

```php
class ProductsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Product::all();
    }

    public function headings(): array
    {
        return ['Name','Price','Qty'];
    }
}
```

---

##  STEP 8: Controller
app/Http/Controllers/ProductController.php
```bash
php artisan make:controller ProductController
```

```php
class ProductController extends Controller
{
    public function index()
    {
        return view('products');
    }

    public function fetch()
    {
        return response()->json(Product::latest()->get());
    }

    public function store(Request $request)
    {
        Product::create($request->all());
        return response()->json(['msg'=>'Added']);
    }

    public function update(Request $request,$id)
    {
        Product::findOrFail($id)->update($request->all());
        return response()->json(['msg'=>'Updated']);
    }

    public function destroy($id)
    {
        Product::findOrFail($id)->delete();
        return response()->json(['msg'=>'Deleted']);
    }

    public function import(Request $request)
    {
        Excel::import(new ProductsImport,$request->file('file'));
        return response()->json(['msg'=>'Imported']);
    }

    public function export()
    {
        return Excel::download(new ProductsExport,'products.xlsx');
    }
}
```

---

## STEP 9: Routes
routes/web.php
```php
Route::get('/products',[ProductController::class,'index']);
Route::get('/products/list',[ProductController::class,'fetch']);
Route::post('/products/store',[ProductController::class,'store']);
Route::post('/products/update/{id}',[ProductController::class,'update']);
Route::delete('/products/delete/{id}',[ProductController::class,'destroy']);
Route::post('/products/import',[ProductController::class,'import']);
Route::get('/products/export',[ProductController::class,'export']);
```

---

##  STEP 10: Blade File
resources/views/products.blade.php
```blade
<!DOCTYPE html>
<html>
<head>
    <title>Products</title>
    @vite('resources/js/app.js')
</head>
<body>
    <div id="app"></div>
</body>
</html>
```

---

##  STEP 11: Vue App

resources/js/app.js

```js
import { createApp } from 'vue';
import ProductCrud from './components/ProductCrud.vue';

createApp(ProductCrud).mount('#app');
```

---

##  STEP 12: ProductCrud.vue 
 Resources/js/components/ProductCrud.vue
```vue
<template>
<div>
<h3>Product CRUD</h3>

<input v-model="form.name" placeholder="Name">
<input v-model="form.price" placeholder="Price">
<input v-model="form.qty" placeholder="Qty">
<button @click="save">Save</button>

<input type="file" @change="importFile">
<button @click="exportExcel">Export</button>

<table border="1">
<tr>
<th>Name</th><th>Price</th><th>Qty</th><th>Action</th>
</tr>
<tr v-for="p in products" :key="p.id">
<td>{{p.name}}</td>
<td>{{p.price}}</td>
<td>{{p.qty}}</td>
<td>
<button @click="edit(p)">Edit</button>
<button @click="del(p.id)">Delete</button>
</td>
</tr>
</table>
</div>
</template>

<script>
import axios from 'axios';

export default {
data(){
return{
products:[],
form:{id:null,name:'',price:'',qty:''}
}
},
mounted(){
this.load();
},
methods:{
load(){
axios.get('/products/list').then(res=>this.products=res.data);
},
save(){
if(this.form.id){
axios.post('/products/update/'+this.form.id,this.form).then(this.load);
}else{
axios.post('/products/store',this.form).then(this.load);
}
this.form={id:null,name:'',price:'',qty:''};
},
edit(p){
this.form=p;
},
del(id){
axios.delete('/products/delete/'+id).then(this.load);
},
importFile(e){
let f=new FormData();
f.append('file',e.target.files[0]);
axios.post('/products/import',f).then(this.load);
},
exportExcel(){
window.location='/products/export';
}
}
}
</script>
```
##  STEP 13: Excel File Format (IMPORTANT)

###  Required Excel Headers

```text
name | price | qty
```

###  Example Excel Data

```text
name    | price | qty
---------------------
Mobile  | 46000 | 1
Laptop  | 75000 | 2
```
<img width="212" height="109" alt="Screenshot 2025-12-25 122107" src="https://github.com/user-attachments/assets/41e72a8e-963c-42d4-a55d-e34b9c0128d1" />

---

##  STEP 14: Run Project

```bash
php artisan serve

npm run dev
```

Open:
http://127.0.0.1:8000/products

---
## OUTPUT:-

INDEX:-

<img width="1313" height="717" alt="Screenshot 2025-12-25 122202" src="https://github.com/user-attachments/assets/0cb9df34-e829-4861-9c5e-5a150849ce11" />

IMPORT DATA:-

<img width="1299" height="864" alt="Screenshot 2025-12-25 122123" src="https://github.com/user-attachments/assets/143631d5-516c-435f-87d8-c26a8bb894d7" />
