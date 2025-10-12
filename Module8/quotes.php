<?php
require '../db.php';
require '../shared/config.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Quotes</title>
  <link rel="stylesheet" href="styles.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <base href="<?php echo BASE_URL; ?>">
</head>
<body>
  <?php include '../shared/sidebar.php'; ?>
  <div class="container" style="margin-left:18rem;">
    <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
      <h1>Quotations</h1>
      <div class="header-actions">
        <button class="btn" onclick="openCreateQuoteModal()">+ New Quote</button>
      </div>
    </div>
    <div id="list"></div>
  </div>

  <!-- Quote Modal -->
  <div id="quoteModal" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="modal-overlay" aria-hidden="true"></div>
    <div class="modal-card">
      <div class="modal-header">
        <h3 id="modalTitle">New Quote</h3>
        <button type="button" class="close-btn" onclick="closeQuoteModal()">&times;</button>
      </div>
      <form id="quoteForm" novalidate onkeydown="if(event.key==='Enter' && event.target.tagName !== 'BUTTON') event.preventDefault();">
        <label>Customer<select id="quote_customer_id" name="customer_id" class="line-input" required><option value="">Select Customer</option></select></label>
        <div style="margin:16px 0;">
          <h4>Line Items</h4>
          <table id="itemsTable" class="table" style="margin-bottom:8px;">
            <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Line Total</th><th>Actions</th></tr></thead>
            <tbody id="itemsBody"></tbody>
          </table>
          <button type="button" class="btn" onclick="addItemRow()">+ Add Item</button>
        </div>
        <div class="form-row">
          <label style="flex:1">Subtotal<input id="quote_subtotal" name="subtotal" type="number" class="line-input" readonly></label>
          <label style="flex:1">Discount<input id="quote_discount" name="discount" type="number" class="line-input" value="0" step="0.01"></label>
        </div>
        <div class="form-row">
          <label style="flex:1">Tax<input id="quote_tax" name="tax" type="number" class="line-input" value="0" step="0.01"></label>
          <label style="flex:1">Total<input id="quote_total" name="total" type="number" class="line-input" readonly></label>
        </div>
        <label>Expiry Date<input id="quote_expiry_date" name="expiry_date" type="date" class="line-input"></label>
        <input type="hidden" id="quote_id" name="id">
        <input type="hidden" id="quote_quote_number" name="quote_number">
        <div class="error" id="quoteError" style="display:none"></div>
        <div style="margin-top:8px;text-align:right;display:flex;gap:8px;justify-content:flex-end;align-items:center">
          <button type="button" class="btn" id="modalCancel">Cancel</button>
          <button type="submit" class="btn btn-primary" id="modalSave">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  async function load(){ const r=await fetch('Module8/api/quotes.php?action=list'); const j=await r.json(); const el=document.getElementById('list'); if(j.status==='success'){ el.innerHTML='<table class="table"><thead><tr><th>Quote #</th><th>Customer</th><th>Total</th><th>Status</th><th>Expiry Date</th><th>Actions</th></tr></thead><tbody>'+j.data.map(q=>`<tr><td>${q.quote_number}</td><td>${q.customer_name||''}</td><td>₱${q.total}</td><td>${q.status}</td><td>${q.expiry_date||''}</td><td><button onclick="edit(${q.id})" class="btn" title="Edit" style="background:none; border:none;"><i class="material-icons" style="color: #007bff;">edit</i></button> <button onclick="deleteQuote(${q.id})" class="btn" title="Delete" style="background:none; border:none;"><i class="material-icons" style="color: #dc3545;">delete</i></button></td></tr>`).join('')+'</tbody></table>'; } }

  async function loadCustomers(){
    const r = await fetch('Module8/api/customers.php?action=list');
    const j = await r.json();
    if (j.status === 'success') {
      const select = document.getElementById('quote_customer_id');
      select.innerHTML = '<option value="">Select Customer</option>' + j.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    }
  }

  async function loadProducts(){
    const r = await fetch('Module8/api/products.php?action=list');
    const j = await r.json();
    if (j.status === 'success') {
      window.products = j.data;
    } else {
      window.products = [];
    }
  }
  async function openEditQuoteModal(id){
    const r = await fetch('Module8/api/quotes.php?action=get&id='+id);
    const j = await r.json();
    if (j.status==='success'){
      document.getElementById('modalTitle').innerText = 'Edit Quote';
      document.getElementById('quote_id').value = id;
      document.getElementById('quote_quote_number').value = j.data.quote_number || '';
      document.getElementById('quote_subtotal').value = j.data.subtotal || '';
      document.getElementById('quote_discount').value = j.data.discount || '0';
      document.getElementById('quote_tax').value = j.data.tax || '0';
      document.getElementById('quote_total').value = j.data.total || '';
      document.getElementById('quote_expiry_date').value = j.data.expiry_date || '';
      window.items = j.items || [];
      ensureModalInit();
      await loadCustomers();
      document.getElementById('quote_customer_id').value = j.data.customer_id || '';
      document.querySelector('label').innerHTML = `<h3>Customer: ${j.data.customer_name}</h3>`;
      await loadProducts();
      document.getElementById('itemsBody').innerHTML = '';
      window.items.forEach((item, idx) => {
        const tbody = document.getElementById('itemsBody');
        const row = document.createElement('tr');
        const productOptions = window.products.map(p => `<option value="${p.id}" ${p.id == item.product_id ? 'selected' : ''}>${p.name}</option>`).join('');
        row.innerHTML = `
          <td><select class="line-input" onchange="updateItem(${idx})"><option value="">Select Product</option>${productOptions}</select></td>
          <td><input type="number" class="line-input" placeholder="Qty" min="1" value="${item.qty}" oninput="updateItem(${idx})"></td>
          <td><input type="number" class="line-input" placeholder="Unit Price" step="0.01" value="${item.unit_price}" oninput="updateItem(${idx})"></td>
          <td><input type="number" class="line-input" placeholder="Line Total" value="${item.line_total}" readonly></td>
          <td><button type="button" class="btn" onclick="removeItemRow(${idx})">Remove</button></td>
        `;
        tbody.appendChild(row);
      });
      if (window.showModal) window.showModal();
    } else {
      alert('Unable to load quote');
    }
  }

  function edit(id){ openEditQuoteModal(id); }

  async function deleteQuote(id){
    if (!confirm('Are you sure you want to delete this quote?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    try {
      const res = await fetch('Module8/api/quotes.php', {method: 'POST', body: fd});
      const j = await res.json();
      if (j.status === 'success') {
        load();
      } else {
        alert('Delete failed: ' + (j.message || 'Unknown error'));
      }
    } catch (error) {
      alert('Error deleting quote: ' + error.message);
    }
  }

  console.log('Module8/quotes.js loaded');

  // Lazy modal initialization to avoid null reference errors if DOM isn't ready
  function ensureModalInit(){
    console.log('ensureModalInit called, initialized=', !!window._quoteModalInitialized);
    if (window._quoteModalInitialized) return;
    const quoteModal = document.getElementById('quoteModal');
    console.log('quoteModal element:', quoteModal);
    if (!quoteModal) {
      // If modal element is not available yet, try again on DOMContentLoaded
      document.addEventListener('DOMContentLoaded', ensureModalInit);
      return;
    }
    window.quoteModal = quoteModal;
    window.modalOverlay = quoteModal.querySelector('.modal-overlay');
    window.modalSaveBtn = document.getElementById('modalSave');
    window.modalCancelBtn = document.getElementById('modalCancel');
    window.quoteError = document.getElementById('quoteError');

    window.showModal = function(){
      console.log('showModal called');
      window.quoteModal.classList.add('modal-show');
      window.quoteModal.setAttribute('aria-hidden','false');
      document.body.style.overflow = 'hidden';
      setTimeout(()=>document.getElementById('quote_customer_id').focus(),80);
    };
    window.hideModal = function(){
      console.log('hideModal called');
      window.quoteModal.classList.remove('modal-show');
      window.quoteModal.setAttribute('aria-hidden','true');
      document.body.style.overflow = '';
      window.quoteError.style.display = 'none';
    };

    // overlay/cancel/escape handlers (bound once)
    if (window.modalOverlay) window.modalOverlay.addEventListener('click', window.hideModal);
    if (window.modalCancelBtn) window.modalCancelBtn.addEventListener('click', window.hideModal);
    document.addEventListener('keydown', function(e){ if (e.key==='Escape' && window.quoteModal && window.quoteModal.getAttribute('aria-hidden')==='false') window.hideModal(); });

    // basic focus trap: keep focus within the modal
    quoteModal.addEventListener('keydown', function(e){
      if (e.key !== 'Tab') return;
      const focusable = Array.from(quoteModal.querySelectorAll('a[href],button:not([disabled]),textarea, input, select')).filter(Boolean);
      if (!focusable.length) return;
      const idx = focusable.indexOf(document.activeElement);
      if (e.shiftKey && idx === 0){ focusable[focusable.length-1].focus(); e.preventDefault(); }
      else if (!e.shiftKey && idx === focusable.length-1){ focusable[0].focus(); e.preventDefault(); }
    });

    window._quoteModalInitialized = true;
    // Bind top-right New Quote button if present
    const btnNew = document.getElementById('btnNewQuote');
    console.log('btnNew element:', btnNew);
    if (btnNew) btnNew.addEventListener('click', openCreateQuoteModal);
  }

  async function openCreateQuoteModal(){
    console.log('openCreateQuoteModal called');
    ensureModalInit();
    document.getElementById('modalTitle').innerText = 'New Quote';
    document.querySelector('label').innerHTML = 'Customer<select id="quote_customer_id" name="customer_id" class="line-input" required><option value="">Select Customer</option></select>';
    document.getElementById('quote_customer_id').value = '';
    document.getElementById('quote_subtotal').value = '';
    document.getElementById('quote_discount').value = '0';
    document.getElementById('quote_tax').value = '0';
    document.getElementById('quote_total').value = '';
    document.getElementById('quote_expiry_date').value = '';
    document.getElementById('quote_id').value = '';
    document.getElementById('quote_quote_number').value = '';
    window.items = [];
    document.getElementById('itemsBody').innerHTML = '';
    await loadCustomers();
    await loadProducts();
    // if showModal isn't ready yet, ensureModalInit will have scheduled initialization
    if (window.showModal) window.showModal();
  }

  function closeQuoteModal(){ if (window.hideModal) window.hideModal(); }

  function addItemRow(){
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    const idx = window.items.length;
    const productOptions = window.products.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
    row.innerHTML = `
      <td><select class="line-input" onchange="updateItem(${idx})"><option value="">Select Product</option>${productOptions}</select></td>
      <td><input type="number" class="line-input" placeholder="Qty" min="1" value="1" oninput="updateItem(${idx})"></td>
      <td><input type="number" class="line-input" placeholder="Unit Price" step="0.01" oninput="updateItem(${idx})"></td>
      <td><input type="number" class="line-input" placeholder="Line Total" readonly></td>
      <td><button type="button" class="btn" onclick="removeItemRow(${idx})">Remove</button></td>
    `;
    tbody.appendChild(row);
    window.items.push({description:'', qty:1, unit_price:0, line_total:0, product_id:0});
  }

  function removeItemRow(idx){
    document.getElementById('itemsBody').deleteRow(idx);
    window.items.splice(idx, 1);
    calculateTotals();
  }

  function updateItem(idx){
    const row = document.getElementById('itemsBody').rows[idx];
    const select = row.cells[0].querySelector('select');
    const product_id = parseInt(select.value) || 0;
    const product = window.products.find(p => p.id == product_id);
    const desc = product ? product.name : '';
    const qty = parseFloat(row.cells[1].querySelector('input').value) || 0;
    const price = parseFloat(row.cells[2].querySelector('input').value) || 0;
    const total = qty * price;
    row.cells[3].querySelector('input').value = total.toFixed(2);
    window.items[idx] = {description:desc, qty, unit_price:price, line_total:total, product_id};
    calculateTotals();
  }

  function calculateTotals(){
    const subtotal = window.items.reduce((sum, item) => sum + item.line_total, 0);
    const discount = parseFloat(document.getElementById('quote_discount').value) || 0;
    const tax = parseFloat(document.getElementById('quote_tax').value) || 0;
    const total = subtotal - discount + tax;
    document.getElementById('quote_subtotal').value = subtotal.toFixed(2);
    document.getElementById('quote_total').value = total.toFixed(2);
  }

  document.getElementById('quote_discount').addEventListener('input', calculateTotals);
  document.getElementById('quote_tax').addEventListener('input', calculateTotals);

  document.getElementById('quoteForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const customer_id = document.getElementById('quote_customer_id').value.trim();
    const subtotal = parseFloat(document.getElementById('quote_subtotal').value) || 0;
    const discount = parseFloat(document.getElementById('quote_discount').value) || 0;
    const tax = parseFloat(document.getElementById('quote_tax').value) || 0;
    const total = parseFloat(document.getElementById('quote_total').value) || 0;
    const expiry_date = document.getElementById('quote_expiry_date').value;

    // inline validation (use safe lookup for the error container)
    const qe = window.quoteError || document.getElementById('quoteError');
    if (!document.getElementById('quote_id').value && !customer_id) { if (qe) { qe.innerText = 'Customer is required'; qe.style.display='block'; } document.getElementById('quote_customer_id').focus(); return; }
    if (window.items.length === 0) { if (qe) { qe.innerText = 'At least one item is required'; qe.style.display='block'; } return; }

    const fd = new FormData();
    fd.append('action', 'save');
    fd.append('id', document.getElementById('quote_id').value);
    fd.append('quote_number', document.getElementById('quote_quote_number').value);
    fd.append('customer_id', customer_id);
    fd.append('subtotal', subtotal);
    fd.append('discount', discount);
    fd.append('tax', tax);
    fd.append('total', total);
    fd.append('expiry_date', expiry_date);
    fd.append('items', JSON.stringify(window.items));
    try{
      ensureModalInit();
      if (window.modalSaveBtn){ window.modalSaveBtn.disabled = true; window.modalSaveBtn.innerText = 'Saving...'; }
      const res = await fetch('Module8/api/quotes.php',{method:'POST',body:fd});
      const j = await res.json();
      if (j.status==='success'){
        if (window.hideModal) window.hideModal();
        load();
      } else {
        const qe2 = window.quoteError || document.getElementById('quoteError'); if (qe2) { qe2.innerText = j.message || 'Save failed'; qe2.style.display='block'; }
      }
    }catch(err){ const qe3 = window.quoteError || document.getElementById('quoteError'); if (qe3){ qe3.innerText = err.message || 'Save failed'; qe3.style.display='block'; } }
    finally{ if (window.modalSaveBtn){ window.modalSaveBtn.disabled = false; window.modalSaveBtn.innerText = 'Save'; } }
  });

  // Ensure modal initialization runs now so the New Quote button is bound immediately
  try{ ensureModalInit(); console.log('ensureModalInit invoked at script end'); }catch(e){ console.error('ensureModalInit failed', e); }

  load();
  </script>
</body>
</html>
