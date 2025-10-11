<?php
require '../db.php';
require '../shared/config.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Support Tickets</title>
  <link rel="stylesheet" href="styles.css">
  <base href="<?php echo BASE_URL; ?>">
</head>
<body>
  <?php include '../shared/sidebar.php'; ?>
  <div class="container" style="margin-left:18rem;">
    <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
      <h1>Support Tickets</h1>
      <div class="header-actions">
        <button class="btn" onclick="openCreateTicketModal()">+ New Ticket</button>
      </div>
    </div>
    <div id="list"></div>
  </div>

  <!-- Ticket Modal -->
  <div id="ticketModal" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="modal-overlay" aria-hidden="true"></div>
    <div class="modal-card">
      <div class="modal-header">
        <h3 id="modalTitle">New Support Ticket</h3>
        <button type="button" class="close-btn" onclick="closeTicketModal()">&times;</button>
      </div>
      <form id="ticketForm" novalidate>
        <label>Customer<select id="ticket_customer_id" name="customer_id" class="line-input"><option value="0">Select Customer (optional)</option></select></label>
        <label>Subject<input id="ticket_subject" name="subject" class="line-input" required></label>
        <label>Description<textarea id="ticket_description" name="description" class="line-input" rows="3" required></textarea></label>
        <div class="error" id="ticketError" style="display:none"></div>
        <div style="margin-top:8px;text-align:right;display:flex;gap:8px;justify-content:flex-end;align-items:center">
          <button type="button" class="btn" id="modalCancel">Cancel</button>
          <button type="submit" class="btn btn-primary" id="modalSave">Create Ticket</button>
        </div>
      </form>
    </div>
  </div>
  <script>
  async function load(){ const r=await fetch('Module8/api/support_tickets.php?action=list'); const j=await r.json(); const el=document.getElementById('list'); if(j.status==='success'){ el.innerHTML='<table class="table"><thead><tr><th>Ticket #</th><th>Subject</th><th>Customer</th><th>Status</th><th>Actions</th></tr></thead><tbody>'+j.data.map(t=>`<tr><td>${t.ticket_number}</td><td>${t.subject}</td><td>${t.customer_name||''}</td><td>${t.status}</td><td><button onclick="view(${t.id})">View</button></td></tr>`).join('')+'</tbody></table>'; } }

  async function loadCustomers(){
    const r = await fetch('Module8/api/customers.php?action=list');
    const j = await r.json();
    if (j.status === 'success') {
      const select = document.getElementById('ticket_customer_id');
      select.innerHTML = '<option value="0">Select Customer (optional)</option>' + j.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    }
  }

  function view(id){ window.location.href='Module8/support_view.php?id='+id; }

  console.log('Module8/support_tickets.js loaded');

  // Lazy modal initialization to avoid null reference errors if DOM isn't ready
  function ensureModalInit(){
    console.log('ensureModalInit called, initialized=', !!window._ticketModalInitialized);
    if (window._ticketModalInitialized) return;
    const ticketModal = document.getElementById('ticketModal');
    console.log('ticketModal element:', ticketModal);
    if (!ticketModal) {
      // If modal element is not available yet, try again on DOMContentLoaded
      document.addEventListener('DOMContentLoaded', ensureModalInit);
      return;
    }
    window.ticketModal = ticketModal;
    window.modalOverlay = ticketModal.querySelector('.modal-overlay');
    window.modalSaveBtn = document.getElementById('modalSave');
    window.modalCancelBtn = document.getElementById('modalCancel');
    window.ticketError = document.getElementById('ticketError');

    window.showModal = function(){
      console.log('showModal called');
      window.ticketModal.classList.add('modal-show');
      window.ticketModal.setAttribute('aria-hidden','false');
      document.body.style.overflow = 'hidden';
      setTimeout(()=>document.getElementById('ticket_customer_id').focus(),80);
    };
    window.hideModal = function(){
      console.log('hideModal called');
      window.ticketModal.classList.remove('modal-show');
      window.ticketModal.setAttribute('aria-hidden','true');
      document.body.style.overflow = '';
      window.ticketError.style.display = 'none';
    };

    // overlay/cancel/escape handlers (bound once)
    if (window.modalOverlay) window.modalOverlay.addEventListener('click', window.hideModal);
    if (window.modalCancelBtn) window.modalCancelBtn.addEventListener('click', window.hideModal);
    document.addEventListener('keydown', function(e){ if (e.key==='Escape' && window.ticketModal && window.ticketModal.getAttribute('aria-hidden')==='false') window.hideModal(); });

    // basic focus trap: keep focus within the modal
    ticketModal.addEventListener('keydown', function(e){
      if (e.key !== 'Tab') return;
      const focusable = Array.from(ticketModal.querySelectorAll('a[href],button:not([disabled]),textarea, input, select')).filter(Boolean);
      if (!focusable.length) return;
      const idx = focusable.indexOf(document.activeElement);
      if (e.shiftKey && idx === 0){ focusable[focusable.length-1].focus(); e.preventDefault(); }
      else if (!e.shiftKey && idx === focusable.length-1){ focusable[0].focus(); e.preventDefault(); }
    });

    window._ticketModalInitialized = true;
    // Bind top-right New Ticket button if present
    const btnNew = document.getElementById('btnNewTicket');
    console.log('btnNew element:', btnNew);
    if (btnNew) btnNew.addEventListener('click', openCreateTicketModal);
  }

  async function openCreateTicketModal(){
    console.log('openCreateTicketModal called');
    ensureModalInit();
    document.getElementById('modalTitle').innerText = 'New Support Ticket';
    document.getElementById('ticket_customer_id').value = '0';
    document.getElementById('ticket_subject').value = '';
    document.getElementById('ticket_description').value = '';
    await loadCustomers();
    // if showModal isn't ready yet, ensureModalInit will have scheduled initialization
    if (window.showModal) window.showModal();
  }

  function closeTicketModal(){ if (window.hideModal) window.hideModal(); }

  document.getElementById('ticketForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const subject = document.getElementById('ticket_subject').value.trim();
    const description = document.getElementById('ticket_description').value.trim();

    // inline validation (use safe lookup for the error container)
    const te = window.ticketError || document.getElementById('ticketError');
    if (!subject) { if (te) { te.innerText = 'Subject is required'; te.style.display='block'; } document.getElementById('ticket_subject').focus(); return; }
    if (!description) { if (te) { te.innerText = 'Description is required'; te.style.display='block'; } document.getElementById('ticket_description').focus(); return; }

    const customer_id = document.getElementById('ticket_customer_id').value;
    const fd = new URLSearchParams({action:'save',customer_id,subject,description});
    try{
      ensureModalInit();
      if (window.modalSaveBtn){ window.modalSaveBtn.disabled = true; window.modalSaveBtn.innerText = 'Creating...'; }
      const res = await fetch('Module8/api/support_tickets.php',{method:'POST',body:fd});
      const j = await res.json();
      if (j.status==='success'){
        if (window.hideModal) window.hideModal();
        load();
      } else {
        const te2 = window.ticketError || document.getElementById('ticketError'); if (te2) { te2.innerText = j.message || 'Save failed'; te2.style.display='block'; }
      }
    }catch(err){ const te3 = window.ticketError || document.getElementById('ticketError'); if (te3){ te3.innerText = err.message || 'Save failed'; te3.style.display='block'; } }
    finally{ if (window.modalSaveBtn){ window.modalSaveBtn.disabled = false; window.modalSaveBtn.innerText = 'Create Ticket'; } }
  });

  // Ensure modal initialization runs now so the New Ticket button is bound immediately
  try{ ensureModalInit(); console.log('ensureModalInit invoked at script end'); }catch(e){ console.error('ensureModalInit failed', e); }

  load();
  </script>
</body>
</html>
