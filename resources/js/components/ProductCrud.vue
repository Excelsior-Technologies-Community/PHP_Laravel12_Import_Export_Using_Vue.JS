<template>
  <div class="container">

    <!-- Page title -->
    <h2 class="title">Product Manager</h2>

    <!-- ================= ADD / EDIT PRODUCT ================= -->
    <div class="card">
      <h3>{{ editId ? 'Edit Product' : 'Add Product' }}</h3>

      <!-- Product form inputs -->
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
        >
        <input
          type="number"
          v-model="form.qty"
          placeholder="Qty"
        >
      </div>

      <!-- Form action buttons -->
      <div class="btn-group">
        <button class="btn primary" @click="saveProduct">
          {{ editId ? 'Update' : 'Add' }}
        </button>

        <!-- Cancel edit mode -->
        <button
          v-if="editId"
          class="btn secondary"
          @click="resetForm"
        >
          Cancel
        </button>
      </div>

      <!-- Success message -->
      <p v-if="message" class="msg-success">
        {{ message }}
      </p>
    </div>

    <!-- ================= IMPORT / EXPORT ================= -->
    <div class="card">
      <h3>Import / Export</h3>

      <div class="import-box">
        <input type="file" @change="handleFile">

        <!-- Import Excel button -->
        <button class="btn success" @click="importExcel">
          Import Excel
        </button>

        <!-- Export Excel button -->
        <button class="btn info" @click="exportExcel">
          Export Excel
        </button>
      </div>
    </div>

    <!-- ================= PRODUCT LIST ================= -->
    <div class="card">
      <h3>Product List</h3>

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
          <!-- Loop through products -->
          <tr v-for="(product, index) in products" :key="product.id">
            <td>{{ index + 1 }}</td>
            <td>{{ product.name }}</td>
            <td>₹ {{ product.price }}</td>
            <td>{{ product.qty }}</td>
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

          <!-- Empty table message -->
          <tr v-if="products.length === 0">
            <td colspan="5" class="empty">
              No products found
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
      products: [],     // Product list
      file: null,       // Selected Excel file
      editId: null,     // Product ID for edit
      message: '',      // Success message
      form: {           // Product form data
        name: '',
        price: '',
        qty: ''
      }
    };
  },

  // Load products when page loads
  mounted() {
    this.loadProducts();
  },

  methods: {
    // Fetch products from backend
    loadProducts() {
      axios.get('/products/list').then(res => {
        this.products = res.data;
      });
    },

    // Create or update product
    saveProduct() {
      const url = this.editId
        ? `/products/update/${this.editId}`
        : '/products/store';

      axios.post(url, this.form).then(res => {
        this.message = res.data.message;
        this.resetForm();
        this.loadProducts();
      });
    },

    // Fill form for editing
    editProduct(product) {
      this.editId = product.id;
      this.form = {
        name: product.name,
        price: product.price,
        qty: product.qty
      };
    },

    // Delete product
    deleteProduct(id) {
      if (!confirm('Are you sure you want to delete this product?')) return;

      axios.delete(`/products/delete/${id}`).then(res => {
        this.message = res.data.message;
        this.loadProducts();
      });
    },

    // Reset form fields
    resetForm() {
      this.editId = null;
      this.form = { name: '', price: '', qty: '' };
    },

    // Handle Excel file selection
    handleFile(event) {
      this.file = event.target.files[0];
    },

    // Import products from Excel
    importExcel() {
      if (!this.file) {
        alert('Please select an Excel file');
        return;
      }

      const formData = new FormData();
      formData.append('file', this.file);

      axios.post('/products/import', formData).then(res => {
        this.message = res.data.message;
        this.loadProducts();
      });
    },

    // Export products to Excel
    exportExcel() {
      window.location.href = '/products/export';
    }
  }
};
</script>

<style scoped>
/* ===== LAYOUT ===== */
.container {
  max-width: 1100px;
  margin: 30px auto;
  font-family: Arial, sans-serif;
}

.title {
  text-align: center;
  margin-bottom: 30px;
}

/* ===== CARD ===== */
.card {
  background: #ffffff;
  padding: 20px;
  margin-bottom: 25px;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

/* ===== FORM ===== */
.form-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
}

input[type="text"],
input[type="number"],
input[type="file"] {
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
}

.btn-group {
  margin-top: 15px;
}

/* ===== BUTTONS ===== */
.btn {
  padding: 8px 14px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  margin-right: 6px;
  color: #fff;
}

.primary { background: #4f46e5; }
.secondary { background: #6b7280; }
.success { background: #16a34a; }
.info { background: #0284c7; }
.warning { background: #f59e0b; }
.danger { background: #dc2626; }

.btn:hover {
  opacity: 0.9;
}

/* ===== TABLE ===== */
table {
  width: 100%;
  border-collapse: collapse;
}

thead {
  background: #f3f4f6;
}

th, td {
  padding: 12px;
  border-bottom: 1px solid #e5e7eb;
  text-align: center;
}

.empty {
  text-align: center;
  color: #777;
}

/* ===== MESSAGE ===== */
.msg-success {
  margin-top: 10px;
  color: #16a34a;
  font-weight: 600;
}
</style>
