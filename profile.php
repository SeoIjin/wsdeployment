<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Handle logout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    session_destroy();
    header("Location: sign-in.php");
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

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM account WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: signin.php");
    exit();
}

// Check if user is admin
$is_admin = ($user['usertype'] === 'admin');

// Get join date
$join_date = isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : date('F j, Y');

// Get profile picture
$profile_picture = !empty($user['profile_picture']) ? $user['profile_picture'] : null;

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Barangay 170</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="design/profile.css" rel="stylesheet">
</head>
<body>
    <header>
    <a href="homepage.php" class="logo-section">
        <button 
            class="back-btn" 
            onclick="event.preventDefault(); window.location.href='homepage.php'"
            title="Go back"
        >
            <i class="fas fa-arrow-left"></i>
        </button>
        <img 
            src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRTDCuh4kIpAtR-QmjA1kTjE_8-HSd8LSt3Gw&s" 
            alt="Logo"
            class="logo-img"
        />
            <div class="logo-text">
                <div>Barangay 170</div>
                <div>Community Portal</div>
            </div>
        </a>
        <form method="POST" style="display: inline; margin: 0;">
            <button type="submit" name="logout" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>Logout
            </button>
        </form>
    </header>

    <div class="main-container">
        <div class="profile-card">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-picture-container">
                    <?php if ($profile_picture && file_exists($profile_picture)): ?>
                        <img src="<?php echo htmlspecialchars($profile_picture); ?>" 
                             alt="Profile Picture" 
                             class="profile-picture-img"
                             id="profilePicturePreview">
                    <?php else: ?>
                        <div class="profile-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    <?php endif; ?>
                    <button type="button" class="change-picture-btn" onclick="document.getElementById('profilePictureInput').click()">
                        <i class="fas fa-camera"></i>
                    </button>
                    <input type="file" id="profilePictureInput" accept="image/*" onchange="uploadProfilePicture(this)">
                </div>
                <div id="profilePictureMessage"></div>
                
                <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']); ?></h1>
                <div class="profile-email">
                    <i class="fas fa-envelope"></i>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="profile-info">
                <h2>Profile Information</h2>
                
                <div class="info-grid">
                    <!-- Contact Number -->
                    <div class="info-card editable-field">
                        <div class="info-card-header">
                            <div class="info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h3>Contact Number</h3>
                        </div>
                        <p id="contactDisplay">
                            <?php echo htmlspecialchars($user['contact_number']); ?>
                            <button type="button" class="edit-button" onclick="editContact()">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </p>
                        <div id="contactEdit" style="display: none;">
                            <input type="text" 
                                   id="contactInput" 
                                   class="edit-input" 
                                   value="<?php echo htmlspecialchars($user['contact_number']); ?>"
                                   placeholder="Enter contact number">
                            <div class="edit-actions">
                                <button type="button" class="save-button" onclick="saveContact()">
                                    <i class="fas fa-check"></i> Save
                                </button>
                                <button type="button" class="cancel-button" onclick="cancelContactEdit()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                        <div id="contactMessage"></div>
                    </div>

                    <!-- Account Type -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <div class="info-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Account Type</h3>
                        </div>
                        <p><?php echo $is_admin ? 'Admin (Barangay Official)' : 'Regular User (Citizen)'; ?></p>
                    </div>

                    <!-- Member Since -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <div class="info-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <h3>Member Since</h3>
                        </div>
                        <p><?php echo $join_date; ?></p>
                    </div>

                    <?php if (isset($user['barangay']) && !empty($user['barangay'])): ?>
                    <!-- Barangay -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h3>Barangay</h3>
                        </div>
                        <p><?php echo htmlspecialchars($user['barangay']); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($user['id_type']) && !empty($user['id_type'])): ?>
                    <!-- Valid ID Type -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <div class="info-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <h3>Valid ID Type</h3>
                        </div>
                        <p class="capitalize"><?php echo htmlspecialchars(str_replace('-', ' ', $user['id_type'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($user['file_path']) && !empty($user['file_path'])): ?>
                    <!-- Valid ID Document -->
                    <div class="info-card" style="grid-column: 1 / -1;">
                        <div class="info-card-header">
                            <div class="info-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <h3>Valid ID Document</h3>
                        </div>
                        <div class="id-image">
                            <img 
                                src="<?php echo htmlspecialchars($user['file_path']); ?>" 
                                alt="Valid ID"
                            />
                            <p>ID verified during registration</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Account Status -->
                <div class="account-status">
                    <h3>Account Status</h3>
                    <div class="status-indicator">
                        <span class="status-dot"></span>
                        <span class="status-text">Active and in good standing</span>
                    </div>
                </div>

                <!-- Change Password Section -->
<div class="change-password-section">
    <h3>
        <i class="fas fa-lock"></i>
        Change Password
    </h3>
    <button type="button" class="toggle-password-form-btn" onclick="togglePasswordForm()">
        <i class="fas fa-key"></i>
        <span id="togglePasswordText">Change Password</span>
    </button>
    
    <div id="passwordFormContainer" style="display: none;">
        <form class="password-form" onsubmit="changePassword(event)">
            <div class="password-input-group">
                <label for="currentPassword">Current Password</label>
                <div class="password-input-wrapper">
                    <input 
                        type="password" 
                        id="currentPassword" 
                        class="password-input" 
                        placeholder="Enter current password"
                        required
                    >
                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('currentPassword', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="password-input-group">
                <label for="newPassword">New Password</label>
                <div class="password-input-wrapper">
                    <input 
                        type="password" 
                        id="newPassword" 
                        class="password-input" 
                        placeholder="Enter new password (min. 6 characters)"
                        required
                    >
                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('newPassword', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="password-input-group">
                <label for="confirmNewPassword">Confirm New Password</label>
                <div class="password-input-wrapper">
                    <input 
                        type="password" 
                        id="confirmNewPassword" 
                        class="password-input" 
                        placeholder="Confirm new password"
                        required
                    >
                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirmNewPassword', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div id="passwordMessage"></div>

            <div class="password-actions">
                <button type="submit" class="change-password-btn">
                    <i class="fas fa-check"></i>
                    Update Password
                </button>
                <button type="button" class="cancel-password-btn" onclick="togglePasswordForm()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

                <!-- Additional Info for Admin -->
                <?php if ($is_admin): ?>
                <div class="admin-info">
                    <h3>Admin Access</h3>
                    <p>
                        You have administrative privileges to manage health requests, 
                        update request statuses, and send notifications to citizens.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <?php if (!$is_admin): ?>
            <a href="submitreq.php" class="action-card">
                <div class="action-icon">📝</div>
                <h3>Submit Request</h3>
                <p>Create a new health request</p>
            </a>

            <a href="trackreq.php" class="action-card">
                <div class="action-icon">🔍</div>
                <h3>Track Request</h3>
                <p>Check your request status</p>
            </a>
            <?php else: ?>
            <a href="admin-dashboard.php" class="action-card">
                <div class="action-icon">📊</div>
                <h3>Admin Dashboard</h3>
                <p>Manage all health requests</p>
            </a>
            <?php endif; ?>

            <form method="POST" style="display: contents;">
                <button type="submit" name="logout" class="action-card logout" style="text-align: center;">
                    <div class="action-icon">🚪</div>
                    <h3>Logout</h3>
                    <p>Sign out of your account</p>
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-grid">
                <!-- Barangay Health Office -->
                <div class="footer-section">
                    <h3>🏢 Barangay Health Office</h3>
                    <div class="footer-content">
                        <div class="footer-item">
                            <div class="footer-label">📍 Address</div>
                            <div class="footer-value">Deparo, Caloocan City, Metro Manila</div>
                        </div>
                        <div class="footer-item">
                            <div class="footer-label">📞 Hotline</div>
                            <div class="footer-value">(02) 8123-4567</div>
                        </div>
                        <div class="footer-item">
                            <div class="footer-label">📧 Email</div>
                            <div class="footer-value">K1contrerascris@gmail.com</div>
                        </div>
                        <div class="footer-item">
                            <div class="footer-label">🕐 Office Hours</div>
                            <div class="footer-value">Mon-Fri, 8:00 AM - 5:00 PM</div>
                        </div>
                    </div>
                </div>

                <!-- Emergency Hotlines -->
                <div class="footer-section">
                    <h3>📞 Emergency Hotlines</h3>
                    <div class="footer-content">
                        <div class="emergency-item">
                            <span>Police</span>
                            <span>(02) 8426-4663</span>
                        </div>
                        <div class="emergency-item">
                            <span>BFP</span>
                            <span>(02) 8245 0849</span>
                        </div>
                    </div>
                </div>

                <!-- Hospitals Near Barangay -->
                <div class="footer-section">
                    <h3>🏥 Hospitals Near Barangay</h3>
                    <div class="footer-content">
                        <div class="hospital-item">
                            <div class="hospital-name">Camarin Doctors Hospital</div>
                            <div class="hospital-phone">(02) 2-7004-2881</div>
                        </div>
                        <div class="hospital-item">
                            <div class="hospital-name">Caloocan City North Medical</div>
                            <div class="hospital-phone">(02) 8288 7077</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Copyright -->
            <div class="footer-copyright">
                <p>© 2025 Barangay 170, Deparo, Caloocan. All rights reserved.</p>
                <p>Barangay Citizen Document Request System (BCDRS)</p>
            </div>
        </div>
    </footer>
    <script>
        // Contact Number Editing
function editContact() {
    document.getElementById('contactDisplay').style.display = 'none';
    document.getElementById('contactEdit').style.display = 'block';
    document.getElementById('contactInput').focus();
}

function cancelContactEdit() {
    document.getElementById('contactDisplay').style.display = 'block';
    document.getElementById('contactEdit').style.display = 'none';
    document.getElementById('contactMessage').innerHTML = '';
}

async function saveContact() {
    const contactInput = document.getElementById('contactInput');
    const contactNumber = contactInput.value.trim();
    
    if (!contactNumber) {
        showMessage('contactMessage', 'Contact number cannot be empty', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('update_contact', '1');
    formData.append('contact_number', contactNumber);
    
    try {
        const response = await fetch('api_update_profile.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update display
            document.getElementById('contactDisplay').innerHTML = 
                contactNumber + 
                ' <button type="button" class="edit-button" onclick="editContact()"><i class="fas fa-edit"></i> Edit</button>';
            
            cancelContactEdit();
            showMessage('contactMessage', result.message, 'success');
            
            // Clear success message after 3 seconds
            setTimeout(() => {
                document.getElementById('contactMessage').innerHTML = '';
            }, 3000);
        } else {
            showMessage('contactMessage', result.error, 'error');
        }
    } catch (error) {
        showMessage('contactMessage', 'An error occurred. Please try again.', 'error');
    }
}

// Profile Picture Upload
async function uploadProfilePicture(input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showMessage('profilePictureMessage', 'Invalid file type. Only JPG, PNG, and GIF allowed', 'error');
        return;
    }
    
    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showMessage('profilePictureMessage', 'File size too large. Maximum 5MB allowed', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('profile_picture', file);
    
    try {
        showMessage('profilePictureMessage', 'Uploading...', 'success');
        
        const response = await fetch('api_update_profile.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update preview
            const preview = document.getElementById('profilePicturePreview');
            if (preview) {
                preview.src = result.filepath + '?' + new Date().getTime(); // Add timestamp to force reload
            } else {
                // If no preview exists, create one
                const container = document.querySelector('.profile-picture-container');
                const avatar = container.querySelector('.profile-avatar');
                if (avatar) avatar.remove();
                
                const img = document.createElement('img');
                img.src = result.filepath + '?' + new Date().getTime();
                img.alt = 'Profile Picture';
                img.className = 'profile-picture-img';
                img.id = 'profilePicturePreview';
                container.insertBefore(img, container.firstChild);
            }
            
            showMessage('profilePictureMessage', result.message, 'success');
            
            // Clear success message after 3 seconds
            setTimeout(() => {
                document.getElementById('profilePictureMessage').innerHTML = '';
            }, 3000);
        } else {
            showMessage('profilePictureMessage', result.error, 'error');
        }
    } catch (error) {
        showMessage('profilePictureMessage', 'An error occurred. Please try again.', 'error');
    }
    
    // Clear the input
    input.value = '';
}

// Toggle Password Form
function togglePasswordForm() {
    const container = document.getElementById('passwordFormContainer');
    const toggleText = document.getElementById('togglePasswordText');
    const toggleBtn = document.querySelector('.toggle-password-form-btn');
    
    if (container.style.display === 'none') {
        container.style.display = 'block';
        toggleText.textContent = 'Hide Form';
        toggleBtn.querySelector('i').className = 'fas fa-eye-slash';
    } else {
        container.style.display = 'none';
        toggleText.textContent = 'Change Password';
        toggleBtn.querySelector('i').className = 'fas fa-key';
        // Clear form
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmNewPassword').value = '';
        document.getElementById('passwordMessage').innerHTML = '';
    }
}

// Toggle Password Visibility
function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Change Password
async function changePassword(event) {
    event.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmNewPassword = document.getElementById('confirmNewPassword').value;
    
    // Validation
    if (newPassword.length < 6) {
        showMessage('passwordMessage', 'New password must be at least 6 characters long', 'error');
        return;
    }
    
    if (newPassword !== confirmNewPassword) {
        showMessage('passwordMessage', 'New passwords do not match', 'error');
        return;
    }
    
    if (currentPassword === newPassword) {
        showMessage('passwordMessage', 'New password must be different from current password', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('change_password', '1');
    formData.append('current_password', currentPassword);
    formData.append('new_password', newPassword);
    
    try {
        showMessage('passwordMessage', 'Updating password...', 'success');
        
        const response = await fetch('api_update_profile.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('passwordMessage', result.message, 'success');
            
            // Clear form after 2 seconds
            setTimeout(() => {
                document.getElementById('currentPassword').value = '';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmNewPassword').value = '';
                togglePasswordForm();
            }, 2000);
        } else {
            showMessage('passwordMessage', result.error, 'error');
        }
    } catch (error) {
        showMessage('passwordMessage', 'An error occurred. Please try again.', 'error');
    }
}

// Helper function to show messages
function showMessage(elementId, message, type) {
    const element = document.getElementById(elementId);
    element.innerHTML = `<div class="${type}-message">${message}</div>`;
}
    </script>
</body>
</html>