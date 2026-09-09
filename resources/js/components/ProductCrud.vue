<template>
  <div class="container">

    <!-- ================= PAGE TITLE ================= -->

    <h2 class="title">
      Product Import / Export Manager
    </h2>


    <!-- ================= ADD / EDIT PRODUCT ================= -->

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


    <!-- ================= IMPORT / EXPORT ================= -->

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
          {{ importing ? 'Importing...' : 'Import Excel' }}
        </button>


        <button
          class="btn info"
          @click="exportExcel"
          :disabled="exporting"
        >
          {{ exporting ? 'Exporting...' : 'Export Excel' }}
        </button>

      </div>


      <p class="help-text">
        Supported formats: XLSX and CSV
      </p>

    </div>


    <!-- ================= IMPORT SUMMARY ================= -->

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


    <!-- ================= PRODUCT LIST ================= -->

    <div class="card">

      <div class="section-header">

        <h3>
          Product List
        </h3>

        <button
          class="btn refresh"
          @click="loadProducts"
        >
          Refresh
        </button>

      </div>


      <table>

        <thead>

          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Action</th>
          </tr>

        </thead>


        <tbody>

          <tr
            v-for="(product, index) in products"
            :key="product.id"
          >

            <td>
              {{ index + 1 }}
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
              colspan="5"
              class="empty"
            >
              No products found
            </td>

          </tr>

        </tbody>

      </table>

    </div>


    <!-- ================= IMPORT / EXPORT HISTORY ================= -->

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


      <table>

        <thead>

          <tr>
            <th>#</th>
            <th>Operation</th>
            <th>File Name</th>
            <th>Total</th>
            <th>Success</th>
            <th>Duplicates</th>
            <th>Invalid</th>
            <th>Status</th>
            <th>Date</th>
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

      products: [],

      history: [],

      file: null,

      editId: null,

      message: '',

      importing: false,

      exporting: false,

      importSummary: null,

      form: {
        name: '',
        price: '',
        qty: ''
      }

    };

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

    loadProducts() {

      axios
        .get('/products/list')
        .then(res => {

          this.products = res.data;

        })
        .catch(error => {

          console.error(error);

        });

    },


    /*
    |--------------------------------------------------------------------------
    | Save Product
    |--------------------------------------------------------------------------
    */

    saveProduct() {

      if (!this.form.name.trim()) {

        alert('Product name is required.');

        return;

      }


      if (
        this.form.price === '' ||
        Number(this.form.price) < 0
      ) {

        alert('Please enter a valid price.');

        return;

      }


      if (
        this.form.qty === '' ||
        Number(this.form.qty) < 0
      ) {

        alert('Please enter a valid quantity.');

        return;

      }


      const url = this.editId
        ? `/products/update/${this.editId}`
        : '/products/store';


      axios
        .post(url, this.form)
        .then(res => {

          this.message = res.data.message;

          this.resetForm();

          this.loadProducts();

          setTimeout(() => {

            this.message = '';

          }, 3000);

        })
        .catch(error => {

          if (error.response?.data?.errors) {

            const errors =
              error.response.data.errors;

            alert(
              Object.values(errors)
                .flat()
                .join('\n')
            );

          } else {

            alert('Something went wrong.');

          }

        });

    },


    /*
    |--------------------------------------------------------------------------
    | Edit Product
    |--------------------------------------------------------------------------
    */

    editProduct(product) {

      this.editId = product.id;

      this.form = {

        name: product.name,

        price: product.price,

        qty: product.qty

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
        .delete(`/products/delete/${id}`)
        .then(res => {

          this.message = res.data.message;

          this.loadProducts();

          setTimeout(() => {

            this.message = '';

          }, 3000);

        })
        .catch(error => {

          console.error(error);

          alert('Unable to delete product.');

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
        event.target.files[0] || null;

      this.importSummary = null;

    },


    /*
    |--------------------------------------------------------------------------
    | Import Excel
    |--------------------------------------------------------------------------
    */

importExcel() {
    if (!this.file) {
        alert('Please select an XLSX or CSV file.');
        return;
    }

    this.importing = true;
    this.importSummary = null;

    const formData = new FormData();
    formData.append('file', this.file);

    axios.post('/products/import', formData)
        .then(res => {
            console.log('IMPORT SUCCESS:', res.data);

            this.message = res.data.message;
            this.importSummary = res.data.summary;

            this.loadProducts();
            this.loadHistory();

            this.file = null;

            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        })
        .catch(error => {
            console.error('IMPORT ERROR:', error);

            if (error.response) {
                console.error('Status:', error.response.status);
                console.error('Response:', error.response.data);
                console.error('Message:', error.response.data?.message);
                console.error('Error:', error.response.data?.error);
                console.error('Exception:', error.response.data?.exception);

                const serverMessage =
                    error.response.data?.error ||
                    error.response.data?.message ||
                    'Import failed.';

                alert(serverMessage);
            } else {
                alert('Unable to connect to the server.');
            }

            this.loadHistory();
        })
        .finally(() => {
            this.importing = false;
        });
},   // <-- IMPORTANT: comma here


/* =========================================================
   Export Excel
========================================================= */

exportExcel() {
    this.exporting = true;

    window.location.href = '/products/export';

    setTimeout(() => {
        this.exporting = false;
        this.loadHistory();
    }, 1500);
},


    /*
    |--------------------------------------------------------------------------
    | Load Import/Export History
    |--------------------------------------------------------------------------
    */

    loadHistory() {

      axios
        .get('/products/history')
        .then(res => {

          this.history = res.data;

        })
        .catch(error => {

          console.error(error);

        });

    },


    /*
    |--------------------------------------------------------------------------
    | Status Class
    |--------------------------------------------------------------------------
    */

    getStatusClass(status) {

      if (status === 'success') {

        return 'status-success';

      }

      if (status === 'partial') {

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
   GLOBAL LAYOUT
========================================================= */

.container {

  max-width: 1200px;

  margin: 30px auto;

  padding: 0 15px;

  font-family: Arial, sans-serif;

  color: #333;

}


.title {

  text-align: center;

  margin-bottom: 30px;

  font-size: 30px;

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
    0 2px 10px rgba(0, 0, 0, 0.08);

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
input[type="file"] {

  padding: 10px;

  border:
    1px solid #ccc;

  border-radius: 5px;

  width: 100%;

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
   IMPORT AREA
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
   SECTION HEADER
========================================================= */

.section-header {

  display: flex;

  justify-content: space-between;

  align-items: center;

  margin-bottom: 15px;

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

@media (max-width: 768px) {

  .form-grid {

    grid-template-columns: 1fr;

  }


  .summary-grid {

    grid-template-columns: 1fr 1fr;

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

  .summary-grid {

    grid-template-columns: 1fr;

  }

}

</style>