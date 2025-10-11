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
            <td><input id="qty" type="number" value="1" style="width:80px" onchange="updateLineTotal()"></td>
            <td><input id="unit_price" type="number" step="0.01" value="0" style="width:120px" onchange="updateLineTotal()"></td>
            <td><span id="line_total_display" style="display:inline-block;width:120px;text-align:center">₱0.00</span></td>
            <td><button class="btn" onclick="addItem()">Add</button></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h3>Order Items</h3>
      <table class="table items-table">
        <thead>
          <tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Line Total</th><th>Subtotal</th><th>Discount</th><th>Tax</th><th>Total</th><th></th></tr>
          <tr><td colspan="4"></td><td>₱<span id="subtotal">0.00</span></td><td><input id="discount" type="number" step="0.01" value="0" style="width:120px" onchange="calcTotals()"></td><td><input id="tax" type="number" step="0.01" value="0" style="width:120px" onchange="calcTotals()"></td><td>₱<span id="total">0.00</span></td><td></td></tr>
        </thead>
        <tbody id="items_body"></tbody>
      </table>
      <div style="margin-top:12px">
        <button class="btn btn-primary" onclick="saveOrder()">Save Order</button>
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
    const res = await fetch('Module8/api/customers.php?action=list');
    const j = await res.json();
    const sel = document.getElementById('customer');
    sel.innerHTML = '<option value="">-- Select Customer --</option>' + j.data.map(c=>`<option value="${c.id}">${c.name}</option>`).join('');
  }

  async function loadProducts(){
    const res = await fetch('Module8/api/products.php?action=list');
    const j = await res.json();
    if (j.status === 'success') {
      products = j.data;
      const sel = document.getElementById('product_select');
      sel.innerHTML = '<option value="">Select Product</option>' + j.data.map(p => `<option value="${p.id}" data-price="${p.unit_price}">${p.name} (${p.sku})</option>`).join('');
    }
  }

  function addItem(){
    const pid = document.getElementById('product_select').value;
    if (!pid) { alert('Select a product'); return; }
    const product = products.find(p => p.id == pid);
    const pname = product ? product.name + ' (' + product.sku + ')' : '';
    const qty = parseInt(document.getElementById('qty').value) || 1;
    const up = parseFloat(document.getElementById('unit_price').value) || (product ? product.unit_price : 0);
    const line = {product_id: pid, description: pname, qty: qty, unit_price: up, line_total: (qty*up)};
    items.push(line);
    renderItems();
    document.getElementById('product_select').value=''; document.getElementById('unit_price').value=0; document.getElementById('qty').value=1;
    updateLineTotal();
  }

  function updateLineTotal(){
    const qty = parseInt(document.getElementById('qty').value) || 0;
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
        updateLineTotal();
      }
    } else {
      document.getElementById('unit_price').value = 0;
      updateLineTotal();
    }
  }

  function renderItems(){
    const body = document.getElementById('items_body');
    body.innerHTML = items.map((it,idx)=>`<tr><td>${it.description||''}</td><td style="text-align:center">${it.qty}</td><td style="text-align:right">₱${it.unit_price.toFixed(2)}</td><td style="text-align:right">₱${(it.line_total).toFixed(2)}</td><td style="text-align:center"><button onclick="removeItem(${idx})" class="btn" style="padding:4px 8px">Remove</button></td></tr>`).join('');
    calcTotals();
  }

  function removeItem(i){ items.splice(i,1); renderItems(); }

  function calcTotals(){
    const subtotal = items.reduce((s,it)=>s + (it.line_total||0),0);
    document.getElementById('subtotal').innerText = subtotal.toFixed(2);
    const discount_percent = parseFloat(document.getElementById('discount').value) || 0;
    const tax_percent = parseFloat(document.getElementById('tax').value) || 0;
    const discounted_subtotal = subtotal * (1 - discount_percent / 100);
    const total = discounted_subtotal * (1 + tax_percent / 100);
    document.getElementById('total').innerText = total.toFixed(2);
  }

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
            actions += ` <button onclick="confirmOrder(${o.id})" class="btn" title="Confirm Order" style="background:none; border:none;"><i class="material-icons">check</i></button>`;
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
        <h3>Order Summary</h3>
        <div class="card" style="background:#f8f9fa; padding:20px; border-radius:8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom:20px;">
          <p style="font-size:1.1em; margin-bottom:4px;"><strong>Order #:</strong> <span id="summary_order_number"></span></p>
          <p style="font-size:1em; color:#666;"><strong>Customer:</strong> <span id="summary_customer"></span></p>
        </div>
        <div class="card" style="background:#fff; padding:20px; border-radius:8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom:20px;">
          <h4 style="margin-bottom:12px;">Item</h4>
          <div id="summary_items_body"></div>
        </div>
        <div class="card" style="background:#f8f9fa; padding:20px; border-radius:8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
          <h4 style="margin-bottom:12px;">Summary</h4>
          <div class="summary-row" style="display:flex;justify-content:space-between;">
            <span>Subtotal:</span><span style="font-weight:500; font-size:0.9em; color:#666;">₱<span id="summary_subtotal"></span></span>
          </div>
          <div class="summary-row" style="display:flex;justify-content:space-between;">
            <span>Discount:</span><span style="font-weight:500; color:#dc3545; font-size:0.9em;"><span id="summary_discount"></span>%</span>
          </div>
          <div class="summary-row" style="display:flex;justify-content:space-between;">
            <span>Tax:</span><span style="font-weight:500; font-size:0.9em; color:#666;"><span id="summary_tax"></span>%</span>
          </div>
          <div class="summary-row" style="display:flex;justify-content:space-between;font-weight:bold;">
            <span>Total:</span><span style="font-weight:bold; font-size:0.9em; color:#666;">₱<span id="summary_total"></span></span>
          </div>
        </div>
        <div style="margin-top:20px;text-align:right">
          <button class="btn" onclick="closeOrderSummaryModal()">Close</button>
        </div>
      </div>
    </div>
  <script>
  function openCustomerModal(){ document.getElementById('customerModal').style.display='flex'; }
  function closeCustomerModal(){ document.getElementById('customerModal').style.display='none'; }
  document.getElementById('customerForm').addEventListener('submit', async function(e){ e.preventDefault(); const name=document.getElementById('cust_name').value.trim(); if(!name) return alert('Name required'); const code=document.getElementById('cust_code').value.trim(); const notes=document.getElementById('cust_notes').value.trim(); const fd=new URLSearchParams({action:'save',name,code,notes}); const res=await fetch('Module8/api/customers.php',{method:'POST',body:fd}); const j=await res.json(); if(j.status==='success'){ alert('Customer created'); closeCustomerModal(); loadCustomers(); } else { alert('Create failed: '+(j.message||JSON.stringify(j))); } });

  function openOrderSummaryModal(){ document.getElementById('orderSummaryModal').style.display='flex'; }
  function closeOrderSummaryModal(){ document.getElementById('orderSummaryModal').style.display='none'; }
  </script>
</body>
</html>
