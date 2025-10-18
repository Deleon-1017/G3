<?php
require '../db.php';
require '../shared/config.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Sales Orders</title>
  <link rel="stylesheet" href="styles.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <base href="<?php echo BASE_URL; ?>">
  <style>
    .line-input{width:100%}
    .small{font-size:0.9rem;color:#666}
    .items-table th, .items-table td{padding:6px 8px}
    .table th, .table td { padding: 8px 12px; }
    .summary-table { table-layout: fixed; width: 100%; }
    .summary-table th:nth-child(1), .summary-table td:nth-child(1) { width: 30%; }
    .summary-table th:nth-child(2), .summary-table td:nth-child(2) { width: 8%; }
    .summary-table th:nth-child(3), .summary-table td:nth-child(3) { width: 15%; }
    .summary-table th:nth-child(4), .summary-table td:nth-child(4) { width: 15%; }
    .summary-table th:nth-child(5), .summary-table td:nth-child(5) { width: 16%; }
    .summary-table th:nth-child(6), .summary-table td:nth-child(6) { width: 16%; }
    .dropdown {
      position: relative;
      display: inline-block;
    }
    .dropdown-content {
      display: none;
      position: absolute;
      background-color: #f9f9f9;
      min-width: 160px;
      box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
      z-index: 1;
      right: 0;
    }
    .dropdown-content a {
      color: black;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
      cursor: pointer;
    }
    .dropdown-content a:hover {background-color: #f1f1f1}
    .menu-icon {
      background: none;
      border: none;
      font-size: 18px;
      cursor: pointer;
      padding: 5px;
    }
    .status-pending {
      background: #ffc107;
      color: #000;
      border-radius: 25px;
      margin: 6px;
      padding: 2px 6px;
      display: inline-block;
    }
    .status-processed {
      background: #17a2b8;
      color: #fff;
      border-radius: 25px;
      margin: 6px;
      padding: 2px 6px;
      display: inline-block;
    }
    .status-shipped {
      background: #28a745;
      color: #fff;
      border-radius: 25px;
      padding: 2px 6px;
      margin: 6px;
      display: inline-block;
    }
    .status-delivered {
      background: #007bff;
      color: #fff;
      border-radius: 25px;
      margin: 6px;
      padding: 2px 6px;
      display: inline-block;
    }
    @media (max-width: 768px) {
      .container {
        margin-left: 0;
        padding: 10px;
      }
      .card {
        margin-bottom: 20px;
        padding: 15px;
      }
      .table {
        font-size: 0.9em;
        overflow-x: auto;
        display: block;
        white-space: nowrap;
      }
      .table th, .table td {
        padding: 6px;
        min-width: 80px;
      }
      .btn {
        padding: 8px 16px;
        font-size: 1em;
        margin: 2px;
      }
      .line-input {
        width: 100%;
        margin-bottom: 10px;
      }
      .container > div > div:first-child {
        flex-direction: column;
        gap: 10px;
      }
      .container > div > div:first-child label {
        width: 100%;
      }
      #orderSummaryModal .card { flex-direction: column; }
      #orderSummaryModal .summary-row { flex-direction: column; align-items: flex-start; }
      #orderSummaryModal .summary-row span:first-child { margin-bottom: 4px; }
      #orderSummaryModal .items-li { flex-direction: column; align-items: flex-start; }
      #orderSummaryModal .items-li .details { margin-top: 4px; text-align: left; }
      #orderSummaryModal {
        width: 95%;
        max-height: 90vh;
      }
      .orders-table {
        border: 0;
      }
      .orders-table thead {
        display: none;
      }
      .orders-table tr {
        display: block;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 5px;
        background: #fff;
      }
      .orders-table td {
        display: block;
        text-align: right;
        border: none;
        position: relative;
        padding-left: 50%;
        padding-right: 10px;
        padding-top: 5px;
        padding-bottom: 5px;
      }
      .orders-table td:before {
        content: attr(data-label);
        position: absolute;
        left: 6px;
        width: 45%;
        padding-right: 10px;
        white-space: nowrap;
        font-weight: bold;
        text-align: left;
      }
      .orders-table td[data-label="Actions"] {
        text-align: center;
        padding-left: 10px;
      }
      .orders-table td[data-label="Actions"]:before {
        display: none;
      }
    }
    @media (max-width: 480px) {
      .container {
        padding: 5px;
      }
      .card {
        padding: 10px;
      }
      .table {
        font-size: 0.8em;
      }
      .table th, .table td {
        padding: 4px;
        min-width: 60px;
      }
      .btn {
        padding: 6px 12px;
        font-size: 0.9em;
      }
      h1 {
        font-size: 1.5em;
      }
      h3 {
        font-size: 1.2em;
      }
      #orderSummaryModal {
        width: 98%;
        padding: 10px;
      }
    }
  </style>
</head>
<body>
  <?php include '../shared/sidebar.php'; ?>
  <div class="container" style="margin-left:18rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h1>Sales Orders</h1>
    </div>

    <div class="card">
      <div style="display:flex;gap:12px;align-items:center">
        <label>Customer
          <select id="customer" class="line-input"></select>
        </label>
        <label>Order #: <input id="order_number" class="line-input" value="SO-<?php echo time(); ?>"></label>
      </div>
    </div>

    <div class="card">
      <h3>Add Item</h3>
      <table class="table">
        <thead>
          <tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Line Total</th><th></th></tr>
        </thead>
        <tbody>
          <tr>
            <td><select id="product_select" class="line-input" onchange="onProductChange()"><option value="">Select Product</option></select></td>
            <td><input id="qty" type="number" value="1" style="width:80px" oninput="updateLineTotal()"></td>
            <td><span id="unit_price_display" style="display:none;width:120px;text-align:center">₱0.00</span><input id="unit_price" type="hidden" value="0"></td>
            <td><span id="line_total_display" style="display:inline-block;width:120px;text-align:center"></span></td>
            <td><button id="add_button" class="btn" onclick="addItem()" style="display:none">Add</button></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card">
  <h3>Order Items</h3>

  <div class="table-responsive" style="overflow:auto;">
    <table class="table items-table" style="width:100%; border-collapse:collapse;">
      <thead>
        <tr>
          <th style="width:48%; text-align:left; padding:8px 10px;">Product</th>
          <th style="width:10%; text-align:center; padding:8px 10px;">Qty</th>
          <th style="width:15%; text-align:right; padding:8px 10px;">Unit Price</th>
          <th style="width:15%; text-align:right; padding:8px 10px;">Line Total</th>
          <th style="width:12%; text-align:center; padding:8px 10px;">Actions</th>
        </tr>
      </thead>

      <tbody id="items_body">
        <!-- rows inserted by JS: ensure each row has 5 <td> matching the 5 headers above -->
      </tbody>

      <tfoot>
        <!-- Subtotal row -->
        <tr style="background:#f8f9fa;">
          <td colspan="2"></td>
          <td style="text-align:right; font-weight:600; padding:8px 10px;">Subtotal</td>
          <td style="text-align:right; font-weight:600; padding:8px 10px;">₱<span id="subtotal">0.00</span></td>
          <td></td>
        </tr>

        <!-- Discount row -->
        <tr style="background:#fff;">
          <td colspan="2"></td>
          <td style="text-align:right; padding:8px 10px;">Discount (%)</td>
          <td style="text-align:right; padding:8px 10px;">
            <input id="discount" type="number" step="0.01" value="0" style="width:80px; text-align:right;" oninput="calcTotals()">
          </td>
          <td></td>
        </tr>

        <!-- Tax row -->
        <tr style="background:#fff;">
          <td colspan="2"></td>
          <td style="text-align:right; padding:8px 10px;">Tax (%)</td>
          <td style="text-align:right; padding:8px 10px;">
            <input id="tax" type="number" step="0.01" value="0" style="width:80px; text-align:right;" oninput="calcTotals()">
          </td>
          <td></td>
        </tr>

        <!-- Total row -->
        <tr style="background:#f1f1f1; font-weight:700;">
          <td colspan="2"></td>
          <td style="text-align:right; padding:8px 10px;">Total</td>
          <td style="text-align:right; padding:8px 10px;">₱<span id="total">0.00</span></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div style="margin-top:12px; text-align:right;">
    <button id="saveOrderBtn" class="btn btn-primary" style="display:none" onclick="saveOrder()">Save Order</button>
  </div>
</div>

    <div class="card">
      <h3>Existing Orders</h3>
      <div id="orders_list"></div>
    </div>
  </div>

  <script>
  let items = [];
  let currentOrderId = null;
  let products = [];

  async function loadCustomers(){
    try {
      const res = await fetch('Module8/api/customers.php?action=list');
      const j = await res.json();
      if (j.status === 'success') {
        const sel = document.getElementById('customer');
        sel.innerHTML = '<option value="">-- Select Customer --</option>' + j.data.map(c=>`<option value="${c.id}">${c.name}</option>`).join('');
      } else {
        alert('Failed to load customers: ' + (j.message || 'Unknown error'));
      }
    } catch (error) {
      alert('Error loading customers: ' + error.message);
      console.error('loadCustomers error:', error);
    }
  }

  async function loadProducts(){
    try {
      const res = await fetch('Module8/api/products.php?action=list');
      const j = await res.json();
      if (j.status === 'success') {
        products = j.data;
        const sel = document.getElementById('product_select');
        sel.innerHTML = '<option value="">Select Product</option>' + j.data.map(p => `<option value="${p.id}" data-price="${p.unit_price}">${p.name} (${p.sku})</option>`).join('');
      } else {
        alert('Failed to load products: ' + (j.message || 'Unknown error'));
      }
    } catch (error) {
      alert('Error loading products: ' + error.message);
      console.error('loadProducts error:', error);
    }
  }

  function addItem(){
    const pid = document.getElementById('product_select').value;
    if (!pid) { alert('Select a product'); return; }
    const product = products.find(p => p.id == pid);
    if (!product) { alert('Product not found'); return; }
    const pname = product.name + ' (' + product.sku + ')';
    const qty = parseFloat(document.getElementById('qty').value) || 1;
    const up = parseFloat(product.unit_price);
    const line = {product_id: pid, description: pname, qty: qty, unit_price: up, line_total: (qty*up)};
    items.push(line);
    renderItems();
    document.getElementById('product_select').value=''; document.getElementById('unit_price_display').style.display = 'none'; document.getElementById('qty').value=1; document.getElementById('line_total_display').innerText = ''; document.getElementById('line_total_display').style.display = 'none';
  }

  function updateLineTotal(){
    const qty = parseFloat(document.getElementById('qty').value) || 0;
    const up = parseFloat(document.getElementById('unit_price').value) || 0;
    const total = qty * up;
    document.getElementById('line_total_display').innerText = '₱' + total.toFixed(2);
  }

  function onProductChange(){
    const pid = document.getElementById('product_select').value;
    if (pid) {
      const product = products.find(p => p.id == pid);
      if (product) {
        document.getElementById('unit_price').value = product.unit_price;
        document.getElementById('unit_price_display').innerText = '₱' + parseFloat(product.unit_price).toFixed(2);
        document.getElementById('unit_price_display').style.display = 'inline-block';
        document.getElementById('line_total_display').style.display = 'inline-block';
        document.getElementById('add_button').style.display = 'inline-block';
        updateLineTotal();
      }
    } else {
      document.getElementById('unit_price').value = '0';
      document.getElementById('unit_price_display').style.display = 'none';
      document.getElementById('line_total_display').style.display = 'none';
      document.getElementById('add_button').style.display = 'none';
      updateLineTotal();
    }
  }

  function renderItems(){
    const body = document.getElementById('items_body');
    if (!body) return;
    body.innerHTML = items.map((it, idx) => {
      // create consistent columns: Product | Qty | Unit Price | Line Total | Actions
      const desc = (it.description || '').replace(/</g,'&lt;').replace(/>/g,'&gt;');
      const qty = Number(it.qty || 0);
      const up = Number(it.unit_price || 0);
      const lt = Number(it.line_total || (qty * up));
      return `
        <tr>
          <td style="padding:8px 10px; vertical-align:middle;">${desc}</td>
          <td style="text-align:center; padding:8px 10px; vertical-align:middle;">${qty}</td>
          <td style="text-align:right; padding:8px 10px; vertical-align:middle;">₱${up.toFixed(2)}</td>
          <td style="text-align:right; padding:8px 10px; vertical-align:middle;">₱${lt.toFixed(2)}</td>
          <td style="text-align:center; padding:8px 10px; vertical-align:middle;">
            <button onclick="removeItem(${idx})" class="btn" style="padding:6px 10px">Remove</button>
          </td>
        </tr>
      `;
    }).join('');
    // Recalculate totals and Save button visibility
    calcTotals();
    updateSaveButtonVisibility();
  }

  function removeItem(i){ items.splice(i,1); renderItems(); }

  function updateSaveButtonVisibility(){
    const btn = document.getElementById('saveOrderBtn');
    if (!btn) return;
    if ((items && items.length > 0) || currentOrderId) {
      btn.style.display = 'inline-block';
    } else {
      btn.style.display = 'none';
    }
  }

  function calcTotals(){
    const subtotalVal = items.reduce((s, it) => {
      const lt = Number(it.line_total || 0);
      return s + (isFinite(lt) ? lt : 0);
    }, 0);

    // update subtotal display
    const subEl = document.getElementById('subtotal');
    if (subEl) subEl.innerText = subtotalVal.toFixed(2);

    // read discount and tax (percent)
    const discountPercent = Math.max(0, parseFloat(document.getElementById('discount').value || 0));
    const taxPercent = Math.max(0, parseFloat(document.getElementById('tax').value || 0));

    // apply discount then tax (percentage)
    const discounted = subtotalVal * (1 - (discountPercent / 100));
    const totalVal = discounted * (1 + (taxPercent / 100));

    const totalEl = document.getElementById('total');
    if (totalEl) totalEl.innerText = totalVal.toFixed(2);

    // ensure save button visibility updated (in case discount/tax input changed)
    updateSaveButtonVisibility();
  }

  // keep removeItem as you have but call renderItems afterwards (already present in your code)
  function removeItem(i){
    items.splice(i,1);
    renderItems();
  }

  function openOrderSummaryModal(){ document.getElementById('orderSummaryModal').style.display='flex'; } 
  function closeOrderSummaryModal(){ document.getElementById('orderSummaryModal').style.display='none'; }

  async function saveOrder(){
    const customer_id = document.getElementById('customer').value;
    if (!customer_id) { alert('Select a customer'); return; }
    const order_number = document.getElementById('order_number').value;
    const subtotal = parseFloat(document.getElementById('subtotal').innerText) || 0;
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const tax = parseFloat(document.getElementById('tax').value) || 0;
    const total = parseFloat(document.getElementById('total').innerText) || 0;
    const payload = new URLSearchParams();
    payload.append('customer_id', customer_id);
    payload.append('order_number', order_number);
    payload.append('subtotal', subtotal);
    payload.append('discount', discount);
    payload.append('tax', tax);
    payload.append('total', total);
    payload.append('items', JSON.stringify(items));
    if (currentOrderId) payload.append('id', currentOrderId);
    const res = await fetch('Module8/api/sales_orders.php?action=save',{method:'POST',body:payload});
    const j = await res.json();
    if (j.status==='success'){
      currentOrderId = j.id;
      alert('Order saved');
      // Clear add item section
      document.getElementById('product_select').value = '';
      document.getElementById('qty').value = 1;
      document.getElementById('unit_price').value = '0';
      document.getElementById('unit_price_display').style.display = 'none';
      document.getElementById('line_total_display').innerText = '';
      document.getElementById('add_button').style.display = 'none';
      // Clear order items section
      items = [];
      renderItems();
      loadOrders();
    } else {
      alert('Save failed: '+(j.message||JSON.stringify(j)));
    }
  }

  async function loadOrders(){
    try {
      const r = await fetch('Module8/api/sales_orders.php?action=list');
      const j = await r.json();
      if (j.status === 'success') {
        const el = document.getElementById('orders_list');
        el.innerHTML = '<table class="table orders-table"><thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead><tbody>' + j.data.map(o => {
          let actions = `<button onclick="loadOrder(${o.id})" class="btn" title="View Order" style="background:none; border:none;"><i class="material-icons">visibility</i></button>`;
          if (o.status === 'pending') {
            actions += ` <button onclick="editOrder(${o.id})" class="btn" title="Edit Order" style="background:none; color:blue; border:none;"><i class="material-icons">edit</i></button>`;
            actions += ` <button onclick="deleteOrder(${o.id})" class="btn" title="Delete Order" style="background:none; color:red; border:none;"><i class="material-icons">delete</i></button>`;
            actions += ` <button onclick="confirmOrder(${o.id})" class="btn" title="Confirm Order" style="background:none; color:green; border:none;"><i class="material-icons">check</i></button>`;
          } else if (o.status === 'processed') {
            actions += `<div class="dropdown"><button class="menu-icon" onclick="toggleDropdown(${o.id})">⋮</button><div id="dropdown-${o.id}" class="dropdown-content"><a onclick="markAsShipped(${o.id})">Mark as Shipped</a><a onclick="markAsDelivered(${o.id})">Mark as Delivered</a></div></div>`;
          } else if (o.status === 'shipped') {
            actions += `<div class="dropdown"><button class="menu-icon" onclick="toggleDropdown(${o.id})">⋮</button><div id="dropdown-${o.id}" class="dropdown-content"><a onclick="markAsDelivered(${o.id})">Mark as Delivered</a></div></div>`;
          }
          return `<tr><td data-label="Order #">${o.order_number}</td><td data-label="Customer">${o.customer_name || ''}</td><td data-label="Total">₱${o.total}</td><td data-label="Status" class="status-${o.status}">${o.status.toUpperCase()}</td><td data-label="Actions">${actions}</td></tr>`;
        }).join('') + '</tbody></table>';
      } else {
        alert('Failed to load orders: ' + (j.message || 'Unknown error'));
      }
    } catch (error) {
      alert('Error loading orders: ' + error.message);
      console.error('loadOrders error:', error);
    }
  }

  async function loadOrder(id){
    try {
      const r = await fetch('Module8/api/sales_orders.php?action=get&id='+id);
      const j = await r.json();
      if (j.status === 'success'){
        // Populate summary modal
        document.getElementById('summary_order_number').textContent = j.data.order_number;
        document.getElementById('summary_customer').textContent = j.data.customer_name || 'N/A';
        document.getElementById('summary_subtotal').textContent = parseFloat(j.data.subtotal).toFixed(2);
        document.getElementById('summary_discount').textContent = parseFloat(j.data.discount).toFixed(2);
        document.getElementById('summary_tax').textContent = parseFloat(j.data.tax).toFixed(2);
        document.getElementById('summary_total').textContent = parseFloat(j.data.total).toFixed(2);
        const itemsList = document.getElementById('summary_items_body');
        itemsList.innerHTML = (j.items || []).map(it => `
          <div class="item" style="display:flex; flex-direction:column; align-items:flex-start; padding:12px 0; border-bottom:1px solid #eee; margin-bottom:4px;">
            <span style="font-weight:500; margin-bottom:8px;">${it.description}</span>
            <span style="font-size:0.9em; color:#666; margin-top:4px;">Qty: ${parseFloat(it.qty).toFixed(2)}</span>
            <span style="font-size:0.9em; color:#666; margin-top:4px;">Unit: ₱${parseFloat(it.unit_price).toFixed(2)}</span>
            <span style="font-weight:500; color:#000; margin-top:4px;">Total: ₱${parseFloat(it.line_total).toFixed(2)}</span>
          </div>
        `).join('');
        openOrderSummaryModal();
      } else {
        alert('Failed to load order: ' + (j.message || 'Unknown error'));
      }
    } catch (error) {
      alert('Error loading order: ' + error.message);
      console.error('loadOrder error:', error);
    }
  }

  async function confirmOrderPrompt(){
    if (!currentOrderId) { alert('Save order first'); return; }
    if (!confirm('Confirm and process this order?')) return;
    await confirmOrder(currentOrderId);
  }

  async function confirmOrder(id){
    const resp = await fetch('Module8/api/sales_orders.php?action=confirm',{method:'POST',body:new URLSearchParams({id})});
    const j = await resp.json();
    if (j.status === 'success') {
      alert('Order Processed');
    } else {
      alert('Confirmation failed: ' + (j.message || JSON.stringify(j)));
    }
    loadOrders();
  }

  async function markAsShipped(id) {
    if (!confirm('Mark this order as shipped?')) return;
    const resp = await fetch('Module8/api/sales_orders.php?action=update_status', {method:'POST', body: new URLSearchParams({id, status: 'shipped'})});
    const j = await resp.json();
    if (j.status === 'success') {
      alert('Order marked as shipped');
    } else {
      alert('Failed to update status: ' + (j.message || JSON.stringify(j)));
    }
    loadOrders();
  }

  async function editOrder(id) {
    try {
      const r = await fetch('Module8/api/sales_orders.php?action=get&id=' + id);
      const j = await r.json();
      if (j.status === 'success') {
        // Populate form with order data
        document.getElementById('customer').value = j.data.customer_id;
        document.getElementById('order_number').value = j.data.order_number;
        document.getElementById('discount').value = j.data.discount;
        document.getElementById('tax').value = j.data.tax;
        // Populate items
        items = (j.items || []).map(it => ({
          product_id: it.product_id,
          description: it.description,
          qty: parseFloat(it.qty),
          unit_price: parseFloat(it.unit_price),
          line_total: parseFloat(it.line_total)
        }));
        renderItems();
        currentOrderId = id;
        // Scroll to top or focus on form
        window.scrollTo(0, 0);
      } else {
        alert('Failed to load order for editing: ' + (j.message || 'Unknown error'));
      }
    } catch (error) {
      alert('Error loading order for editing: ' + error.message);
      console.error('editOrder error:', error);
    }
  }

  async function deleteOrder(id) {
    if (!confirm('Are you sure you want to delete this order? This action cannot be undone.')) return;
    try {
      const resp = await fetch('Module8/api/sales_orders.php?action=delete', {method:'POST', body: new URLSearchParams({id})});
      const j = await resp.json();
      if (j.status === 'success') {
        alert('Order deleted successfully');
        loadOrders();
      } else {
        alert('Failed to delete order: ' + (j.message || JSON.stringify(j)));
      }
    } catch (error) {
      alert('Error deleting order: ' + error.message);
      console.error('deleteOrder error:', error);
    }
  }

  async function markAsDelivered(id) {
    if (!confirm('Mark this order as delivered?')) return;
    const resp = await fetch('Module8/api/sales_orders.php?action=update_status', {method:'POST', body: new URLSearchParams({id, status: 'delivered'})});
    const j = await resp.json();
    if (j.status === 'success') {
      alert('Order marked as delivered');
    } else {
      alert('Failed to update status: ' + (j.message || JSON.stringify(j)));
    }
    loadOrders();
  }

  function toggleDropdown(id) {
    const dropdown = document.getElementById(`dropdown-${id}`);
    const isVisible = dropdown.style.display === 'block';
    // Hide all dropdowns first
    document.querySelectorAll('.dropdown-content').forEach(d => d.style.display = 'none');
    if (!isVisible) {
      dropdown.style.display = 'block';
    }
  }

  document.addEventListener('click', function(event) {
    if (!event.target.matches('.menu-icon')) {
      const dropdowns = document.querySelectorAll('.dropdown-content');
      dropdowns.forEach(d => d.style.display = 'none');
    }
  });

  // init
  loadCustomers(); loadProducts(); loadOrders();
  </script>
  <!-- Customer Modal -->
  <div id="customerModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);justify-content:center;align-items:center;z-index:200">
    <div style="background:#fff;padding:20px;border-radius:8px;width:420px;">
      <h3>New Customer</h3>
      <form id="customerForm">
        <label>Name<input id="cust_name" class="line-input" required></label>
        <label>Code<input id="cust_code" class="line-input"></label>
        <label>Notes<textarea id="cust_notes" class="line-input"></textarea></label>
        <div style="margin-top:8px;text-align:right">
          <button type="button" class="btn" onclick="closeCustomerModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Create</button>
        </div>
      </form>
    </div>
  </div>
  <!-- Order Summary Modal -->
    <div id="orderSummaryModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);justify-content:center;align-items:center;z-index:200">
      <div style="background:#fff;padding:20px;border-radius:12px;width:650px;max-height:80vh;overflow-y:auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <h1>Order Summary</h1>
        <div class="card" style="background:#f8f9fa; padding:20px; border-radius:8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom:20px;">
          <p style="font-size:1.1em; margin-bottom:4px;"><strong>Order #:</strong> <span id="summary_order_number"></span></p>
          <p style="font-size:1em; color:#666;"><strong>Customer:</strong> <span id="summary_customer"></span></p>
        </div>
        <div class="card" style="background:#fff; padding:20px; border-radius:8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom:20px;">
          <h2 style="margin-bottom:15px;">Item</h2>
          <div id="summary_items_body"></div>
        </div>
        <div class="card" style="background:#f8f9fa; padding:20px; border-radius:8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
          <h2 style="margin-bottom:15px;">Summary</h2>
          <div class="summary-row" style="display:flex;justify-content:space-between;margin-bottom:8px;">
            <span style="font-weight:bold; font-size:1em;">Subtotal:</span><span style="font-weight:500; font-size:1em; color:#000;">₱<span id="summary_subtotal"></span></span>
          </div>
          <div class="summary-row" style="display:flex;justify-content:space-between;margin-bottom:8px;">
            <span style="font-weight:bold; font-size:1em;">Discount:</span><span style="font-weight:500; color:#dc3545; font-size:1em;"><span id="summary_discount"></span>%</span>
          </div>
          <div class="summary-row" style="display:flex;justify-content:space-between;margin-bottom:8px;">
            <span style="font-weight:bold; font-size:1em;">Tax:</span><span style="font-weight:500; font-size:1em; color:#000;"><span id="summary_tax"></span>%</span>
          </div>
          <div class="summary-row" style="display:flex;justify-content:space-between;font-weight:bold; padding-top:8px; border-top:1px solid #ddd;">
            <span style="font-weight:bold; font-size:1.1em;">Total:</span><span style="font-weight:bold; font-size:1.1em; color:#000;">₱<span id="summary_total"></span></span>
          </div>
        </div>
        <div style="margin-top:20px;text-align:right">
          <button class="btn" onclick="closeOrderSummaryModal()">Close</button>
        </div>
      </div>
    </div>

  </script>
</body>
</html>
