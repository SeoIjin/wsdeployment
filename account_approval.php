<?php
session_start();
require_once 'audit_trail_helper.php';

// Require admin session
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: sign-in.php');
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "users";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$admin_id = $_SESSION['user_id'];
$admin_email = $_SESSION['user_email'] ?? 'Unknown';

// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);
    
    if ($user_id > 0) {
        if ($action === 'approve') {
            // Approve account
            $stmt = $conn->prepare("UPDATE account SET account_status = 'approved', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                // Get user email for audit log
                $stmt2 = $conn->prepare("SELECT email FROM account WHERE id = ?");
                $stmt2->bind_param("i", $user_id);
                $stmt2->execute();
                $result = $stmt2->get_result();
                $user = $result->fetch_assoc();
                
                // Log the approval
                logAuditTrail(
                    $admin_id,
                    $admin_email,
                    'ACCOUNT_APPROVAL',
                    "Approved account for user: {$user['email']}",
                    'account',
                    $user_id,
                    'pending',
                    'approved'
                );
                
                $stmt2->close();
                $_SESSION['success_message'] = "Account approved successfully!";
            }
            $stmt->close();
            
        } elseif ($action === 'reject') {
            // Get user info before deletion
            $stmt = $conn->prepare("SELECT email, first_name, last_name FROM account WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            // Delete/reject account
            $stmt = $conn->prepare("DELETE FROM account WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                // Log the rejection
                logAuditTrail(
                    $admin_id,
                    $admin_email,
                    'ACCOUNT_REJECTION',
                    "Rejected account registration for: {$user['email']} ({$user['first_name']} {$user['last_name']})",
                    'account',
                    $user_id,
                    'pending',
                    'rejected'
                );
                
                $_SESSION['success_message'] = "Account rejected and removed successfully!";
            }
            $stmt->close();
        }
    }
    
    header("Location: account_approval.php");
    exit();
}

// Pagination variables
$items_per_page = 6;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM account 
                WHERE (account_status = 'pending' OR account_status IS NULL) 
                AND (usertype = '' OR usertype IS NULL)";
$count_result = $conn->query($count_query);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Fetch pending accounts with pagination
$pending_query = "SELECT id, first_name, middle_name, last_name, email, barangay, id_type, resident_type, file_path, created_at 
                  FROM account 
                  WHERE (account_status = 'pending' OR account_status IS NULL) 
                  AND (usertype = '' OR usertype IS NULL)
                  ORDER BY created_at DESC
                  LIMIT $items_per_page OFFSET $offset";
$pending_result = $conn->query($pending_query);

// Pagination for approved accounts
$approved_items_per_page = 6;
$approved_page = isset($_GET['approved_page']) ? max(1, intval($_GET['approved_page'])) : 1;
$approved_offset = ($approved_page - 1) * $approved_items_per_page;

// Get total count for approved accounts pagination
$approved_count_query = "SELECT COUNT(*) as total FROM account 
                         WHERE account_status = 'approved' 
                         AND (usertype = '' OR usertype IS NULL)";
$approved_count_result = $conn->query($approved_count_query);
$approved_total_items = $approved_count_result->fetch_assoc()['total'];
$approved_total_pages = ceil($approved_total_items / $approved_items_per_page);

// Fetch approved accounts with pagination
$approved_query = "SELECT id, first_name, middle_name, last_name, email, barangay, id_type, resident_type, created_at, updated_at 
                   FROM account 
                   WHERE account_status = 'approved' 
                   AND (usertype = '' OR usertype IS NULL)
                   ORDER BY updated_at DESC 
                   LIMIT $approved_items_per_page OFFSET $approved_offset";
$approved_result = $conn->query($approved_query);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
  <title>Account Approval - Barangay 170</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="design/account_approval.css" rel="stylesheet">
</head>
<body>
  <!-- Header -->
  <div class="page-header">
    <div class="logo-section">
      <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRTDCuh4kIpAtR-QmjA1kTjE_8-HSd8LSt3Gw&s" alt="seal">
      <div class="title-section">
        <div>Barangay 170</div>
        <div>Account Approval System</div>
      </div>
    </div>

    <div class="header-actions">
      <a href="admindashboard.php" class="btn">
        <i class="fas fa-arrow-left"></i>
        Back to Dashboard
      </a>
    </div>
  </div>

  <div class="main-content">
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="success-message">
        <i class="fas fa-check-circle"></i>
        <?php 
        echo htmlspecialchars($_SESSION['success_message']); 
        unset($_SESSION['success_message']);
        ?>
      </div>
    <?php endif; ?>

    <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon pending">
            <i class="fas fa-clock"></i>
          </div>
          <div class="stat-info">
            <h3><?php echo $total_items; ?></h3>
              <p>Pending Approval</p>
            </div>
          </div>
      <div class="stat-card">
        <div class="stat-icon approved">
          <i class="fas fa-check-circle"></i>
        </div>
      <div class="stat-info">
        <h3><?php echo $approved_total_items; ?></h3>
          <p>Recently Approved</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon total">
          <i class="fas fa-users"></i>
        </div>
      <div class="stat-info">
        <h3><?php echo $total_items + $approved_total_items; ?></h3>
        <p>Total Accounts</p>
        </div>
      </div>
    </div>

    <!-- Pending Accounts Section -->
    <div class="section">
      <div class="section-header">
        <h2 class="section-title">
          <i class="fas fa-clock"></i>
          Pending Accounts
          <?php if ($total_items > 0): ?>
            <span class="badge-count"><?php echo $total_items; ?></span>
          <?php endif; ?>
        </h2>
      </div>

      <div class="accounts-grid">
        <?php if ($pending_result->num_rows > 0): ?>
          <?php while ($account = $pending_result->fetch_assoc()): 
            $initials = strtoupper(substr($account['first_name'], 0, 1) . substr($account['last_name'], 0, 1));
          ?>
            <div class="account-card">
              <div class="account-header">
                <div class="account-avatar"><?php echo $initials; ?></div>
                <div class="account-info">
                  <h3><?php echo htmlspecialchars($account['first_name'] . ' ' . $account['last_name']); ?></h3>
                  <p><?php echo htmlspecialchars($account['email']); ?></p>
                </div>
              </div>

              <div class="account-details">
                <div class="detail-row">
                  <span class="detail-label">Barangay</span>
                  <span class="detail-value"><?php echo htmlspecialchars($account['barangay']); ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">ID Type</span>
                  <span class="detail-value capitalize">
                    <?php echo htmlspecialchars(str_replace('-', ' ', $account['id_type'])); ?>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Resident Type</span>
                  <span class="detail-value capitalize"><?php echo htmlspecialchars($account['resident_type']); ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Registered</span>
                  <span class="detail-value">
                    <?php echo date('M j, Y g:i A', strtotime($account['created_at'])); ?>
                  </span>
                </div>
              </div>

              <?php if (!empty($account['file_path']) && $account['file_path'] !== 'uploads/default.jpg'): ?>
                <div class="id-preview">
                  <button 
                    class="id-preview-btn" 
                    onclick="showIDModal('<?php echo htmlspecialchars($account['file_path']); ?>', '<?php echo htmlspecialchars($account['first_name'] . ' ' . $account['last_name']); ?>')"
                  >
                    <i class="fas fa-eye"></i> View ID Document
                  </button>
                </div>
              <?php endif; ?>

              <div class="action-buttons">
                <form method="POST" style="flex: 1; margin: 0;" onsubmit="return confirm('Are you sure you want to approve this account?');">
                  <input type="hidden" name="user_id" value="<?php echo $account['id']; ?>">
                  <input type="hidden" name="action" value="approve">
                  <button type="submit" class="btn-approve">
                    <i class="fas fa-check"></i>
                    Approve
                  </button>
                </form>
                <form method="POST" style="flex: 1; margin: 0;" onsubmit="return confirm('Are you sure you want to reject and delete this account? This action cannot be undone.');">
                  <input type="hidden" name="user_id" value="<?php echo $account['id']; ?>">
                  <input type="hidden" name="action" value="reject">
                  <button type="submit" class="btn-reject">
                    <i class="fas fa-times"></i>
                    Reject
                  </button>
                </form>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="empty-state" style="grid-column: 1 / -1;">
            <i class="fas fa-check-circle"></i>
            <p>No pending accounts</p>
            <p style="font-size: 0.875rem;">All account registrations have been processed</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?page=<?php echo ($page - 1); ?>" class="pagination-btn">
              <i class="fas fa-chevron-left"></i>
              Previous
            </a>
          <?php else: ?>
            <button class="pagination-btn" disabled>
              <i class="fas fa-chevron-left"></i>
              Previous
            </button>
          <?php endif; ?>

          <?php
          // Page numbers logic
          $max_visible = 5;
          
          if ($total_pages <= $max_visible) {
            for ($i = 1; $i <= $total_pages; $i++) {
              $active_class = ($i == $page) ? 'active' : '';
              echo "<a href='?page=$i' class='page-number $active_class'>$i</a>";
            }
          } else {
            $active_class = ($page == 1) ? 'active' : '';
            echo "<a href='?page=1' class='page-number $active_class'>1</a>";
            
            if ($page > 3) {
              echo "<span class='page-ellipsis'>...</span>";
            }
            
            $start = max(2, $page - 1);
            $end = min($total_pages - 1, $page + 1);
            
            for ($i = $start; $i <= $end; $i++) {
              $active_class = ($i == $page) ? 'active' : '';
              echo "<a href='?page=$i' class='page-number $active_class'>$i</a>";
            }
            
            if ($page < $total_pages - 2) {
              echo "<span class='page-ellipsis'>...</span>";
            }
            
            $active_class = ($page == $total_pages) ? 'active' : '';
            echo "<a href='?page=$total_pages' class='page-number $active_class'>$total_pages</a>";
          }
          ?>

          <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo ($page + 1); ?>" class="pagination-btn">
              Next
              <i class="fas fa-chevron-right"></i>
            </a>
          <?php else: ?>
            <button class="pagination-btn" disabled>
              Next
              <i class="fas fa-chevron-right"></i>
            </button>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Recently Approved Section -->
    <div class="section">
      <div class="section-header">
        <h2 class="section-title">
          <i class="fas fa-check-circle"></i>
          Recently Approved Accounts
          <?php if ($approved_total_items > 0): ?>
            <span class="badge-count" style="background: #d1fae5; color: #16a34a;"><?php echo $approved_total_items; ?></span>
          <?php endif; ?>
        </h2>
      </div>

      <div class="accounts-grid">
        <?php if ($approved_result->num_rows > 0): ?>
          <?php while ($account = $approved_result->fetch_assoc()): 
            $initials = strtoupper(substr($account['first_name'], 0, 1) . substr($account['last_name'], 0, 1));
          ?>
            <div class="account-card" style="border-color: #16a34a; opacity: 0.8;">
              <div class="account-header">
                <div class="account-avatar" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                  <?php echo $initials; ?>
                </div>
                <div class="account-info">
                  <h3><?php echo htmlspecialchars($account['first_name'] . ' ' . $account['last_name']); ?></h3>
                  <p><?php echo htmlspecialchars($account['email']); ?></p>
                </div>
              </div>

              <div class="account-details">
                <div class="detail-row">
                  <span class="detail-label">Barangay</span>
                  <span class="detail-value"><?php echo htmlspecialchars($account['barangay']); ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Resident Type</span>
                  <span class="detail-value capitalize"><?php echo htmlspecialchars($account['resident_type']); ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Approved</span>
                  <span class="detail-value">
                    <?php echo date('M j, Y g:i A', strtotime($account['updated_at'])); ?>
                  </span>
                </div>
              </div>

              <div style="text-align: center; color: #16a34a; font-weight: 600; font-size: 0.875rem;">
                <i class="fas fa-check-circle"></i> Approved
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="empty-state" style="grid-column: 1 / -1;">
            <i class="fas fa-users"></i>
            <p>No approved accounts yet</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Approved Accounts Pagination -->
      <?php if ($approved_total_pages > 1): ?>
        <div class="pagination">
          <!-- Previous Button -->
          <?php if ($approved_page > 1): ?>
            <a href="?page=<?php echo $page; ?>&approved_page=<?php echo ($approved_page - 1); ?>" class="pagination-btn">
              <i class="fas fa-chevron-left"></i>
              Previous
            </a>
          <?php else: ?>
            <button class="pagination-btn" disabled>
              <i class="fas fa-chevron-left"></i>
              Previous
            </button>
          <?php endif; ?>

          <?php
          // Page numbers logic
          $max_visible = 5;
          
          if ($approved_total_pages <= $max_visible) {
            // Show all pages if total is less than max visible
            for ($i = 1; $i <= $approved_total_pages; $i++) {
              $active_class = ($i == $approved_page) ? 'active' : '';
              echo "<a href='?page=$page&approved_page=$i' class='page-number $active_class'>$i</a>";
            }
          } else {
            // Show first page
            $active_class = ($approved_page == 1) ? 'active' : '';
            echo "<a href='?page=$page&approved_page=1' class='page-number $active_class'>1</a>";
            
            // Show ellipsis if needed
            if ($approved_page > 3) {
              echo "<span class='page-ellipsis'>...</span>";
            }
            
            // Show pages around current page
            $start = max(2, $approved_page - 1);
            $end = min($approved_total_pages - 1, $approved_page + 1);
            
            for ($i = $start; $i <= $end; $i++) {
              $active_class = ($i == $approved_page) ? 'active' : '';
              echo "<a href='?page=$page&approved_page=$i' class='page-number $active_class'>$i</a>";
            }
            
            // Show ellipsis if needed
            if ($approved_page < $approved_total_pages - 2) {
              echo "<span class='page-ellipsis'>...</span>";
            }
            
            // Show last page
            $active_class = ($approved_page == $approved_total_pages) ? 'active' : '';
            echo "<a href='?page=$page&approved_page=$approved_total_pages' class='page-number $active_class'>$approved_total_pages</a>";
          }
          ?>

          <!-- Next Button -->
          <?php if ($approved_page < $approved_total_pages): ?>
            <a href="?page=<?php echo $page; ?>&approved_page=<?php echo ($approved_page + 1); ?>" class="pagination-btn">
              Next
              <i class="fas fa-chevron-right"></i>
            </a>
          <?php else: ?>
            <button class="pagination-btn" disabled>
              Next
              <i class="fas fa-chevron-right"></i>
            </button>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
    </div>
  </div>

  <!-- ID Preview Modal -->
  <div id="idModal" class="modal" onclick="closeIDModal()">
    <div class="modal-content" onclick="event.stopPropagation()">
      <div class="modal-header">
        <h3 class="modal-title" id="modalTitle">ID Document</h3>
        <button class="modal-close" onclick="closeIDModal()">×</button>
      </div>
      <div class="modal-body">
        <img id="modalImage" class="modal-image" src="" alt="ID Document">
      </div>
    </div>
  </div>

  <script>
    function showIDModal(imagePath, userName) {
      document.getElementById('modalTitle').textContent = userName + ' - ID Document';
      document.getElementById('modalImage').src = imagePath;
      document.getElementById('idModal').classList.add('show');
      document.body.style.overflow = 'hidden';
    }

    function closeIDModal() {
      document.getElementById('idModal').classList.remove('show');
      document.body.style.overflow = 'auto';
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeIDModal();
      }
    });
  </script>
</body>
</html>