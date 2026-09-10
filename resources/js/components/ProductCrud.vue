<template>

  <div class="container">

    <!-- =========================================================
         PAGE TITLE
    ========================================================== -->

    <h2 class="title">
      Product Import / Export Manager
    </h2>


    <!-- =========================================================
         DASHBOARD STATISTICS
    ========================================================== -->

    <div class="stats-grid">

      <div class="stat-card total-card">
        <span>Total Products</span>
        <strong>
          {{ statistics.total_products }}
        </strong>
      </div>

      <div class="stat-card quantity-card">
        <span>Total Quantity</span>
        <strong>
          {{ statistics.total_quantity }}
        </strong>
      </div>

      <div class="stat-card value-card">
        <span>Inventory Value</span>
        <strong>
          ₹ {{ formatNumber(statistics.inventory_value) }}
        </strong>
      </div>

      <div class="stat-card stock-card">
        <span>In Stock</span>
        <strong>
          {{ statistics.in_stock }}
        </strong>
      </div>

      <div class="stat-card low-card">
        <span>Low Stock</span>
        <strong>
          {{ statistics.low_stock }}
        </strong>
      </div>

      <div class="stat-card out-card">
        <span>Out of Stock</span>
        <strong>
          {{ statistics.out_of_stock }}
        </strong>
      </div>

    </div>


    <!-- =========================================================
         ADD / EDIT PRODUCT
    ========================================================== -->

    <div class="card">

      <h3>
        {{ editId ? 'Edit Product' : 'Add Product' }}
      </h3>

      <div class="form-grid">

        <input
          type="text"
          v-model="form.name"
          placeholder="Product Name"
        >

        <input
          type="number"
          v-model="form.price"
          placeholder="Price"
          min="0"
        >

        <input
          type="number"
          v-model="form.qty"
          placeholder="Qty"
          min="0"
        >

      </div>

      <div class="btn-group">

        <button
          class="btn primary"
          @click="saveProduct"
        >
          {{ editId ? 'Update' : 'Add' }}
        </button>

        <button
          v-if="editId"
          class="btn secondary"
          @click="resetForm"
        >
          Cancel
        </button>

      </div>

      <p
        v-if="message"
        class="msg-success"
      >
        {{ message }}
      </p>

    </div>


    <!-- =========================================================
         IMPORT / EXPORT
    ========================================================== -->

    <div class="card">

      <h3>
        Import / Export
      </h3>

      <div class="import-box">

        <input
          ref="fileInput"
          type="file"
          accept=".xlsx,.csv"
          @change="handleFile"
        >

        <button
          class="btn success"
          @click="importExcel"
          :disabled="importing"
        >
          {{
            importing
              ? 'Importing...'
              : 'Import Excel'
          }}
        </button>

        <button
          class="btn info"
          @click="exportExcel"
          :disabled="exporting"
        >
          {{
            exporting
              ? 'Exporting...'
              : 'Export Filtered Excel'
          }}
        </button>

      </div>

      <p class="help-text">
        Export respects your current search, filters and sorting.
      </p>

    </div>


    <!-- =========================================================
         IMPORT SUMMARY
    ========================================================== -->

    <div
      v-if="importSummary"
      class="card summary-card"
    >

      <h3>
        Import Summary
      </h3>

      <div class="summary-grid">

        <div class="summary-box">
          <span>Total Rows</span>
          <strong>
            {{ importSummary.total }}
          </strong>
        </div>

        <div class="summary-box success-box">
          <span>Imported</span>
          <strong>
            {{ importSummary.successful }}
          </strong>
        </div>

        <div class="summary-box duplicate-box">
          <span>Duplicates</span>
          <strong>
            {{ importSummary.duplicates }}
          </strong>
        </div>

        <div class="summary-box invalid-box">
          <span>Invalid Rows</span>
          <strong>
            {{ importSummary.invalid }}
          </strong>
        </div>

      </div>

      <div
        v-if="importSummary.errors.length"
        class="error-section"
      >

        <h4>
          Import Errors / Skipped Rows
        </h4>

        <table class="error-table">

          <thead>

            <tr>
              <th>Excel Row</th>
              <th>Product</th>
              <th>Type</th>
              <th>Reason</th>
            </tr>

          </thead>

          <tbody>

            <tr
              v-for="error in importSummary.errors"
              :key="error.row + error.reason"
            >

              <td>
                {{ error.row }}
              </td>

              <td>
                {{ error.name }}
              </td>

              <td>

                <span
                  :class="[
                    'error-badge',
                    error.type === 'Duplicate'
                      ? 'duplicate-badge'
                      : 'invalid-badge'
                  ]"
                >
                  {{ error.type }}
                </span>

              </td>

              <td>
                {{ error.reason }}
              </td>

            </tr>

          </tbody>

        </table>

      </div>

    </div>


    <!-- =========================================================
         PRODUCT FILTERS
    ========================================================== -->

    <div class="card">

      <div class="section-header">

        <h3>
          Product Filters
        </h3>

        <button
          class="btn secondary"
          @click="clearFilters"
        >
          Clear Filters
        </button>

      </div>


      <div class="filter-grid">

        <!-- Search -->

        <div class="filter-field">

          <label>
            🔎 Search
          </label>

          <input
            type="text"
            v-model="filters.search"
            @input="debouncedLoadProducts"
            placeholder="Search product name or ID"
          >

        </div>


        <!-- Minimum Price -->

        <div class="filter-field">

          <label>
            Min Price
          </label>

          <input
            type="number"
            v-model="filters.min_price"
            @change="applyFilters"
            min="0"
            placeholder="Min price"
          >

        </div>


        <!-- Maximum Price -->

        <div class="filter-field">

          <label>
            Max Price
          </label>

          <input
            type="number"
            v-model="filters.max_price"
            @change="applyFilters"
            min="0"
            placeholder="Max price"
          >

        </div>


        <!-- Stock -->

        <div class="filter-field">

          <label>
            Stock Status
          </label>

          <select
            v-model="filters.stock_status"
            @change="applyFilters"
          >

            <option value="">
              All Stock
            </option>

            <option value="in_stock">
              In Stock
            </option>

            <option value="low_stock">
              Low Stock
            </option>

            <option value="out_of_stock">
              Out of Stock
            </option>

          </select>

        </div>


        <!-- Sort By -->

        <div class="filter-field">

          <label>
            Sort By
          </label>

          <select
            v-model="filters.sort_by"
            @change="applyFilters"
          >

            <option value="id">
              ID
            </option>

            <option value="name">
              Name
            </option>

            <option value="price">
              Price
            </option>

            <option value="qty">
              Quantity
            </option>

            <option value="created_at">
              Created Date
            </option>

          </select>

        </div>


        <!-- Direction -->

        <div class="filter-field">

          <label>
            Direction
          </label>

          <select
            v-model="filters.sort_direction"
            @change="applyFilters"
          >

            <option value="asc">
              Ascending
            </option>

            <option value="desc">
              Descending
            </option>

          </select>

        </div>


        <!-- Per Page -->

        <div class="filter-field">

          <label>
            Per Page
          </label>

          <select
            v-model="filters.per_page"
            @change="applyFilters"
          >

            <option :value="5">
              5
            </option>

            <option :value="10">
              10
            </option>

            <option :value="20">
              20
            </option>

            <option :value="50">
              50
            </option>

          </select>

        </div>

      </div>

    </div>


    <!-- =========================================================
         PRODUCT LIST
    ========================================================== -->

    <div class="card">

      <div class="section-header">

        <div>

          <h3>
            Product List
          </h3>

          <p class="result-text">
            Showing
            {{ pagination.from || 0 }}
            -
            {{ pagination.to || 0 }}
            of
            {{ pagination.total || 0 }}
            products
          </p>

        </div>

        <button
          class="btn refresh"
          @click="loadProducts"
        >
          Refresh
        </button>

      </div>


      <!-- Bulk Action -->

      <div
        v-if="selectedProducts.length"
        class="bulk-bar"
      >

        <span>
          {{ selectedProducts.length }}
          product(s) selected
        </span>

        <button
          class="btn danger"
          @click="bulkDelete"
        >
          Delete Selected
        </button>

      </div>


      <table>

        <thead>

          <tr>

            <th>
              <input
                type="checkbox"
                :checked="allSelected"
                @change="toggleSelectAll"
              >
            </th>

            <th>
              #
            </th>

            <th>
              ID
            </th>

            <th>
              Name
            </th>

            <th>
              Price
            </th>

            <th>
              Qty
            </th>

            <th>
              Stock
            </th>

            <th>
              Action
            </th>

          </tr>

        </thead>


        <tbody>

          <tr
            v-for="product in products"
            :key="product.id"
          >

            <td>

              <input
                type="checkbox"
                :value="product.id"
                v-model="selectedProducts"
              >

            </td>

            <td>
              {{ getRowNumber(product) }}
            </td>

            <td>
              {{ product.id }}
            </td>

            <td>
              {{ product.name }}
            </td>

            <td>
              ₹ {{ Number(product.price).toFixed(2) }}
            </td>

            <td>
              {{ product.qty }}
            </td>

            <td>

              <span
                :class="[
                  'stock-badge',
                  getStockClass(product.qty)
                ]"
              >
                {{ getStockLabel(product.qty) }}
              </span>

            </td>

            <td>

              <button
                class="btn warning"
                @click="editProduct(product)"
              >
                Edit
              </button>

              <button
                class="btn danger"
                @click="deleteProduct(product.id)"
              >
                Delete
              </button>

            </td>

          </tr>


          <tr v-if="products.length === 0">

            <td
              colspan="8"
              class="empty"
            >
              No products found
            </td>

          </tr>

        </tbody>

      </table>


      <!-- =======================================================
           PAGINATION
      ======================================================== -->

      <div
        v-if="pagination.last_page > 1"
        class="pagination"
      >

        <button
          v-for="page in paginationPages"
          :key="page"
          class="page-btn"
          :class="{
            active: page === pagination.current_page
          }"
          @click="goToPage(page)"
        >
          {{ page }}
        </button>

      </div>

    </div>


    <!-- =========================================================
         IMPORT / EXPORT HISTORY
    ========================================================== -->

    <div class="card">

      <div class="section-header">

        <h3>
          Import / Export History
        </h3>

        <button
          class="btn refresh"
          @click="loadHistory"
        >
          Refresh History
        </button>

      </div>


      <!-- History Filters -->

      <div class="history-filter-grid">

        <input
          type="text"
          v-model="historyFilters.search"
          @input="loadHistory"
          placeholder="Search file/status"
        >

        <select
          v-model="historyFilters.operation"
          @change="loadHistory"
        >

          <option value="">
            All Operations
          </option>

          <option value="import">
            Import
          </option>

          <option value="export">
            Export
          </option>

        </select>

        <select
          v-model="historyFilters.status"
          @change="loadHistory"
        >

          <option value="">
            All Status
          </option>

          <option value="success">
            Success
          </option>

          <option value="partial">
            Partial
          </option>

          <option value="failed">
            Failed
          </option>

        </select>

      </div>


      <table>

        <thead>

          <tr>

            <th>
              #
            </th>

            <th>
              Operation
            </th>

            <th>
              File Name
            </th>

            <th>
              Total
            </th>

            <th>
              Success
            </th>

            <th>
              Duplicates
            </th>

            <th>
              Invalid
            </th>

            <th>
              Status
            </th>

            <th>
              Date
            </th>

          </tr>

        </thead>


        <tbody>

          <tr
            v-for="(item, index) in history"
            :key="item.id"
          >

            <td>
              {{ index + 1 }}
            </td>

            <td>

              <span
                :class="[
                  'operation-badge',
                  item.operation === 'import'
                    ? 'import-badge'
                    : 'export-badge'
                ]"
              >
                {{ item.operation.toUpperCase() }}
              </span>

            </td>

            <td>
              {{ item.file_name || '-' }}
            </td>

            <td>
              {{ item.total_records }}
            </td>

            <td>
              {{ item.successful_records }}
            </td>

            <td>
              {{ item.duplicate_records }}
            </td>

            <td>
              {{ item.invalid_records }}
            </td>

            <td>

              <span
                :class="[
                  'status-badge',
                  getStatusClass(item.status)
                ]"
              >
                {{ item.status }}
              </span>

            </td>

            <td>
              {{ formatDate(item.created_at) }}
            </td>

          </tr>


          <tr v-if="history.length === 0">

            <td
              colspan="9"
              class="empty"
            >
              No import/export history found
            </td>

          </tr>

        </tbody>

      </table>

    </div>

  </div>

</template>


<script>

import axios from 'axios';

export default {

  data() {

    return {

      /*
      |--------------------------------------------------------------------------
      | Products
      |--------------------------------------------------------------------------
      */

      products: [],

      selectedProducts: [],

      /*
      |--------------------------------------------------------------------------
      | Pagination
      |--------------------------------------------------------------------------
      */

      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 5,
        total: 0,
        from: 0,
        to: 0
      },

      /*
      |--------------------------------------------------------------------------
      | Dashboard Statistics
      |--------------------------------------------------------------------------
      */

      statistics: {
        total_products: 0,
        total_quantity: 0,
        inventory_value: 0,
        in_stock: 0,
        low_stock: 0,
        out_of_stock: 0
      },

      /*
      |--------------------------------------------------------------------------
      | Product Filters
      |--------------------------------------------------------------------------
      */

      filters: {

        search: '',

        min_price: '',

        max_price: '',

        stock_status: '',

        sort_by: 'id',

        sort_direction: 'asc',

        per_page: 5

      },

      /*
      |--------------------------------------------------------------------------
      | History
      |--------------------------------------------------------------------------
      */

      history: [],

      historyFilters: {

        search: '',

        operation: '',

        status: ''

      },

      /*
      |--------------------------------------------------------------------------
      | Import
      |--------------------------------------------------------------------------
      */

      file: null,

      importing: false,

      exporting: false,

      importSummary: null,

      /*
      |--------------------------------------------------------------------------
      | Product Form
      |--------------------------------------------------------------------------
      */

      editId: null,

      message: '',

      form: {

        name: '',

        price: '',

        qty: ''

      },

      /*
      |--------------------------------------------------------------------------
      | Search Timer
      |--------------------------------------------------------------------------
      */

      searchTimer: null

    };

  },


  computed: {

    /*
    |--------------------------------------------------------------------------
    | Check all visible products selected
    |--------------------------------------------------------------------------
    */

    allSelected() {

      return (
        this.products.length > 0 &&
        this.selectedProducts.length ===
          this.products.length
      );

    },


    /*
    |--------------------------------------------------------------------------
    | Numeric pagination buttons
    |--------------------------------------------------------------------------
    */

    paginationPages() {

      const pages = [];

      for (
        let i = 1;
        i <= this.pagination.last_page;
        i++
      ) {

        pages.push(i);

      }

      return pages;

    }

  },


  mounted() {

    this.loadProducts();

    this.loadHistory();

  },


  methods: {

    /*
    |--------------------------------------------------------------------------
    | Load Products
    |--------------------------------------------------------------------------
    */

    loadProducts(page = 1) {

      axios
        .get('/products/list', {

          params: {

            search:
              this.filters.search,

            min_price:
              this.filters.min_price,

            max_price:
              this.filters.max_price,

            stock_status:
              this.filters.stock_status,

            sort_by:
              this.filters.sort_by,

            sort_direction:
              this.filters.sort_direction,

            per_page:
              this.filters.per_page,

            page: page

          }

        })
        .then(res => {

          const data = res.data;

          this.products =
            data.products.data;

          this.pagination = {

            current_page:
              data.products.current_page,

            last_page:
              data.products.last_page,

            per_page:
              data.products.per_page,

            total:
              data.products.total,

            from:
              data.products.from,

            to:
              data.products.to

          };

          this.statistics =
            data.statistics;

          /*
          | Remove selected IDs that no longer exist
          */

          const visibleIds =
            this.products.map(
              product => product.id
            );

          this.selectedProducts =
            this.selectedProducts.filter(
              id =>
                visibleIds.includes(id)
            );

        })
        .catch(error => {

          console.error(
            'PRODUCT LOAD ERROR:',
            error
          );

        });

    },


    /*
    |--------------------------------------------------------------------------
    | Search debounce
    |--------------------------------------------------------------------------
    */

    debouncedLoadProducts() {

      clearTimeout(
        this.searchTimer
      );

      this.searchTimer =
        setTimeout(() => {

          this.loadProducts(1);

        }, 400);

    },


    /*
    |--------------------------------------------------------------------------
    | Apply Filters
    |--------------------------------------------------------------------------
    */

    applyFilters() {

      this.selectedProducts = [];

      this.loadProducts(1);

    },


    /*
    |--------------------------------------------------------------------------
    | Clear Filters
    |--------------------------------------------------------------------------
    */

    clearFilters() {

      this.filters = {

        search: '',

        min_price: '',

        max_price: '',

        stock_status: '',

        sort_by: 'id',

        sort_direction: 'asc',

        per_page: 5

      };

      this.selectedProducts = [];

      this.loadProducts(1);

    },


    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    goToPage(page) {

      if (
        page < 1 ||
        page > this.pagination.last_page
      ) {
        return;
      }

      this.selectedProducts = [];

      this.loadProducts(page);

      window.scrollTo({

        top: 0,

        behavior: 'smooth'

      });

    },


    /*
    |--------------------------------------------------------------------------
    | Row Number
    |--------------------------------------------------------------------------
    */

    getRowNumber(product) {

      return (
        (this.pagination.current_page - 1) *
          this.pagination.per_page
      ) +
        this.products.indexOf(product) +
        1;

    },


    /*
    |--------------------------------------------------------------------------
    | Dashboard number formatting
    |--------------------------------------------------------------------------
    */

    formatNumber(value) {

      return Number(value || 0)
        .toLocaleString(
          'en-IN',
          {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          }
        );

    },


    /*
    |--------------------------------------------------------------------------
    | Stock Label
    |--------------------------------------------------------------------------
    */

    getStockLabel(qty) {

      if (Number(qty) === 0) {

        return 'Out of Stock';

      }

      if (Number(qty) <= 5) {

        return 'Low Stock';

      }

      return 'In Stock';

    },


    /*
    |--------------------------------------------------------------------------
    | Stock CSS class
    |--------------------------------------------------------------------------
    */

    getStockClass(qty) {

      if (Number(qty) === 0) {

        return 'stock-out';

      }

      if (Number(qty) <= 5) {

        return 'stock-low';

      }

      return 'stock-in';

    },


    /*
    |--------------------------------------------------------------------------
    | Save Product
    |--------------------------------------------------------------------------
    */

    saveProduct() {

      if (!this.form.name.trim()) {

        alert(
          'Product name is required.'
        );

        return;

      }

      if (
        this.form.price === '' ||
        Number(this.form.price) < 0
      ) {

        alert(
          'Please enter a valid price.'
        );

        return;

      }

      if (
        this.form.qty === '' ||
        Number(this.form.qty) < 0 ||
        !Number.isInteger(
          Number(this.form.qty)
        )
      ) {

        alert(
          'Please enter a valid quantity.'
        );

        return;

      }

      const url = this.editId
        ? `/products/update/${this.editId}`
        : '/products/store';

      axios
        .post(
          url,
          this.form
        )
        .then(res => {

          this.message =
            res.data.message;

          this.resetForm();

          this.loadProducts(
            this.pagination.current_page
          );

          setTimeout(() => {

            this.message = '';

          }, 3000);

        })
        .catch(error => {

          if (
            error.response?.data?.errors
          ) {

            const errors =
              error.response.data.errors;

            alert(
              Object.values(errors)
                .flat()
                .join('\n')
            );

          } else {

            alert(
              'Something went wrong.'
            );

          }

        });

    },


    /*
    |--------------------------------------------------------------------------
    | Edit Product
    |--------------------------------------------------------------------------
    */

    editProduct(product) {

      this.editId =
        product.id;

      this.form = {

        name:
          product.name,

        price:
          product.price,

        qty:
          product.qty

      };

      window.scrollTo({

        top: 0,

        behavior: 'smooth'

      });

    },


    /*
    |--------------------------------------------------------------------------
    | Delete Product
    |--------------------------------------------------------------------------
    */

    deleteProduct(id) {

      if (
        !confirm(
          'Are you sure you want to delete this product?'
        )
      ) {

        return;

      }

      axios
        .delete(
          `/products/delete/${id}`
        )
        .then(res => {

          this.message =
            res.data.message;

          this.selectedProducts =
            this.selectedProducts.filter(
              selectedId =>
                selectedId !== id
            );

          this.loadProducts(
            this.pagination.current_page
          );

          setTimeout(() => {

            this.message = '';

          }, 3000);

        })
        .catch(error => {

          console.error(error);

          alert(
            'Unable to delete product.'
          );

        });

    },


    /*
    |--------------------------------------------------------------------------
    | Select All
    |--------------------------------------------------------------------------
    */

    toggleSelectAll(event) {

      if (event.target.checked) {

        this.selectedProducts =
          this.products.map(
            product =>
              product.id
          );

      } else {

        this.selectedProducts = [];

      }

    },


    /*
    |--------------------------------------------------------------------------
    | Bulk Delete
    |--------------------------------------------------------------------------
    */

    bulkDelete() {

      if (
        this.selectedProducts.length === 0
      ) {

        alert(
          'Please select at least one product.'
        );

        return;

      }

      if (
        !confirm(
          `Are you sure you want to delete ${this.selectedProducts.length} selected product(s)?`
        )
      ) {

        return;

      }

      axios
        .post(
          '/products/bulk-delete',
          {
            ids:
              this.selectedProducts
          }
        )
        .then(res => {

          this.message =
            res.data.message;

          this.selectedProducts = [];

          this.loadProducts(1);

          setTimeout(() => {

            this.message = '';

          }, 3000);

        })
        .catch(error => {

          console.error(
            'BULK DELETE ERROR:',
            error
          );

          alert(
            error.response?.data?.message ||
            'Unable to delete selected products.'
          );

        });

    },


    /*
    |--------------------------------------------------------------------------
    | Reset Form
    |--------------------------------------------------------------------------
    */

    resetForm() {

      this.editId = null;

      this.form = {

        name: '',

        price: '',

        qty: ''

      };

    },
    


    /*
    |--------------------------------------------------------------------------
    | Select Excel File
    |--------------------------------------------------------------------------
    */

    handleFile(event) {

      this.file =
        event.target.files[0] ||
        null;

      this.importSummary = null;

    },


    /*
    |--------------------------------------------------------------------------
    | Import Excel
    |--------------------------------------------------------------------------
    */

    importExcel() {

      if (!this.file) {

        alert(
          'Please select an XLSX or CSV file.'
        );

        return;

      }

      this.importing = true;

      this.importSummary = null;

      const formData =
        new FormData();

      formData.append(
        'file',
        this.file
      );

      axios
        .post(
          '/products/import',
          formData
        )
        .then(res => {

          this.message =
            res.data.message;

          this.importSummary =
            res.data.summary;

          this.loadProducts(1);

          this.loadHistory();

          this.file = null;

          if (
            this.$refs.fileInput
          ) {

            this.$refs.fileInput.value =
              '';

          }

        })
        .catch(error => {

          console.error(
            'IMPORT ERROR:',
            error
          );

          const serverMessage =
            error.response?.data?.error ||
            error.response?.data?.message ||
            'Import failed.';

          alert(serverMessage);

          this.loadHistory();

        })
        .finally(() => {

          this.importing = false;

        });

    },


    /*
    |--------------------------------------------------------------------------
    | Filtered Excel Export
    |--------------------------------------------------------------------------
    */

    exportExcel() {

      this.exporting = true;

      const params =
        new URLSearchParams();

      if (this.filters.search) {

        params.append(
          'search',
          this.filters.search
        );

      }

      if (
        this.filters.min_price !== ''
      ) {

        params.append(
          'min_price',
          this.filters.min_price
        );

      }

      if (
        this.filters.max_price !== ''
      ) {

        params.append(
          'max_price',
          this.filters.max_price
        );

      }

      if (
        this.filters.stock_status
      ) {

        params.append(
          'stock_status',
          this.filters.stock_status
        );

      }

      params.append(
        'sort_by',
        this.filters.sort_by
      );

      params.append(
        'sort_direction',
        this.filters.sort_direction
      );

      window.location.href =
        '/products/export?' +
        params.toString();

      setTimeout(() => {

        this.exporting = false;

        this.loadHistory();

      }, 1500);

    },


    /*
    |--------------------------------------------------------------------------
    | Load History
    |--------------------------------------------------------------------------
    */

    loadHistory() {

      axios
        .get(
          '/products/history',
          {
            params: {

              search:
                this.historyFilters.search,

              operation:
                this.historyFilters.operation,

              status:
                this.historyFilters.status

            }
          }
        )
        .then(res => {

          this.history =
            res.data;

        })
        .catch(error => {

          console.error(
            'HISTORY ERROR:',
            error
          );

        });

    },


    /*
    |--------------------------------------------------------------------------
    | History Status Class
    |--------------------------------------------------------------------------
    */

    getStatusClass(status) {

      if (
        status === 'success'
      ) {

        return 'status-success';

      }

      if (
        status === 'partial'
      ) {

        return 'status-partial';

      }

      return 'status-failed';

    },


    /*
    |--------------------------------------------------------------------------
    | Format Date
    |--------------------------------------------------------------------------
    */

    formatDate(date) {

      if (!date) {

        return '-';

      }

      return new Date(date)
        .toLocaleString();

    }

  }

};

</script>


<style scoped>

/* =========================================================
   GLOBAL
========================================================= */

.container {

  max-width: 1250px;

  margin: 30px auto;

  padding: 0 15px;

  font-family:
    Arial,
    sans-serif;

  color: #333;

}

.title {

  text-align: center;

  margin-bottom: 30px;

  font-size: 30px;

}


/* =========================================================
   DASHBOARD
========================================================= */

.stats-grid {

  display: grid;

  grid-template-columns:
    repeat(6, 1fr);

  gap: 15px;

  margin-bottom: 25px;

}

.stat-card {

  padding: 20px;

  border-radius: 10px;

  text-align: center;

  box-shadow:
    0 2px 10px
    rgba(0, 0, 0, 0.08);

}

.stat-card span {

  display: block;

  font-size: 13px;

  margin-bottom: 8px;

}

.stat-card strong {

  display: block;

  font-size: 25px;

}

.total-card {

  background: #eef2ff;

  color: #3730a3;

}

.quantity-card {

  background: #ecfeff;

  color: #155e75;

}

.value-card {

  background: #f0fdf4;

  color: #166534;

}

.stock-card {

  background: #eff6ff;

  color: #1d4ed8;

}

.low-card {

  background: #fef3c7;

  color: #92400e;

}

.out-card {

  background: #fee2e2;

  color: #991b1b;

}


/* =========================================================
   CARD
========================================================= */

.card {

  background: #fff;

  padding: 20px;

  margin-bottom: 25px;

  border-radius: 10px;

  box-shadow:
    0 2px 10px
    rgba(0, 0, 0, 0.08);

}


/* =========================================================
   FORM
========================================================= */

.form-grid {

  display: grid;

  grid-template-columns:
    repeat(3, 1fr);

  gap: 15px;

}

input[type="text"],
input[type="number"],
input[type="file"],
select {

  padding: 10px;

  border:
    1px solid #ccc;

  border-radius: 5px;

  width: 100%;

  background: #fff;

}

label {

  display: block;

  margin-bottom: 7px;

  font-weight: 600;

  font-size: 13px;

}

.btn-group {

  margin-top: 15px;

}


/* =========================================================
   BUTTONS
========================================================= */

.btn {

  padding: 8px 14px;

  border: none;

  border-radius: 5px;

  cursor: pointer;

  margin-right: 6px;

  color: #fff;

  transition: 0.2s;

}

.btn:hover {

  opacity: 0.9;

}

.btn:disabled {

  opacity: 0.6;

  cursor: not-allowed;

}

.primary {

  background: #4f46e5;

}

.secondary {

  background: #6b7280;

}

.success {

  background: #16a34a;

}

.info {

  background: #0284c7;

}

.warning {

  background: #f59e0b;

}

.danger {

  background: #dc2626;

}

.refresh {

  background: #475569;

}


/* =========================================================
   IMPORT
========================================================= */

.import-box {

  display: flex;

  align-items: center;

  gap: 10px;

  flex-wrap: wrap;

}

.help-text {

  color: #777;

  font-size: 13px;

  margin-top: 10px;

}


/* =========================================================
   FILTERS
========================================================= */

.filter-grid {

  display: grid;

  grid-template-columns:
    repeat(4, 1fr);

  gap: 15px;

}

.filter-field {

  min-width: 0;

}


/* =========================================================
   SECTION HEADER
========================================================= */

.section-header {

  display: flex;

  justify-content: space-between;

  align-items: center;

  margin-bottom: 15px;

}

.result-text {

  color: #777;

  font-size: 13px;

  margin: 5px 0 0;

}


/* =========================================================
   BULK BAR
========================================================= */

.bulk-bar {

  background: #fef2f2;

  border:
    1px solid #fecaca;

  padding: 12px;

  border-radius: 7px;

  margin-bottom: 15px;

  display: flex;

  justify-content: space-between;

  align-items: center;

  color: #991b1b;

  font-weight: 600;

}


/* =========================================================
   TABLE
========================================================= */

table {

  width: 100%;

  border-collapse: collapse;

}

thead {

  background: #f3f4f6;

}

th,
td {

  padding: 12px;

  border-bottom:
    1px solid #e5e7eb;

  text-align: center;

}

.empty {

  text-align: center;

  color: #777;

  padding: 25px;

}


/* =========================================================
   STOCK BADGES
========================================================= */

.stock-badge {

  display: inline-block;

  padding: 5px 9px;

  border-radius: 15px;

  font-size: 11px;

  font-weight: bold;

}

.stock-in {

  background: #dcfce7;

  color: #166534;

}

.stock-low {

  background: #fef3c7;

  color: #92400e;

}

.stock-out {

  background: #fee2e2;

  color: #991b1b;

}


/* =========================================================
   PAGINATION
========================================================= */

.pagination {

  display: flex;

  justify-content: center;

  flex-wrap: wrap;

  gap: 6px;

  margin-top: 20px;

}

.page-btn {

  min-width: 38px;

  height: 38px;

  border:
    1px solid #d1d5db;

  background: #fff;

  border-radius: 5px;

  cursor: pointer;

  font-weight: 600;

}

.page-btn:hover {

  background: #f3f4f6;

}

.page-btn.active {

  background: #4f46e5;

  color: #fff;

  border-color: #4f46e5;

}


/* =========================================================
   MESSAGE
========================================================= */

.msg-success {

  margin-top: 10px;

  color: #16a34a;

  font-weight: 600;

}


/* =========================================================
   IMPORT SUMMARY
========================================================= */

.summary-grid {

  display: grid;

  grid-template-columns:
    repeat(4, 1fr);

  gap: 15px;

  margin-top: 15px;

}

.summary-box {

  padding: 20px;

  border-radius: 8px;

  background: #f3f4f6;

  text-align: center;

}

.summary-box span {

  display: block;

  color: #666;

  margin-bottom: 8px;

}

.summary-box strong {

  font-size: 28px;

}

.success-box {

  background: #dcfce7;

  color: #166534;

}

.duplicate-box {

  background: #fef3c7;

  color: #92400e;

}

.invalid-box {

  background: #fee2e2;

  color: #991b1b;

}


/* =========================================================
   ERROR TABLE
========================================================= */

.error-section {

  margin-top: 25px;

}

.error-section h4 {

  margin-bottom: 10px;

}

.error-table {

  font-size: 14px;

}

.error-badge {

  display: inline-block;

  padding: 5px 9px;

  border-radius: 15px;

  font-size: 12px;

  font-weight: bold;

}

.duplicate-badge {

  background: #fef3c7;

  color: #92400e;

}

.invalid-badge {

  background: #fee2e2;

  color: #991b1b;

}


/* =========================================================
   HISTORY FILTER
========================================================= */

.history-filter-grid {

  display: grid;

  grid-template-columns:
    2fr 1fr 1fr;

  gap: 10px;

  margin-bottom: 20px;

}


/* =========================================================
   HISTORY BADGES
========================================================= */

.operation-badge,
.status-badge {

  display: inline-block;

  padding: 5px 9px;

  border-radius: 15px;

  font-size: 11px;

  font-weight: bold;

}

.import-badge {

  background: #dbeafe;

  color: #1e40af;

}

.export-badge {

  background: #dcfce7;

  color: #166534;

}

.status-success {

  background: #dcfce7;

  color: #166534;

}

.status-partial {

  background: #fef3c7;

  color: #92400e;

}

.status-failed {

  background: #fee2e2;

  color: #991b1b;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {

  .stats-grid {

    grid-template-columns:
      repeat(3, 1fr);

  }

  .filter-grid {

    grid-template-columns:
      repeat(2, 1fr);

  }

}

@media (max-width: 768px) {

  .form-grid {

    grid-template-columns: 1fr;

  }

  .stats-grid {

    grid-template-columns:
      repeat(2, 1fr);

  }

  .filter-grid {

    grid-template-columns: 1fr;

  }

  .history-filter-grid {

    grid-template-columns: 1fr;

  }

  .import-box {

    flex-direction: column;

    align-items: stretch;

  }

  table {

    display: block;

    overflow-x: auto;

    white-space: nowrap;

  }

}

@media (max-width: 480px) {

  .stats-grid {

    grid-template-columns: 1fr;

  }

  .summary-grid {

    grid-template-columns: 1fr;

  }

}

</style>