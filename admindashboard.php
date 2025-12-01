<?php
session_start();
require_once 'audit_trail_helper.php';

// Handle logout
if (isset($_POST['logout']) && $_POST['logout'] === 'true') {
    $admin_id = $_SESSION['user_id'] ?? 0;
    $admin_email = $_SESSION['user_email'] ?? 'Unknown';
    
    // Log logout
    logAdminLogout($admin_id, $admin_email);
    
    session_destroy();
    header('Location: sign-in.php');
    exit();
}

// require admin session
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: sign-in.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
  <title>Admin Dashboard - Barangay 170</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="design/admindashboard.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
  <!-- Header -->
  <div class="page-header">
    <div class="logo-section">
      <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRTDCuh4kIpAtR-QmjA1kTjE_8-HSd8LSt3Gw&s" alt="seal">
      <div class="title-section">
        <div>Barangay 170</div>
        <div>Admin Dashboard</div>
      </div>
    </div>
    <div class="header-actions">
      <button class="btn" id="dashboardBtn">Dashboard</button>
      <button class="btn" id="requestMgmtBtn">Request Management</button>
      <button class="btn" id="notificationsBtn">Notifications</button>
      <button class="btn" id="addOfficialBtn">Add Official</button>
      <button class="btn" id="auditBtn">Audit Trail</button>
      <button class="btn" id="accBtn">Account Management</button>
      <button class="btn logout" id="logoutBtn">Logout</button>
    </div>
  </div>

  <div class="main-content">
    <!-- Analytics Cards -->
    <div class="analytics">
      <div class="card">
        <h2 id="totalCount" style="color: #2E5DFC;">0</h2>
        <small>Total</small>
      </div>
      <div class="card">
        <h2 id="reviewCount" style="color: #F66D31;">0</h2>
        <small>Pending</small>
      </div>
      <div class="card">
        <h2 id="progressCount" style="color: #E27508;">0</h2>
        <small>In Progress</small>
      </div>
      <div class="card">
        <h2 id="readyCount" style="color: #505B6D;">0</h2>
        <small>Ready</small>
      </div>
      <div class="card">
        <h2 id="completedCount" style="color: #07A840;">0</h2>
        <small>Completed</small>
      </div>
    </div>

    <!-- Analytics Graph Section -->
    <div class="analytics-graph-section">
      <div class="graph-header">
        <div>
          <h2>📊 Report Graph</h2>
          <p>Track reports over time</p>
        </div>
        <div class="timeframe-selector">
          <button class="timeframe-btn active" data-timeframe="day">Today</button>
          <button class="timeframe-btn" data-timeframe="week">This Week</button>
          <button class="timeframe-btn" data-timeframe="month">This Month</button>
        </div>
      </div>
      
      <div class="stats-summary">
        <div class="stat-card-small blue">
          <h3 id="graphTotalRequests">0</h3>
          <p>Total Requests</p>
        </div>
        <div class="stat-card-small orange">
          <h3 id="graphAvgPerPeriod">0</h3>
          <p id="graphAvgLabel">Avg per Hour</p>
        </div>
        <div class="stat-card-small green">
          <h3 id="graphPeakValue">0</h3>
          <p id="graphPeakLabel">Peak Hour</p>
        </div>
      </div>
      
      <div class="chart-container">
        <canvas id="analyticsChart"></canvas>
      </div>
      
      <div id="loadingChartIndicator" class="loading-chart">
        <div class="spinner"></div>
        <span>Loading data...</span>
      </div>
    </div>

    <!-- Main Content Section -->
    <div class="content-section">
      <div class="content-header">
        <div>
          <h1 id="sectionTitle">Request Management</h1>
          <p id="sectionDescription">Manage and track all requests from citizens</p>
        </div>
        <div class="tab-switcher">
          <button class="active" id="requestsTab">
            <i class="fas fa-file-alt"></i>
            Requests
          </button>
          <button id="usersTab">
            <i class="fas fa-users"></i>
            Users
          </button>
        </div>
      </div>

      <!-- Requests Section -->
      <div id="requestsSection">
        <div class="search-bar">
          <i class="fa fa-search"></i>
          <input type="text" id="searchInput" placeholder="Search by ID, name, or request type...">
        </div>

        <div class="filter-tabs">
          <button class="active" data-status="all">All</button>
          <button data-status="review">Pending</button>
          <button data-status="progress">In Progress</button>
          <button data-status="ready">Ready</button>
          <button data-status="done">Completed Report</button>
        </div>

        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Type</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="requestTableBody"></tbody>
          </table>
        </div>
        
        <!-- Requests Pagination -->
        <div id="requestsPagination" class="requests-pagination"></div>
      </div>

      <!-- Users Section -->
      <div id="usersSection" class="hidden">
        <div class="users-header">
          <h2 id="userCount">0</h2>
          <p>Total Registered Users</p>
        </div>
        <div class="users-grid" id="usersGrid"></div>
      </div>
    </div>

    <!-- Notifications Section -->
    <div class="content-section">
      <div class="notifications-header">
        <div class="notifications-title">
          <h2>🔔 Notifications</h2>
          <button class="btn-collapse hidden" id="collapseBtn">Show All (0)</button>
        </div>
        <button class="btn-add" id="addNotificationBtn">
          <i class="fas fa-plus"></i>
        </button>
      </div>

      <!-- Notification Form -->
      <div id="notificationForm" class="notification-form hidden">
        <div class="notification-type-btns">
          <button class="news active" data-type="NEWS">News</button>
          <button class="event" data-type="EVENT">Event</button>
        </div>
        <input type="text" id="notifTitle" placeholder="Title">
        <input type="text" id="notifDate" placeholder="Date">
        <textarea id="notifDescription" rows="3" placeholder="Description"></textarea>
        <button class="btn-add" id="submitNotificationBtn">Add Notification</button>
      </div>

      <!-- Notifications List -->
      <div id="notificationsList" class="notification-list"></div>
      <div id="emptyNotifications" class="empty-state">
        <p>No notifications yet. Click + to add one.</p>
      </div>
    </div>
      <!-- Barangay Officials Management Section -->
<div class="content-section">
  <div class="officials-management-header">
    <div>
      <h1>👥 Barangay Officials</h1>
      <p>Manage barangay officials displayed on the guest portal</p>
    </div>
    <button class="btn-add-official" id="toggleAddOfficialBtn">
      <i class="fas fa-plus"></i>
      Add Official
    </button>
  </div>

  <!-- Add Official Form -->
  <div id="addOfficialForm" class="add-official-form hidden">
    <h3>
      <i class="fas fa-user-plus"></i>
      Add New Official
    </h3>
    <div class="form-row">
      <div class="official-form-group">
        <label>Name</label>
        <input type="text" id="newOfficialName" placeholder="Enter official name" />
      </div>
      <div class="official-form-group">
        <label>Position</label>
        <input type="text" id="newOfficialPosition" placeholder="Enter position" />
      </div>
    </div>
    <div class="official-actions">
      <button class="btn-cancel-official" onclick="cancelAddOfficial()">Cancel</button>
      <button class="btn-save-official" onclick="addNewOfficial()">Add Official</button>
    </div>
  </div>

  <div id="officialsManagementList" class="officials-management-list"></div>
</div>
  </div>
  <script>
    // Global variables
let currentFilter = "all";
let currentTimeframe = 'day';
let currentTab = 'requests';
let analyticsChart = null;
let notificationType = 'NEWS';
let notifications = [];
let notificationsExpanded = true;

// Pagination variables
let currentRequestPage = 1;
let requestsPerPage = 10;
let totalRequestPages = 1;
let allRequests = [];

// Pagination variables for users
let currentUserPage = 1;
let usersPerPage = 5;
let totalUserPages = 1;
let allUsers = [];

// Pagination variables for officials
let currentOfficialPage = 1;
let officialsPerPage = 3;
let totalOfficialPages = 1;
let allOfficials = [];

// Fetch requests from server API
async function fetchRequests() {
  try {
    const res = await fetch('api_get_requests.php', {cache: 'no-store'});
    if (!res.ok) {
      console.error('Failed to fetch requests', res.status);
      return [];
    }
    const data = await res.json();
    return Array.isArray(data) ? data : [];
  } catch (err) {
    console.error('Error fetching requests', err);
    return [];
  }
}

// Fetch users
async function fetchUsers() {
  try {
    const res = await fetch('api_get_users.php', {cache: 'no-store'});
    if (!res.ok) {
      console.error('Failed to fetch users', res.status);
      return [];
    }
    const data = await res.json();
    return Array.isArray(data) ? data : [];
  } catch (err) {
    console.error('Error fetching users', err);
    return [];
  }
}

// Load and render requests
async function loadRequests() {
  const tableBody = document.getElementById("requestTableBody");
  const requests = await fetchRequests();
  const searchInput = document.getElementById("searchInput")?.value.toLowerCase() || "";

  // Apply search filter
  let filteredRequests = requests.filter(r =>
    ('' + (r.id || '')).toLowerCase().includes(searchInput) ||
    (r.name || '').toLowerCase().includes(searchInput) ||
    (r.type || '').toLowerCase().includes(searchInput)
  );

  // Apply tab (status) filter
  if (currentFilter !== "all") {
    filteredRequests = filteredRequests.filter(r => {
      const status = (r.status || '').toLowerCase();
      if (currentFilter === "review") return status === "under review" || status === "review" || status === "pending";
      if (currentFilter === "progress") return status === "in progress" || status === "progress";
      if (currentFilter === "ready") return status === "ready";
      if (currentFilter === "done") return status === "completed" || status === "done";
      return true;
    });
  }

  // Store filtered requests for pagination
  allRequests = filteredRequests;
  
  // Calculate pagination
  totalRequestPages = Math.ceil(allRequests.length / requestsPerPage);
  
  // Ensure current page is valid
  if (currentRequestPage > totalRequestPages && totalRequestPages > 0) {
    currentRequestPage = totalRequestPages;
  }
  if (currentRequestPage < 1) {
    currentRequestPage = 1;
  }
  
  // Get requests for current page
  const startIndex = (currentRequestPage - 1) * requestsPerPage;
  const endIndex = startIndex + requestsPerPage;
  const paginatedRequests = allRequests.slice(startIndex, endIndex);

  // Populate table
  tableBody.innerHTML = "";
  if (paginatedRequests.length === 0) {
    tableBody.innerHTML = `<tr><td colspan="7" class="center">No matching requests</td></tr>`;
  } else {
    paginatedRequests.forEach(r => {
      const priority = (r.priority || 'Medium').toLowerCase();
      const priorityClass =
        priority === "low" ? "priority-low" : priority === "medium" ? "priority-medium" : "priority-high";

      const st = (r.status || '').toLowerCase();
      const statusClass = st === "under review" || st === "pending" ? "status-under-review" : 
                         st === "in progress" ? "status-in-progress" : 
                         st === "ready" ? "status-ready" : "status-completed";

      tableBody.innerHTML += `
  <tr>
    <td>${r.ticket_id || r.id}</td>
    <td>${r.name}</td>
    <td>${r.type}</td>
    <td><span class="${priorityClass}">${r.priority || 'Medium'}</span></td>
    <td><span class="${statusClass}">${r.status || 'New'}</span></td>
    <td>${r.submitted}</td>
    <td class="actions">
      <a href="ReqDet&Upd.php?ticket_id=${encodeURIComponent(r.ticket_id || r.id)}" title="View Details">
        <i class="fa fa-eye"></i>
      </a>
      <a href="ReqDet&Upd.php?ticket_id=${encodeURIComponent(r.ticket_id || r.id)}" title="Edit Request">
        <i class="fa fa-edit"></i>
      </a>
      <button class="btn-delete-request" onclick="deleteRequest('${r.ticket_id || r.id}', '${r.type}')" title="Delete Request">
        <i class="fa fa-trash"></i>
      </button>
    </td>
  </tr>`;
    });
  }
  
  // Render pagination
  renderRequestsPagination();
  
  updateDashboard(requests);
}

// Render requests pagination
function renderRequestsPagination() {
  const paginationContainer = document.getElementById('requestsPagination');
  
  if (!paginationContainer) return;
  
  // Hide pagination if only one page or no results
  if (totalRequestPages <= 1) {
    paginationContainer.innerHTML = '';
    return;
  }
  
  let paginationHTML = '';
  
  // Previous button
  if (currentRequestPage > 1) {
    paginationHTML += `
      <button class="requests-pagination-btn" onclick="changeRequestPage(${currentRequestPage - 1})">
        <i class="fas fa-chevron-left"></i>
        Previous
      </button>
    `;
  } else {
    paginationHTML += `
      <button class="requests-pagination-btn" disabled>
        <i class="fas fa-chevron-left"></i>
        Previous
      </button>
    `;
  }
  
  // Page numbers
  const maxVisible = 5;
  
  if (totalRequestPages <= maxVisible) {
    // Show all pages
    for (let i = 1; i <= totalRequestPages; i++) {
      const activeClass = i === currentRequestPage ? 'active' : '';
      paginationHTML += `
        <button class="requests-page-number ${activeClass}" onclick="changeRequestPage(${i})">
          ${i}
        </button>
      `;
    }
  } else {
    // Show first page
    const activeClass = currentRequestPage === 1 ? 'active' : '';
    paginationHTML += `
      <button class="requests-page-number ${activeClass}" onclick="changeRequestPage(1)">
        1
      </button>
    `;
    
    // Show ellipsis if needed
    if (currentRequestPage > 3) {
      paginationHTML += `<span class="requests-page-ellipsis">...</span>`;
    }
    
    // Show pages around current page
    const start = Math.max(2, currentRequestPage - 1);
    const end = Math.min(totalRequestPages - 1, currentRequestPage + 1);
    
    for (let i = start; i <= end; i++) {
      const activeClass = i === currentRequestPage ? 'active' : '';
      paginationHTML += `
        <button class="requests-page-number ${activeClass}" onclick="changeRequestPage(${i})">
          ${i}
        </button>
      `;
    }
    
    // Show ellipsis if needed
    if (currentRequestPage < totalRequestPages - 2) {
      paginationHTML += `<span class="requests-page-ellipsis">...</span>`;
    }
    
    // Show last page
    const lastActiveClass = currentRequestPage === totalRequestPages ? 'active' : '';
    paginationHTML += `
      <button class="requests-page-number ${lastActiveClass}" onclick="changeRequestPage(${totalRequestPages})">
        ${totalRequestPages}
      </button>
    `;
  }
  
  // Next button
  if (currentRequestPage < totalRequestPages) {
    paginationHTML += `
      <button class="requests-pagination-btn" onclick="changeRequestPage(${currentRequestPage + 1})">
        Next
        <i class="fas fa-chevron-right"></i>
      </button>
    `;
  } else {
    paginationHTML += `
      <button class="requests-pagination-btn" disabled>
        Next
        <i class="fas fa-chevron-right"></i>
      </button>
    `;
  }
  
  paginationContainer.innerHTML = paginationHTML;
}

// Change request page
function changeRequestPage(page) {
  currentRequestPage = page;
  loadRequests();
  
  // Scroll to top of table
  const tableContainer = document.querySelector('.table-container');
  if (tableContainer) {
    tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

// Load and render users
async function loadUsers() {
  const usersGrid = document.getElementById("usersGrid");
  const users = await fetchUsers();
  
  document.getElementById("userCount").textContent = users.length;
  
  // Store all users
  allUsers = users;
  
  // Calculate pagination
  totalUserPages = Math.ceil(allUsers.length / usersPerPage);
  
  // Ensure current page is valid
  if (currentUserPage > totalUserPages && totalUserPages > 0) {
    currentUserPage = totalUserPages;
  }
  if (currentUserPage < 1) {
    currentUserPage = 1;
  }
  
  if (users.length === 0) {
    usersGrid.innerHTML = `
      <div class="empty-state" style="grid-column: 1/-1;">
        <i class="fas fa-users" style="font-size: 4rem; opacity: 0.3; margin-bottom: 1rem;"></i>
        <p style="font-size: 1.125rem;">No users registered yet</p>
        <p style="font-size: 0.875rem;">Users will appear here when they sign up</p>
      </div>`;
    // Clear pagination
    const paginationContainer = document.getElementById('usersPagination');
    if (paginationContainer) paginationContainer.innerHTML = '';
  } else {
    // Get users for current page
    const startIndex = (currentUserPage - 1) * usersPerPage;
    const endIndex = startIndex + usersPerPage;
    const paginatedUsers = allUsers.slice(startIndex, endIndex);
    
    usersGrid.innerHTML = paginatedUsers.map(user => `
      <div class="user-card">
        <div class="user-card-header">
          <div class="user-avatar">
            <i class="fas fa-user"></i>
          </div>
          <div class="user-info">
            <h3>${user.name}</h3>
            <p>${user.email}</p>
          </div>
        </div>
        <div class="user-details">
          ${user.isResident !== undefined ? `
            <div class="user-detail-row">
              <span class="user-detail-label">Status:</span>
              <span class="${user.isResident ? 'badge-resident' : 'badge-non-resident'}">
                ${user.isResident ? 'Resident' : 'Non-Resident'}
              </span>
            </div>
          ` : ''}
          ${user.idType ? `
            <div class="user-detail-row">
              <span class="user-detail-label">ID Type:</span>
              <span class="user-detail-value" style="text-transform: capitalize;">
                ${user.idType.replace(/-/g, ' ')}
              </span>
            </div>
          ` : ''}
          ${user.joinedDate ? `
            <div class="user-detail-row">
              <span class="user-detail-label">Joined:</span>
              <span class="user-detail-value">
                ${new Date(user.joinedDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
              </span>
            </div>
          ` : ''}
        </div>
        <button onclick="viewUserProfile(${user.id})">View Full Profile</button>
      </div>
    `).join('');
    
    // Render pagination
    renderUsersPagination();
  }
}

// Render users pagination
function renderUsersPagination() {
  let paginationContainer = document.getElementById('usersPagination');
  
  // Create pagination container if it doesn't exist
  if (!paginationContainer) {
    const usersSection = document.getElementById('usersSection');
    paginationContainer = document.createElement('div');
    paginationContainer.id = 'usersPagination';
    paginationContainer.className = 'requests-pagination';
    usersSection.appendChild(paginationContainer);
  }
  
  // Hide pagination if only one page or no results
  if (totalUserPages <= 1) {
    paginationContainer.innerHTML = '';
    return;
  }
  
  let paginationHTML = '';
  
  // Previous button
  if (currentUserPage > 1) {
    paginationHTML += `
      <button class="requests-pagination-btn" onclick="changeUserPage(${currentUserPage - 1})">
        <i class="fas fa-chevron-left"></i>
        Previous
      </button>
    `;
  } else {
    paginationHTML += `
      <button class="requests-pagination-btn" disabled>
        <i class="fas fa-chevron-left"></i>
        Previous
      </button>
    `;
  }
  
  // Page numbers
  const maxVisible = 5;
  
  if (totalUserPages <= maxVisible) {
    // Show all pages
    for (let i = 1; i <= totalUserPages; i++) {
      const activeClass = i === currentUserPage ? 'active' : '';
      paginationHTML += `
        <button class="requests-page-number ${activeClass}" onclick="changeUserPage(${i})">
          ${i}
        </button>
      `;
    }
  } else {
    // Show first page
    const activeClass = currentUserPage === 1 ? 'active' : '';
    paginationHTML += `
      <button class="requests-page-number ${activeClass}" onclick="changeUserPage(1)">
        1
      </button>
    `;
    
    // Show ellipsis if needed
    if (currentUserPage > 3) {
      paginationHTML += `<span class="requests-page-ellipsis">...</span>`;
    }
    
    // Show pages around current page
    const start = Math.max(2, currentUserPage - 1);
    const end = Math.min(totalUserPages - 1, currentUserPage + 1);
    
    for (let i = start; i <= end; i++) {
      const activeClass = i === currentUserPage ? 'active' : '';
      paginationHTML += `
        <button class="requests-page-number ${activeClass}" onclick="changeUserPage(${i})">
          ${i}
        </button>
      `;
    }
    
    // Show ellipsis if needed
    if (currentUserPage < totalUserPages - 2) {
      paginationHTML += `<span class="requests-page-ellipsis">...</span>`;
    }
    
    // Show last page
    const lastActiveClass = currentUserPage === totalUserPages ? 'active' : '';
    paginationHTML += `
      <button class="requests-page-number ${lastActiveClass}" onclick="changeUserPage(${totalUserPages})">
        ${totalUserPages}
      </button>
    `;
  }
  
  // Next button
  if (currentUserPage < totalUserPages) {
    paginationHTML += `
      <button class="requests-pagination-btn" onclick="changeUserPage(${currentUserPage + 1})">
        Next
        <i class="fas fa-chevron-right"></i>
      </button>
    `;
  } else {
    paginationHTML += `
      <button class="requests-pagination-btn" disabled>
        Next
        <i class="fas fa-chevron-right"></i>
      </button>
    `;
  }
  
  paginationContainer.innerHTML = paginationHTML;
}

// Change user page
function changeUserPage(page) {
  currentUserPage = page;
  loadUsers();
  
  // Scroll to top of users section
  const usersSection = document.getElementById('usersSection');
  if (usersSection) {
    usersSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

// View user profile
function viewUserProfile(userId) {
  window.location.href = `user-profile.php?id=${userId}`;
}

// Update dashboard analytics
function updateDashboard(reqs) {
  const counts = {
    total: reqs.length,
    review: reqs.filter(r => {
      const st = r.status.toLowerCase();
      return st === "pending" || st === "under review" || st === "review";
    }).length,
    progress: reqs.filter(r => r.status.toLowerCase() === "in progress").length,
    ready: reqs.filter(r => r.status.toLowerCase() === "ready").length,
    completed: reqs.filter(r => r.status.toLowerCase() === "completed").length,
  };
  document.getElementById("totalCount").textContent = counts.total;
  document.getElementById("reviewCount").textContent = counts.review;
  document.getElementById("progressCount").textContent = counts.progress;
  document.getElementById("readyCount").textContent = counts.ready;
  document.getElementById("completedCount").textContent = counts.completed;
}

// Initialize analytics chart
function initAnalyticsChart() {
  const ctx = document.getElementById('analyticsChart');
  if (!ctx) return;
  
  analyticsChart = new Chart(ctx.getContext('2d'), {
    type: 'line',
    data: {
      labels: [],
      datasets: [{
        label: 'Requests',
        data: [],
        borderColor: '#2E5DFC',
        backgroundColor: 'rgba(46, 93, 252, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.4,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: '#2E5DFC',
        pointBorderColor: '#fff',
        pointBorderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          titleFont: {
            size: 14,
            family: 'Poppins'
          },
          bodyFont: {
            size: 13,
            family: 'Poppins'
          },
          displayColors: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1,
            font: {
              family: 'Poppins',
              size: 11
            }
          },
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          }
        },
        x: {
          ticks: {
            font: {
              family: 'Poppins',
              size: 10
            },
            maxRotation: 45,
            minRotation: 45
          },
          grid: {
            display: false
          }
        }
      }
    }
  });
}

// Fetch analytics data
async function fetchAnalytics(timeframe) {
  try {
    const response = await fetch(`api_get_analytics.php?timeframe=${timeframe}`, {
      cache: 'no-store'
    });
    
    if (!response.ok) {
      throw new Error('Failed to fetch analytics');
    }
    
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error fetching analytics:', error);
    return [];
  }
}

// Update chart with new data
async function updateAnalyticsChart(timeframe) {
  const loadingIndicator = document.getElementById('loadingChartIndicator');
  const chartCanvas = document.querySelector('.chart-container canvas');
  
  if (loadingIndicator) loadingIndicator.style.display = 'flex';
  if (chartCanvas) chartCanvas.style.opacity = '0.3';
  
  const data = await fetchAnalytics(timeframe);
  
  if (analyticsChart && data.length > 0) {
    analyticsChart.data.labels = data.map(d => d.label);
    analyticsChart.data.datasets[0].data = data.map(d => d.value);
    analyticsChart.update('none');
    
    updateAnalyticsStats(data, timeframe);
  }
  
  if (loadingIndicator) loadingIndicator.style.display = 'none';
  if (chartCanvas) chartCanvas.style.opacity = '1';
}

// Update summary statistics
function updateAnalyticsStats(data, timeframe) {
  const total = data.reduce((sum, item) => sum + item.value, 0);
  const avg = data.length > 0 ? (total / data.length).toFixed(1) : 0;
  const peak = Math.max(...data.map(d => d.value));
  const peakIndex = data.findIndex(d => d.value === peak);
  const peakLabel = peakIndex >= 0 ? data[peakIndex].label : '-';
  
  const totalEl = document.getElementById('graphTotalRequests');
  const avgEl = document.getElementById('graphAvgPerPeriod');
  const peakEl = document.getElementById('graphPeakValue');
  const avgLabelEl = document.getElementById('graphAvgLabel');
  const peakLabelEl = document.getElementById('graphPeakLabel');
  
  if (totalEl) totalEl.textContent = total;
  if (avgEl) avgEl.textContent = avg;
  if (peakEl) peakEl.textContent = peak;
  
  if (timeframe === 'day') {
    if (avgLabelEl) avgLabelEl.textContent = 'Avg per Hour';
    if (peakLabelEl) peakLabelEl.textContent = `Peak: ${peakLabel}`;
  } else if (timeframe === 'week') {
    if (avgLabelEl) avgLabelEl.textContent = 'Avg per Day';
    if (peakLabelEl) peakLabelEl.textContent = `Peak: ${peakLabel}`;
  } else {
    if (avgLabelEl) avgLabelEl.textContent = 'Avg per Day';
    if (peakLabelEl) peakLabelEl.textContent = `Peak Day`;
  }
}

// Fetch notifications from server
async function fetchNotifications() {
  try {
    const res = await fetch('api_get_notifications.php', {cache: 'no-store'});
    if (!res.ok) {
      console.error('Failed to fetch notifications', res.status);
      return [];
    }
    const data = await res.json();
    return Array.isArray(data) ? data : [];
  } catch (err) {
    console.error('Error fetching notifications', err);
    return [];
  }
}

// Load and render notifications
async function loadNotifications() {
  notifications = await fetchNotifications();
  renderNotifications();
}

// Render notifications
function renderNotifications() {
  const notificationsList = document.getElementById('notificationsList');
  const emptyNotifications = document.getElementById('emptyNotifications');
  const collapseBtn = document.getElementById('collapseBtn');
  
  if (!notificationsList || !emptyNotifications || !collapseBtn) return;
  
  if (notifications.length === 0) {
    notificationsList.innerHTML = '';
    emptyNotifications.classList.remove('hidden');
    collapseBtn.classList.add('hidden');
    return;
  }
  
  emptyNotifications.classList.add('hidden');
  
  // Auto-collapse if more than 3 notifications
if (notifications.length > 3) {
  collapseBtn.classList.remove('hidden');
  
  // Update button text based on expanded state
  if (notificationsExpanded) {
    collapseBtn.textContent = `Show Less`;
  } else {
    collapseBtn.textContent = `Show All (${notifications.length})`;
  }
} else {
  collapseBtn.classList.add('hidden');
  notificationsExpanded = false;
}

const displayNotifications = notificationsExpanded ? notifications : notifications.slice(0, 3);
  
  notificationsList.innerHTML = displayNotifications.map(notif => `
    <div class="notification-item ${notif.type.toLowerCase()}">
      <div class="notification-item-header">
        <div class="notification-item-title">
          <span class="notification-badge ${notif.type.toLowerCase()}">${notif.type}</span>
          <h3>${notif.title}</h3>
        </div>
        <button class="btn-delete" onclick="deleteNotification(${notif.id})">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <p class="notification-date">${notif.date}</p>
      <p class="notification-description">${notif.description}</p>
    </div>
  `).join('');
}

// Add notification
async function addNotification() {
  const title = document.getElementById('notifTitle').value;
  const date = document.getElementById('notifDate').value;
  const description = document.getElementById('notifDescription').value;
  
  if (!title || !date || !description) {
    alert('Please fill in all fields');
    return;
  }
  
  try {
    const response = await fetch('api_add_notification.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        type: notificationType,
        title: title,
        date: date,
        description: description
      })
    });
    
    const responseText = await response.text();
    console.log('Server response:', responseText);
    
    let result;
    try {
      result = JSON.parse(responseText);
    } catch (e) {
      console.error('Failed to parse JSON:', responseText);
      alert('Server error: Invalid response format. Check console for details.');
      return;
    }
    
    if (response.ok && result.success) {
      document.getElementById('notifTitle').value = '';
      document.getElementById('notifDate').value = '';
      document.getElementById('notifDescription').value = '';
      document.getElementById('notificationForm').classList.add('hidden');
      
      await loadNotifications();
      alert('Notification added successfully!');
    } else {
      console.error('Error details:', result);
      alert('Error: ' + (result.error || result.message || 'Failed to add notification. Check console for details.'));
    }
  } catch (err) {
    console.error('Error adding notification:', err);
    alert('Network error: ' + err.message + '. Check if api_add_notification.php exists and the database is running.');
  }
}

// Delete notification
async function deleteNotification(id) {
  if (!confirm('Are you sure you want to delete this notification?')) {
    return;
  }
  
  try {
    const response = await fetch(`api_delete_notification.php?id=${id}`, {
      method: 'DELETE'
    });
    
    const result = await response.json();
    
    if (response.ok && result.success) {
      await loadNotifications();
      alert('Notification deleted successfully!');
    } else {
      alert('Error: ' + (result.error || 'Failed to delete notification'));
    }
  } catch (err) {
    console.error('Error deleting notification:', err);
    alert('Failed to delete notification. Please try again.');
  }
}

// Fetch and render barangay officials management
async function loadOfficialsManagement() {
  try {
    const res = await fetch('api_get_officials.php', {cache: 'no-store'});
    if (!res.ok) {
      console.error('Failed to fetch officials', res.status);
      return;
    }
    const officials = await res.json();
    renderOfficialsManagement(officials);
  } catch (err) {
    console.error('Error fetching officials', err);
  }
}

// Render officials management interface
function renderOfficialsManagement(officials) {
  const container = document.getElementById('officialsManagementList');
  if (!container) return;
  
  // Store all officials
  allOfficials = officials;
  
  // Calculate pagination
  totalOfficialPages = Math.ceil(allOfficials.length / officialsPerPage);
  
  // Ensure current page is valid
  if (currentOfficialPage > totalOfficialPages && totalOfficialPages > 0) {
    currentOfficialPage = totalOfficialPages;
  }
  if (currentOfficialPage < 1) {
    currentOfficialPage = 1;
  }
  
  if (officials.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <i class="fas fa-users" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
        <p>No officials added yet. Click "Add Official" to get started.</p>
      </div>
    `;
    return;
  }
  
  // Get officials for current page
  const startIndex = (currentOfficialPage - 1) * officialsPerPage;
  const endIndex = startIndex + officialsPerPage;
  const paginatedOfficials = allOfficials.slice(startIndex, endIndex);
  
  container.innerHTML = paginatedOfficials.map(official => `
    <div class="official-edit-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3>Official ${official.display_order}</h3>
        <button class="btn-delete-official" onclick="deleteOfficial(${official.id})">
          <i class="fas fa-trash"></i> Delete
        </button>
      </div>
      <div class="official-form-group">
        <label>Name</label>
        <input type="text" id="name_${official.id}" value="${official.name}" />
      </div>
      <div class="official-form-group">
        <label>Position</label>
        <input type="text" id="position_${official.id}" value="${official.position}" />
      </div>
      <div class="official-actions">
        <button class="btn-save-official" onclick="saveOfficial(${official.id})">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </div>
    </div>
  `).join('');
  
  // Render pagination
  renderOfficialsPagination();
}

// Render officials pagination
function renderOfficialsPagination() {
  // Check if pagination container exists, if not create it
  let paginationContainer = document.getElementById('officialsPagination');
  
  if (!paginationContainer) {
    const managementList = document.getElementById('officialsManagementList');
    paginationContainer = document.createElement('div');
    paginationContainer.id = 'officialsPagination';
    paginationContainer.className = 'requests-pagination';
    managementList.parentNode.appendChild(paginationContainer);
  }
  
  // Hide pagination if only one page or no results
  if (totalOfficialPages <= 1) {
    paginationContainer.innerHTML = '';
    return;
  }
  
  let paginationHTML = '';
  
  // Previous button
  if (currentOfficialPage > 1) {
    paginationHTML += `
      <button class="requests-pagination-btn" onclick="changeOfficialPage(${currentOfficialPage - 1})">
        <i class="fas fa-chevron-left"></i>
        Previous
      </button>
    `;
  } else {
    paginationHTML += `
      <button class="requests-pagination-btn" disabled>
        <i class="fas fa-chevron-left"></i>
        Previous
      </button>
    `;
  }
  
  // Page numbers
  for (let i = 1; i <= totalOfficialPages; i++) {
    const activeClass = i === currentOfficialPage ? 'active' : '';
    paginationHTML += `
      <button class="requests-page-number ${activeClass}" onclick="changeOfficialPage(${i})">
        ${i}
      </button>
    `;
  }
  
  // Next button
  if (currentOfficialPage < totalOfficialPages) {
    paginationHTML += `
      <button class="requests-pagination-btn" onclick="changeOfficialPage(${currentOfficialPage + 1})">
        Next
        <i class="fas fa-chevron-right"></i>
      </button>
    `;
  } else {
    paginationHTML += `
      <button class="requests-pagination-btn" disabled>
        Next
        <i class="fas fa-chevron-right"></i>
      </button>
    `;
  }
  
  paginationContainer.innerHTML = paginationHTML;
}

// Change official page
function changeOfficialPage(page) {
  currentOfficialPage = page;
  renderOfficialsManagement(allOfficials);
  
  // Scroll to officials section
  const officialsSection = document.querySelector('.officials-management-header');
  if (officialsSection) {
    officialsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}



// Toggle add official form
function toggleAddOfficialForm() {
  const form = document.getElementById('addOfficialForm');
  if (form) {
    form.classList.toggle('hidden');
  }
}

// Cancel add official
function cancelAddOfficial() {
  const form = document.getElementById('addOfficialForm');
  if (form) {
    form.classList.add('hidden');
  }
  document.getElementById('newOfficialName').value = '';
  document.getElementById('newOfficialPosition').value = '';
}

// Add new official
async function addNewOfficial() {
  const name = document.getElementById('newOfficialName').value.trim();
  const position = document.getElementById('newOfficialPosition').value.trim();
  
  if (!name || !position) {
    alert('Please fill in all fields');
    return;
  }
  
  try {
    const response = await fetch('api_add_official.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        name: name,
        position: position
      })
    });
    
    const result = await response.json();
    
    if (response.ok && result.success) {
      alert('Official added successfully!');
      cancelAddOfficial();
      currentOfficialPage = 1; // Add this line
      loadOfficialsManagement();
    } else {
      alert('Error: ' + (result.error || 'Failed to add official'));
    }
  } catch (err) {
    console.error('Error adding official:', err);
    alert('Failed to add official. Please try again.');
  }
}

// Save official changes
async function saveOfficial(id) {
  const name = document.getElementById(`name_${id}`).value.trim();
  const position = document.getElementById(`position_${id}`).value.trim();
  
  if (!name || !position) {
    alert('Please fill in all fields');
    return;
  }
  
  try {
    const response = await fetch('api_update_official.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        id: id,
        name: name,
        position: position
      })
    });
    
    const result = await response.json();
    
    if (response.ok && result.success) {
      alert('Official updated successfully!');
      loadOfficialsManagement();
    } else {
      alert('Error: ' + (result.error || 'Failed to update official'));
    }
  } catch (err) {
    console.error('Error updating official:', err);
    alert('Failed to update official. Please try again.');
  }
}

// Delete official
async function deleteOfficial(id) {
  if (!confirm('Are you sure you want to delete this official? This action cannot be undone.')) {
    return;
  }
  
  try {
    const response = await fetch(`api_delete_official.php?id=${id}`, {
      method: 'DELETE'
    });
    
    const result = await response.json();
    
    if (response.ok && result.success) {
      alert('Official deleted successfully!');
      loadOfficialsManagement();
    } else {
      alert('Error: ' + (result.error || 'Failed to delete official'));
    }
  } catch (err) {
    console.error('Error deleting official:', err);
    alert('Failed to delete official. Please try again.');
  }
}

// Move this OUTSIDE of the DOMContentLoaded event listener
// Place it before the document.addEventListener('DOMContentLoaded', ...) line

// Delete request
async function deleteRequest(ticketId, requestType) {
  if (!confirm(`Are you sure you want to delete request ${ticketId}?\n\nType: ${requestType}\n\nThis action cannot be undone.`)) {
    return;
  }
  
  try {
    const response = await fetch('api_delete_request.php', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        ticket_id: ticketId,
        request_type: requestType
      })
    });
    
    const result = await response.json();
    
    if (response.ok && result.success) {
      alert('Request deleted successfully!');
      // Reload the requests
      await loadRequests();
    } else {
      alert('Error: ' + (result.error || 'Failed to delete request'));
    }
  } catch (err) {
    console.error('Error deleting request:', err);
    alert('Failed to delete request. Please try again.');
  }
}

// Initialize everything on page load
document.addEventListener('DOMContentLoaded', () => {
  // Initialize
  loadRequests();
  loadNotifications();
  loadOfficialsManagement();
  initAnalyticsChart();
  updateAnalyticsChart(currentTimeframe);
  
  // Search input - reset to page 1 when searching
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      currentRequestPage = 1;
      loadRequests();
    });
  }
  
  // Filter tabs - reset to page 1 when filtering
  document.querySelectorAll('.filter-tabs button').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-tabs button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentFilter = btn.dataset.status;
      currentRequestPage = 1;
      loadRequests();
    });
  });
  
  // Timeframe buttons
  document.querySelectorAll('.timeframe-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.timeframe-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentTimeframe = btn.dataset.timeframe;
      updateAnalyticsChart(currentTimeframe);
    });
  });
  
  // Tab switcher
  const requestsTab = document.getElementById('requestsTab');
  const usersTab = document.getElementById('usersTab');
  
  if (requestsTab) {
    requestsTab.addEventListener('click', () => {
      requestsTab.classList.add('active');
      usersTab.classList.remove('active');
      document.getElementById('requestsSection').classList.remove('hidden');
      document.getElementById('usersSection').classList.add('hidden');
      document.getElementById('sectionTitle').textContent = 'Request Management';
      document.getElementById('sectionDescription').textContent = 'Manage and track all requests from citizens';
      currentTab = 'requests';
    });
  }
  
  if (usersTab) {
    usersTab.addEventListener('click', () => {
      usersTab.classList.add('active');
      requestsTab.classList.remove('active');
      document.getElementById('usersSection').classList.remove('hidden');
      document.getElementById('requestsSection').classList.add('hidden');
      document.getElementById('sectionTitle').textContent = 'User Management';
      document.getElementById('sectionDescription').textContent = 'View and manage registered users';
      currentTab = 'users';
      loadUsers();
    });
  }


  
  // Notification form toggle
  const addNotificationBtn = document.getElementById('addNotificationBtn');
  if (addNotificationBtn) {
    addNotificationBtn.addEventListener('click', () => {
      const form = document.getElementById('notificationForm');
      if (form) form.classList.toggle('hidden');
    });
  }
  
  // Notification type buttons
  document.querySelectorAll('.notification-type-btns button').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.notification-type-btns button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      notificationType = btn.dataset.type;
    });
  });
  
  // Submit notification
  const submitNotificationBtn = document.getElementById('submitNotificationBtn');
  if (submitNotificationBtn) {
    submitNotificationBtn.addEventListener('click', addNotification);
  }
  
  // Collapse notifications
const collapseBtn = document.getElementById('collapseBtn');
if (collapseBtn) {
  collapseBtn.addEventListener('click', () => {
    notificationsExpanded = !notificationsExpanded;
    renderNotifications();
  });
}

  // Toggle add official form
const toggleAddOfficialBtn = document.getElementById('toggleAddOfficialBtn');
if (toggleAddOfficialBtn) {
  toggleAddOfficialBtn.addEventListener('click', toggleAddOfficialForm);
}
  
  // Navigation buttons
  const auditBtn = document.getElementById('auditBtn');
  if (auditBtn) {
    auditBtn.addEventListener('click', () => {
      window.location.href = 'audit_trail.php';
    });
  }
  
  const accBtn = document.getElementById('accBtn');
  if (accBtn) {
    accBtn.addEventListener('click', () => {
      window.location.href = 'account_approval.php';
    });
  }

  // Navigation - Dashboard
const dashboardBtn = document.getElementById('dashboardBtn');
if (dashboardBtn) {
  dashboardBtn.addEventListener('click', () => {
    // Scroll to top of page
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}

  // Navigation - Request Management
const requestMgmtBtn = document.getElementById('requestMgmtBtn');
if (requestMgmtBtn) {
  requestMgmtBtn.addEventListener('click', () => {
    // Switch to requests tab
    const requestsTab = document.getElementById('requestsTab');
    if (requestsTab) requestsTab.click();
    
    // Scroll to requests section
    const requestsSection = document.getElementById('requestsSection');
    if (requestsSection) {
      requestsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
}

// Navigation - Notifications
const notificationsBtn = document.getElementById('notificationsBtn');
if (notificationsBtn) {
  notificationsBtn.addEventListener('click', () => {
    const notificationsSection = document.querySelector('.content-section:has(.notifications-header)');
    if (notificationsSection) {
      notificationsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
}

// Navigation - Add Official
const addOfficialNavBtn = document.getElementById('addOfficialBtn');
if (addOfficialNavBtn) {
  addOfficialNavBtn.addEventListener('click', () => {
    const officialsSection = document.querySelector('.officials-management-header');
    if (officialsSection) {
      officialsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
}
  
  // Logout
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
      try {
        const formData = new FormData();
        formData.append('logout', 'true');
        
        await fetch(window.location.href, {
          method: 'POST',
          body: formData
        });
        
        window.location.href = 'sign-in.php';
      } catch (err) {
        console.error('Logout error:', err);
        window.location.href = 'sign-in.php';
      }
    });
  }
  
  // Auto-refresh data every 30 seconds
  setInterval(loadRequests, 30000);
  setInterval(() => updateAnalyticsChart(currentTimeframe), 30000);
});
  </script>
</body>
</html>
