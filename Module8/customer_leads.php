<?php
require '../db.php';
require '../shared/config.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Customer Leads</title>
  <link rel="stylesheet" href="styles.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <base href="<?php echo BASE_URL; ?>">
  <style>
    .line-input { width: 100%; }
    .small { font-size: 0.9rem; color: #666; }
    .status-New { background: #007bff; color: #fff; border-radius: 25px; margin: 6px; padding: 2px 6px; display: inline-block; }
    .status-Contacted { background: #17a2b8; color: #fff; border-radius: 25px; margin: 6px; padding: 2px 6px; display: inline-block; }
    .status-Qualified { background: #28a745; color: #fff; border-radius: 25px; margin: 6px; padding: 2px 6px; display: inline-block; }
    .status-Converted { background: #ffc107; color: #000; border-radius: 25px; margin: 6px; padding: 2px 6px; display: inline-block; }
    .status-Lost { background: #dc3545; color: #fff; border-radius: 25px; margin: 6px; padding: 2px 6px; display: inline-block; }
    .status-Hot { background: #dc3545; color: #fff; border-radius: 25px; margin: 6px; padding: 2px 6px; display: inline-block; }
    .status-Warm { background: #ffc107; color: #000; border-radius: 25px; margin: 6px; padding: 2px 6px; display: inline-block; }
    .status-Cold { background: #6c757d; color: #fff; border-radius: 25px; margin: 6px; padding: 2px 6px; display: inline-block; }
    .interest-Hot { background: #dc3545; color: #fff; border-radius: 25px; margin: 6px; padding: 2px 6px; display: inline-block; }
    .interest-Warm { background: #ffc107; color: #000; border-radius: 25px; margin: 6px; padding: 2px 6px; display: inline-block; }
    .interest-Cold { background: #6c757d; color: #fff; border-radius: 25px; margin: 6px; padding: 2px 6px; display: inline-block; }
    .dropdown { position: relative; display: inline-block; }
    .dropdown-content { display: none; position: absolute; background-color: #f9f9f9; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; right: 0; }
    .dropdown-content a { color: black; padding: 12px 16px; text-decoration: none; display: block; cursor: pointer; }
    .dropdown-content a:hover { background-color: #f1f1f1; }
    .menu-icon { background: none; border: none; font-size: 18px; cursor: pointer; padding: 5px; }
    .leads-slider { display: flex; gap: 15px; overflow-x: auto; padding: 10px; scroll-behavior: smooth; }
    .lead-card { min-width: 300px; border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s ease; }
    .lead-card:hover { transform: translateY(-5px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
    .lead-card div { margin-bottom: 8px; padding: 4px 0; }
    .lead-card div:last-child { margin-bottom: 0; }
    @media (max-width: 768px) {
      .container { margin-left: 0; padding: 10px; }
      .card { margin-bottom: 20px; padding: 15px; }
      .btn { padding: 8px 16px; font-size: 1em; margin: 2px; }
      .line-input { width: 100%; margin-bottom: 10px; }
      .container > div > div:first-child { flex-direction: column; gap: 10px; }
      .container > div > div:first-child label { width: 100%; }
      .leads-slider { gap: 10px; padding: 5px; }
      .lead-card { min-width: 280px; padding: 10px; }
    }
    @media (max-width: 480px) {
      .container { padding: 5px; }
      .card { padding: 10px; }
      .btn { padding: 6px 12px; font-size: 0.9em; }
      h1 { font-size: 1.5em; }
      h3 { font-size: 1.2em; }
      .leads-slider { gap: 5px; padding: 5px; }
      .lead-card { min-width: 250px; padding: 8px; }
    }
  </style>
</head>
<body>
  <?php include '../shared/sidebar.php'; ?>
  <div class="container" style="margin-left:18rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h1>Customer Leads</h1>
      <button class="btn btn-primary" onclick="openAddModal()">Add New Lead</button>
    </div>

    <div class="card">
      <h3>Filters</h3>
      <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <label>Search: <input id="search" class="line-input" placeholder="Name, Email, Company" style="width:200px"></label>
        <label>Status:
          <select id="filter_status" class="line-input" style="width:150px">
            <option value="">All</option>
            <option value="New">New</option>
            <option value="Contacted">Contacted</option>
            <option value="Qualified">Qualified</option>
            <option value="Converted">Converted</option>
            <option value="Lost">Lost</option>
          </select>
        </label>
        <label>Interest Level:
          <select id="filter_interest" class="line-input" style="width:150px">
            <option value="">All</option>
            <option value="Hot">Hot</option>
            <option value="Warm">Warm</option>
            <option value="Cold">Cold</option>
          </select>
        </label>
        <button class="btn" onclick="loadLeads()">Filter</button>
      </div>
    </div>

    <div class="card">
      <h3>Leads List</h3>
      <div id="leads_list"></div>
    </div>
  </div>

  <!-- Add/Edit Modal -->
  <div id="leadModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);justify-content:center;align-items:center;z-index:200">
    <div style="background:#fff;padding:20px;border-radius:8px;width:500px;max-height:90vh;overflow-y:auto;">
      <h3 id="modal_title">Add New Lead</h3>
      <form id="leadForm">
        <input type="hidden" id="lead_id">
        <label>Customer Name <input id="customer_name" class="line-input" required></label>
        <label>Company Name <input id="company_name" class="line-input"></label>
        <label>Email <input id="email" class="line-input" type="email" required></label>
        <label>Contact Number <input id="contact_number" class="line-input" required></label>
        <label>Lead Source
          <select id="lead_source" class="line-input">
            <option value="Website">Website</option>
            <option value="Referral">Referral</option>
            <option value="Email">Email</option>
            <option value="Call">Call</option>
            <option value="Walk-in">Walk-in</option>
            <option value="Other">Other</option>
          </select>
        </label>
        <label>Interest Level
          <select id="interest_level" class="line-input">
            <option value="Hot">Hot</option>
            <option value="Warm">Warm</option>
            <option value="Cold">Cold</option>
          </select>
        </label>
        <label>Status
          <select id="status" class="line-input">
            <option value="New">New</option>
            <option value="Contacted">Contacted</option>
            <option value="Qualified">Qualified</option>
            <option value="Converted">Converted</option>
            <option value="Lost">Lost</option>
          </select>
        </label>
        <label>Assigned To
          <select id="assigned_to" class="line-input" required></select>
        </label>
        <label>Remarks <textarea id="remarks" class="line-input"></textarea></label>
        <div style="margin-top:8px;text-align:right">
          <button type="button" class="btn" onclick="closeLeadModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  let users = [];

  async function loadUsers() {
    const res = await fetch('Module8/api/leads.php?action=users');
    const j = await res.json();
    if (j.status === 'success') {
      users = j.data;
      const sel = document.getElementById('assigned_to');
      sel.innerHTML = '<option value="">-- Select User --</option>' + users.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
    } else {
      alert('Failed to load users');
    }
  }

  async function loadLeads() {
    const search = document.getElementById('search').value;
    const status = document.getElementById('filter_status').value;
    const interest_level = document.getElementById('filter_interest').value;
    const params = new URLSearchParams();
    params.append('action', 'list');
    if (search) params.append('search', search);
    if (status) params.append('status', status);
    if (interest_level) params.append('interest_level', interest_level);
    const res = await fetch('Module8/api/leads.php?' + params);
    const j = await res.json();
    if (j.status === 'success') {
    const el = document.getElementById('leads_list');
      el.innerHTML = `<div class="leads-slider">${j.data.map(l => {
        const actions = `<button onclick="editLead(${l.lead_id})" style="background: none; border: none;" title="Edit"><i class="material-icons">edit</i></button> <button onclick="deleteLead(${l.lead_id})" style="background: none; border: none;" title="Delete"><i class="material-icons">delete</i></button>`;
        const customerLink = l.converted_customer_id ? `<a href="Module8/customers.php?id=${l.converted_customer_id}" target="_blank" style="color: #007bff; text-decoration: none;">View Customer</a>` : '';
        return `<div class="lead-card"><div><strong>Name:</strong> ${l.customer_name}</div><div><strong>Email:</strong> ${l.email}</div><div><strong>Status:</strong> <span class="status-${l.status}">${l.status}</span></div><div><strong>Interest:</strong> <span class="interest-${l.interest_level}">${l.interest_level}</span></div><div><strong>Assigned:</strong> ${l.assigned_name || ''}</div>${customerLink ? `<div><strong>Customer:</strong> ${customerLink}</div>` : ''}<div><strong>Actions:</strong> ${actions}</div></div>`;
      }).join('')}</div>`;
    } else {
      alert('Failed to load leads: ' + (j.message || 'Unknown error'));
    }
  }

  function openAddModal() {
    document.getElementById('modal_title').textContent = 'Add New Lead';
    document.getElementById('leadForm').reset();
    document.getElementById('lead_id').value = '';
    document.getElementById('leadModal').style.display = 'flex';
  }

  function closeLeadModal() {
    document.getElementById('leadModal').style.display = 'none';
  }

  async function editLead(id) {
    const res = await fetch('Module8/api/leads.php?action=get&id=' + id);
    const j = await res.json();
    if (j.status === 'success') {
      const l = j.data;
      document.getElementById('modal_title').textContent = 'Edit Lead';
      document.getElementById('lead_id').value = l.lead_id;
      document.getElementById('customer_name').value = l.customer_name;
      document.getElementById('company_name').value = l.company_name;
      document.getElementById('email').value = l.email;
      document.getElementById('contact_number').value = l.contact_number;
      document.getElementById('lead_source').value = l.lead_source;
      document.getElementById('interest_level').value = l.interest_level;
      document.getElementById('status').value = l.status;
      document.getElementById('assigned_to').value = l.assigned_to;
      document.getElementById('remarks').value = l.remarks;
      document.getElementById('leadModal').style.display = 'flex';
    } else {
      alert('Failed to load lead: ' + (j.message || 'Unknown error'));
    }
  }

  async function deleteLead(id) {
    if (!confirm('Are you sure you want to delete this lead?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    const res = await fetch('Module8/api/leads.php', {method:'POST', body:fd});
    const j = await res.json();
    if (j.status === 'success') {
      alert('Lead deleted');
      loadLeads();
    } else {
      alert('Delete failed: ' + (j.message || JSON.stringify(j)));
    }
  }

  document.getElementById('leadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData();
    const id = document.getElementById('lead_id').value;
    fd.append('action', id ? 'update' : 'add');
    if (id) fd.append('lead_id', id);
    fd.append('customer_name', document.getElementById('customer_name').value);
    fd.append('company_name', document.getElementById('company_name').value);
    fd.append('email', document.getElementById('email').value);
    fd.append('contact_number', document.getElementById('contact_number').value);
    fd.append('lead_source', document.getElementById('lead_source').value);
    fd.append('interest_level', document.getElementById('interest_level').value);
    fd.append('status', document.getElementById('status').value);
    fd.append('assigned_to', document.getElementById('assigned_to').value);
    fd.append('remarks', document.getElementById('remarks').value);
    const res = await fetch('Module8/api/leads.php', {method:'POST', body:fd});
    const j = await res.json();
    if (j.status === 'success') {
      alert('Lead saved');
      closeLeadModal();
      loadLeads();
    } else {
      alert('Save failed: ' + (j.message || JSON.stringify(j)));
    }
  });

  // init
  loadUsers();
  loadLeads();
  </script>
</body>
</html>
