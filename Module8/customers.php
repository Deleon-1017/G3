<?php
require '../db.php';
require '../shared/config.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Customers</title>
  <link rel="stylesheet" href="styles.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <base href="<?php echo BASE_URL; ?>">
</head>
<body>
  <?php include '../shared/sidebar.php'; ?>
  <div class="container" style="margin-left:18rem;">
    <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
      <h1>Customers</h1>
      <div class="header-actions">
  <button id="btnNewCustomer" class="btn" onclick="openCreateModal()" aria-haspopup="dialog" aria-controls="customerModal">+ New Customer</button>
      </div>
    </div>
    <div id="list"></div>
  </div>

  <!-- Customer Modal -->
  <div id="customerModal" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="modal-overlay" aria-hidden="true"></div>
    <div class="modal-card">
      <div class="modal-header">
        <h3 id="modalTitle">New Customer</h3>
        <button type="button" class="close-btn" onclick="closeCustomerModal()">&times;</button>
      </div>
      <form id="customerForm" novalidate>
        <input type="hidden" id="cust_id" name="id">
        <label>Code<input id="cust_code" name="code" class="line-input" readonly></label>
        <label>Name<input id="cust_name" name="name" class="line-input" required></label>
        <div class="form-row">
          <label style="flex:1">Email<input id="cust_email" name="email" type="email" class="line-input"></label>
          <label style="flex:1">Phone<input id="cust_phone" name="phone" class="line-input"></label>
        </div>
        <label>Address<textarea id="cust_address" name="address" class="line-input" rows="3"></textarea></label>
        <div class="form-row">
          <label style="flex:1">Credit Limit<input id="cust_credit_limit" name="credit_limit" type="number" step="0.01" class="line-input" value="10000"></label>
          <label style="flex:1">Outstanding Balance<input id="cust_outstanding_balance" name="outstanding_balance" type="number" step="0.01" class="line-input" value="0"></label>
        </div>
        <div class="error" id="customerError" style="display:none"></div>
        <div style="margin-top:8px;text-align:right;display:flex;gap:8px;justify-content:flex-end;align-items:center">
          <button type="button" class="btn" id="modalCancel">Cancel</button>
          <button type="submit" class="btn btn-primary" id="modalSave">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  async function load(){
    const res = await fetch('Module8/api/customers.php?action=list');
    const j = await res.json();
    const el = document.getElementById('list');
    if (j.status==='success'){
      el.innerHTML = '<table class="table"><thead><tr><th>Code</th><th>Name</th><th>Email</th><th>Phone</th><th>Outstanding Balance</th><th>Actions</th></tr></thead><tbody>'+j.data.map(c=>`<tr><td>${c.code||''}</td><td>${c.name}</td><td>${c.email||''}</td><td>${c.phone||''}</td><td>${c.outstanding_balance || '0'}</td><td><button onclick="openEditModal(${c.id})" class="btn" title="Edit" style="background:none; border:none;"><i class="material-icons" style="color: #007bff;">edit</i></button> <button onclick="del(${c.id})" class="btn" title="Delete" style="background:none; border:none;"><i class="material-icons" style="color: #dc3545;">delete</i></button></td></tr>`).join('')+'</tbody></table>';
    }
  }

  console.log('Module8/customers.js loaded');

  // Lazy modal initialization to avoid null reference errors if DOM isn't ready
  function ensureModalInit(){
    console.log('ensureModalInit called, initialized=', !!window._customerModalInitialized);
    if (window._customerModalInitialized) return;
    const customerModal = document.getElementById('customerModal');
    console.log('customerModal element:', customerModal);
    if (!customerModal) {
      // If modal element is not available yet, try again on DOMContentLoaded
      document.addEventListener('DOMContentLoaded', ensureModalInit);
      return;
    }
    window.customerModal = customerModal;
    window.modalOverlay = customerModal.querySelector('.modal-overlay');
    window.modalSaveBtn = document.getElementById('modalSave');
    window.modalCancelBtn = document.getElementById('modalCancel');
    window.customerError = document.getElementById('customerError');

    window.showModal = function(){
      console.log('showModal called');
      window.customerModal.classList.add('modal-show');
      window.customerModal.setAttribute('aria-hidden','false');
      document.body.style.overflow = 'hidden';
      setTimeout(()=>document.getElementById('cust_name').focus(),80);
    };
    window.hideModal = function(){
      console.log('hideModal called');
      window.customerModal.classList.remove('modal-show');
      window.customerModal.setAttribute('aria-hidden','true');
      document.body.style.overflow = '';
      window.customerError.style.display = 'none';
    };

    // overlay/cancel/escape handlers (bound once)
    if (window.modalOverlay) window.modalOverlay.addEventListener('click', window.hideModal);
    if (window.modalCancelBtn) window.modalCancelBtn.addEventListener('click', window.hideModal);
    document.addEventListener('keydown', function(e){ if (e.key==='Escape' && window.customerModal && window.customerModal.getAttribute('aria-hidden')==='false') window.hideModal(); });

    // basic focus trap: keep focus within the modal
    customerModal.addEventListener('keydown', function(e){
      if (e.key !== 'Tab') return;
      const focusable = Array.from(customerModal.querySelectorAll('a[href],button:not([disabled]),textarea, input, select')).filter(Boolean);
      if (!focusable.length) return;
      const idx = focusable.indexOf(document.activeElement);
      if (e.shiftKey && idx === 0){ focusable[focusable.length-1].focus(); e.preventDefault(); }
      else if (!e.shiftKey && idx === focusable.length-1){ focusable[0].focus(); e.preventDefault(); }
    });

    window._customerModalInitialized = true;
    // Bind top-right New Customer button if present
    const btnNew = document.getElementById('btnNewCustomer');
    console.log('btnNew element:', btnNew);
    if (btnNew) btnNew.addEventListener('click', openCreateModal);
  }

  function openCreateModal(){
    console.log('openCreateModal called');
    ensureModalInit();
    document.getElementById('modalTitle').innerText = 'New Customer';
    document.getElementById('cust_id').value = '';
    document.getElementById('cust_code').value = '';
    document.getElementById('cust_name').value = '';
    document.getElementById('cust_email').value = '';
    document.getElementById('cust_phone').value = '';
    document.getElementById('cust_address').value = '';
    document.getElementById('cust_credit_limit').value = '10000';
    document.getElementById('cust_outstanding_balance').value = '0';
    // if showModal isn't ready yet, ensureModalInit will have scheduled initialization
    if (window.showModal) window.showModal();
  }

  async function openEditModal(id){
    const r = await fetch('Module8/api/customers.php?action=get&id='+id);
    const j = await r.json();
    if (j.status==='success'){
      document.getElementById('modalTitle').innerText = 'Edit Customer';
  document.getElementById('cust_id').value = j.data.id;
  document.getElementById('cust_code').value = j.data.code || '';
  document.getElementById('cust_name').value = j.data.name || '';
  document.getElementById('cust_email').value = j.data.email || '';
  document.getElementById('cust_phone').value = j.data.phone || '';
  document.getElementById('cust_address').value = j.data.address || '';
  document.getElementById('cust_credit_limit').value = j.data.credit_limit || '10000';
  document.getElementById('cust_outstanding_balance').value = j.data.outstanding_balance || '0';
      ensureModalInit(); if (window.showModal) window.showModal();
    } else {
      alert('Unable to load customer');
    }
  }

  function closeCustomerModal(){ if (window.hideModal) window.hideModal(); }

  document.getElementById('customerForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const id = document.getElementById('cust_id').value;
    const name = document.getElementById('cust_name').value.trim();
    const email = document.getElementById('cust_email').value.trim();
    const phone = document.getElementById('cust_phone').value.trim();
    const address = document.getElementById('cust_address').value.trim();

  // inline validation (use safe lookup for the error container)
  const ce = window.customerError || document.getElementById('customerError');
  if (!name) { if (ce) { ce.innerText = 'Name is required'; ce.style.display='block'; } document.getElementById('cust_name').focus(); return; }
  if (!email && !phone) { if (ce) { ce.innerText = 'Provide at least an email or phone number'; ce.style.display='block'; } return; }

    const code = document.getElementById('cust_code').value;
    const credit_limit = document.getElementById('cust_credit_limit').value;
    const outstanding_balance = document.getElementById('cust_outstanding_balance').value;
    const fd = new URLSearchParams({action:'save',name,email,phone,address,credit_limit,outstanding_balance});
    if (code) fd.append('code', code);
    if (id) fd.append('id', id);
    try{
      ensureModalInit();
      if (window.modalSaveBtn){ window.modalSaveBtn.disabled = true; window.modalSaveBtn.innerText = 'Saving...'; }
      const res = await fetch('Module8/api/customers.php',{method:'POST',body:fd});
      const j = await res.json();
      if (j.status==='success'){
        if (window.hideModal) window.hideModal();
        load();
      } else {
        const ce2 = window.customerError || document.getElementById('customerError'); if (ce2) { ce2.innerText = j.message || 'Save failed'; ce2.style.display='block'; }
      }
  }catch(err){ const ce3 = window.customerError || document.getElementById('customerError'); if (ce3){ ce3.innerText = err.message || 'Save failed'; ce3.style.display='block'; } }
    finally{ if (window.modalSaveBtn){ window.modalSaveBtn.disabled = false; window.modalSaveBtn.innerText = 'Save'; } }
  });

  // overlay/cancel/escape handlers
  // Note: overlay/cancel/escape handlers and focus trap are set up in ensureModalInit()

  // Ensure modal initialization runs now so the New Customer button is bound immediately
  try{ ensureModalInit(); console.log('ensureModalInit invoked at script end'); }catch(e){ console.error('ensureModalInit failed', e); }

  async function del(id){ if(!confirm('Delete?')) return; const r=await fetch('Module8/api/customers.php?action=delete',{method:'POST',body:new URLSearchParams({id})}); const j=await r.json(); if (j.status==='success') load(); else alert('Delete failed'); }

  load();

  // Handle URL parameter for viewing a specific customer - removed modal opening
  </script>
</body>
</html>
