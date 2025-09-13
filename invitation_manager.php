<?php
/**
 * invitation_manager.php - Invitation management page for owners
 * Uses unified authentication system
 */

require_once 'unified_auth.php';
require_once 'config.php';
require_once 'invitation_system.php';

// Check if user is authenticated
$currentUser = getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$invitationSystem = new InvitationSystem();
$invitations = $invitationSystem->getInvitationsByOwner($currentUser['uid']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Manager - MemoWindow</title>
    <link rel="stylesheet" href="includes/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/app.css?v=<?php echo time(); ?>">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 80px;
        }
        
        .manager-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: #4a5568;
            font-size: 1.1rem;
        }
        
        .create-invitation-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
            box-sizing: border-box;
            max-width: 100%;
        }
        
        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
            min-height: 100px;
            resize: vertical;
            box-sizing: border-box;
            max-width: 100%;
            font-family: inherit;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            box-sizing: border-box;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .manager-container {
                padding: 15px;
            }
            
            .create-invitation-card {
                padding: 20px;
            }
            
            .page-header {
                padding: 20px;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            .manager-container {
                padding: 10px;
            }
            
            .create-invitation-card {
                padding: 15px;
            }
            
            .form-input,
            .form-textarea {
                font-size: 16px; /* Prevents zoom on iOS */
            }
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-checkbox input {
            width: 18px;
            height: 18px;
        }
        
        .create-button {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
        }
        
        .create-button:hover {
            background: #5a67d8;
        }
        
        .create-button:disabled {
            background: #a0aec0;
            cursor: not-allowed;
        }
        
        .invitations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .invitation-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .invitation-card:hover {
            transform: translateY(-2px);
        }
        
        .invitation-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .invitation-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .invitation-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef5e7;
            color: #d69e2e;
        }
        
        .status-closed {
            background: #fed7d7;
            color: #e53e3e;
        }
        
        .status-expired {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .invitation-details {
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .detail-label {
            color: #4a5568;
            font-weight: 500;
        }
        
        .detail-value {
            color: #2d3748;
            font-weight: 600;
        }
        
        .invitation-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .action-button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            flex: 1;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-danger {
            background: #e53e3e;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .invitation-link {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
            font-family: monospace;
            font-size: 0.875rem;
            word-break: break-all;
        }
        
        .copy-button {
            background: #38a169;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.75rem;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .copy-button:hover {
            background: #2f855a;
        }
        
        .success-message {
            background: #c6f6d5;
            color: #22543d;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error-message {
            background: #fed7d7;
            color: #742a2a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #4a5568;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #2d3748;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
        }
        
        .close-button {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #4a5568;
        }
        
        .submission-item {
            background: #f7fafc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .submission-title {
            font-weight: 600;
            color: #2d3748;
        }
        
        .submission-date {
            font-size: 0.875rem;
            color: #4a5568;
        }
        
        .submission-details {
            font-size: 0.9rem;
            color: #4a5568;
            margin-bottom: 10px;
        }
        
        .submission-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 4px 12px;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>
    
    <div class="manager-container">
        <div class="page-header">
            <h1 class="page-title">Invitation Manager</h1>
            <p class="page-subtitle">Create and manage memory collection invitations</p>
        </div>
        
        <div id="successMessage" class="success-message" style="display: none;"></div>
        <div id="errorMessage" class="error-message" style="display: none;"></div>
        
        <!-- Create Invitation Form -->
        <div class="create-invitation-card">
            <h2 style="margin: 0 0 20px 0; color: #2d3748;">Create New Invitation</h2>
            
            <form id="createInvitationForm">
                <div class="form-group">
                    <label for="invitationTitle" class="form-label">Invitation Title</label>
                    <input type="text" id="invitationTitle" class="form-input" required 
                           placeholder="e.g., Share Your Wedding Memories">
                </div>
                
                <div class="form-group">
                    <label for="invitedEmail" class="form-label">Invite Email Address</label>
                    <input type="email" id="invitedEmail" class="form-input" required 
                           placeholder="Enter email address to invite">
                </div>
                
                <div class="form-group">
                    <label for="invitationMessage" class="form-label">Invitation Message</label>
                    <textarea id="invitationMessage" class="form-input form-textarea" 
                              placeholder="Add a personal message for the invitation..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="maxSubmissions" class="form-label">Max Submissions (Optional)</label>
                        <input type="number" id="maxSubmissions" class="form-input" min="1" 
                               placeholder="Leave empty for unlimited">
                    </div>
                    
                    <div class="form-group">
                        <label for="expiresAt" class="form-label">Expires At (Optional)</label>
                        <input type="datetime-local" id="expiresAt" class="form-input">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-checkbox">
                        <input type="checkbox" id="allowPublic">
                        <label for="allowPublic">Allow public submissions (anyone with the link can submit)</label>
                    </div>
                </div>
                
                <button type="submit" id="createButton" class="create-button">
                    Create Invitation
                </button>
            </form>
        </div>
        
        <!-- Invitations List -->
        <div class="invitations-grid">
            <?php if (empty($invitations)): ?>
                <div class="empty-state">
                    <h3>No invitations yet</h3>
                    <p>Create your first invitation to start collecting memories!</p>
                </div>
            <?php else: ?>
                <?php foreach ($invitations as $invitation): ?>
                    <div class="invitation-card">
                        <div class="invitation-header">
                            <div>
                                <h3 class="invitation-title"><?php echo htmlspecialchars($invitation['invitation_title']); ?></h3>
                                <span class="invitation-status status-<?php echo $invitation['status']; ?>">
                                    <?php echo ucfirst($invitation['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="invitation-link">
                            <strong>Invitation Link:</strong><br>
                            <span id="link-<?php echo $invitation['id']; ?>">
                                <?php 
                                $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                                echo $baseUrl . '/record_memory.php?token=' . $invitation['invitation_token'];
                                ?>
                            </span>
                            <button class="copy-button" onclick="copyLink(<?php echo $invitation['id']; ?>)">Copy</button>
                        </div>
                        
                        <div class="invitation-details">
                            <div class="detail-row">
                                <span class="detail-label">Invited Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($invitation['invited_email']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">QR Scans:</span>
                                <span class="detail-value">
                                    <?php echo $invitation['qr_scans'] ?? 0; ?> 
                                    (<?php echo $invitation['unique_scans'] ?? 0; ?> unique)
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Submissions:</span>
                                <span class="detail-value">
                                    <?php echo $invitation['submission_count']; ?>
                                    <?php if ($invitation['max_submissions']): ?>
                                        / <?php echo $invitation['max_submissions']; ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Conversion Rate:</span>
                                <span class="detail-value">
                                    <?php 
                                    $scans = $invitation['qr_scans'] ?? 0;
                                    $submissions = $invitation['submission_count'];
                                    if ($scans > 0) {
                                        echo number_format(($submissions / $scans) * 100, 1) . '%';
                                    } else {
                                        echo '0%';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Created:</span>
                                <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($invitation['created_at'])); ?></span>
                            </div>
                            <?php if ($invitation['last_scan_at']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Last Scan:</span>
                                    <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($invitation['last_scan_at'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($invitation['expires_at']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Expires:</span>
                                    <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($invitation['expires_at'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($invitation['allow_public']): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Public:</span>
                                    <span class="detail-value" style="color: #38a169;">Yes</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="invitation-actions">
                            <button class="action-button btn-secondary" onclick="viewSubmissions(<?php echo $invitation['id']; ?>)">
                                Submissions
                            </button>
                            <button class="action-button btn-secondary" onclick="viewAnalytics(<?php echo $invitation['id']; ?>)">
                                Analytics
                            </button>
                            <button class="action-button btn-secondary" onclick="viewScans(<?php echo $invitation['id']; ?>)">
                                Scan Details
                            </button>
                            <?php if ($invitation['status'] === 'pending'): ?>
                                <button class="action-button btn-danger" onclick="closeInvitation(<?php echo $invitation['id']; ?>)">
                                    Close
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Submissions Modal -->
    <div id="submissionsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Memory Submissions</h2>
                <button class="close-button" onclick="closeModal()">&times;</button>
            </div>
            <div id="submissionsContent"></div>
        </div>
    </div>
    
    <script>
        // Create invitation form
        document.getElementById('createInvitationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                invited_email: document.getElementById('invitedEmail').value,
                title: document.getElementById('invitationTitle').value,
                message: document.getElementById('invitationMessage').value,
                max_submissions: document.getElementById('maxSubmissions').value || null,
                allow_public: document.getElementById('allowPublic').checked,
                expires_at: document.getElementById('expiresAt').value || null
            };
            
            const createButton = document.getElementById('createButton');
            createButton.disabled = true;
            createButton.textContent = 'Creating...';
            
            try {
                const response = await fetch('invitation_api.php?action=create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Invitation created successfully!');
                    document.getElementById('createInvitationForm').reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError(result.error || 'Failed to create invitation.');
                }
                
            } catch (error) {
                showError('An error occurred while creating the invitation.');
            } finally {
                createButton.disabled = false;
                createButton.textContent = 'Create Invitation';
            }
        });
        
        // Close invitation
        async function closeInvitation(invitationId) {
            if (!confirm('Are you sure you want to close this invitation? This will stop accepting new submissions.')) {
                return;
            }
            
            try {
                const response = await fetch('invitation_api.php?action=close', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ invitation_id: invitationId })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Invitation closed successfully!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showError(result.error || 'Failed to close invitation.');
                }
                
            } catch (error) {
                showError('An error occurred while closing the invitation.');
            }
        }
        
        // View submissions
        async function viewSubmissions(invitationId) {
            try {
                currentInvitationId = invitationId; // Store for refresh
                const response = await fetch(`invitation_api.php?action=submissions&invitation_id=${invitationId}`);
                const result = await response.json();
                
                if (result.success) {
                    displaySubmissions(result.submissions);
                    document.getElementById('submissionsModal').style.display = 'block';
                } else {
                    showError(result.error || 'Failed to load submissions.');
                }
                
            } catch (error) {
                showError('An error occurred while loading submissions.');
            }
        }
        
        // View analytics
        async function viewAnalytics(invitationId) {
            try {
                const response = await fetch(`invitation_api.php?action=analytics&invitation_id=${invitationId}&days=30`);
                const result = await response.json();
                
                if (result.success) {
                    displayAnalytics(result.analytics);
                    document.getElementById('submissionsModal').style.display = 'block';
                } else {
                    showError(result.error || 'Failed to load analytics.');
                }
                
            } catch (error) {
                showError('An error occurred while loading analytics.');
            }
        }
        
        // View scans
        async function viewScans(invitationId) {
            try {
                const response = await fetch(`invitation_api.php?action=scans&invitation_id=${invitationId}&limit=100`);
                const result = await response.json();
                
                if (result.success) {
                    displayScans(result.scans);
                    document.getElementById('submissionsModal').style.display = 'block';
                } else {
                    showError(result.error || 'Failed to load scan details.');
                }
                
            } catch (error) {
                showError('An error occurred while loading scan details.');
            }
        }
        
        // Store current invitation ID for refresh
        let currentInvitationId = null;
        
        // Display submissions in modal
        function displaySubmissions(submissions) {
            const content = document.getElementById('submissionsContent');
            document.querySelector('.modal-title').textContent = 'Memory Submissions';
            
            if (submissions.length === 0) {
                content.innerHTML = '<p style="text-align: center; color: #4a5568;">No submissions yet.</p>';
                return;
            }
            
            let html = '';
            submissions.forEach(submission => {
                html += `
                    <div class="submission-item" data-invitation-id="${submission.invitation_id}">
                        <div class="submission-header">
                            <span class="submission-title">${submission.memory_title}</span>
                            <span class="submission-date">${new Date(submission.created_at).toLocaleDateString()}</span>
                        </div>
                        <div class="submission-details">
                            <strong>From:</strong> ${submission.submitter_email}<br>
                            ${submission.submission_notes ? `<strong>Notes:</strong> ${submission.submission_notes}<br>` : ''}
                            <strong>Status:</strong> <span style="color: ${submission.status === 'pending' ? '#d69e2e' : submission.status === 'approved' ? '#38a169' : '#e53e3e'}">${submission.status}</span>
                        </div>
                        <div class="submission-actions">
                            <button class="action-button btn-primary btn-sm" onclick="playAudio('${submission.audio_url}')">Play Audio</button>
                            ${submission.status === 'pending' ? `
                                <button class="action-button btn-secondary btn-sm" onclick="approveSubmission(${submission.id})">Approve</button>
                                <button class="action-button btn-danger btn-sm" onclick="rejectSubmission(${submission.id})">Reject</button>
                            ` : `
                                <span style="color: ${submission.status === 'approved' ? '#38a169' : '#e53e3e'}; font-weight: bold;">
                                    ${submission.status === 'approved' ? '✅ Approved' : '❌ Rejected'}
                                </span>
                            `}
                        </div>
                    </div>
                `;
            });
            
            content.innerHTML = html;
        }
        
        // Display analytics in modal
        function displayAnalytics(analytics) {
            const content = document.getElementById('submissionsContent');
            document.querySelector('.modal-title').textContent = 'Analytics (Last 30 Days)';
            
            if (analytics.length === 0) {
                content.innerHTML = '<p style="text-align: center; color: #4a5568;">No analytics data available.</p>';
                return;
            }
            
            // Calculate totals
            const totals = analytics.reduce((acc, day) => {
                acc.scans += parseInt(day.scans) || 0;
                acc.uniqueScans += parseInt(day.unique_scans) || 0;
                acc.submissions += parseInt(day.submissions) || 0;
                return acc;
            }, { scans: 0, uniqueScans: 0, submissions: 0 });
            
            const conversionRate = totals.scans > 0 ? ((totals.submissions / totals.scans) * 100).toFixed(1) : 0;
            
            let html = `
                <div style="background: #f7fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 15px 0; color: #2d3748;">Summary</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: #667eea;">${totals.scans}</div>
                            <div style="color: #4a5568; font-size: 0.9rem;">Total Scans</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: #38a169;">${totals.uniqueScans}</div>
                            <div style="color: #4a5568; font-size: 0.9rem;">Unique Scans</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: #d69e2e;">${totals.submissions}</div>
                            <div style="color: #4a5568; font-size: 0.9rem;">Submissions</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: #e53e3e;">${conversionRate}%</div>
                            <div style="color: #4a5568; font-size: 0.9rem;">Conversion Rate</div>
                        </div>
                    </div>
                </div>
            `;
            
            html += '<h3 style="margin: 0 0 15px 0; color: #2d3748;">Daily Breakdown</h3>';
            
            analytics.forEach(day => {
                const dayConversion = day.scans > 0 ? ((day.submissions / day.scans) * 100).toFixed(1) : 0;
                html += `
                    <div class="submission-item">
                        <div class="submission-header">
                            <span class="submission-title">${new Date(day.date).toLocaleDateString()}</span>
                            <span class="submission-date">${dayConversion}% conversion</span>
                        </div>
                        <div class="submission-details">
                            <strong>Scans:</strong> ${day.scans} (${day.unique_scans} unique)<br>
                            <strong>Submissions:</strong> ${day.submissions}
                        </div>
                    </div>
                `;
            });
            
            content.innerHTML = html;
        }
        
        // Display scans in modal
        function displayScans(scans) {
            const content = document.getElementById('submissionsContent');
            document.querySelector('.modal-title').textContent = 'Scan Details';
            
            if (scans.length === 0) {
                content.innerHTML = '<p style="text-align: center; color: #4a5568;">No scans recorded yet.</p>';
                return;
            }
            
            let html = '';
            scans.forEach(scan => {
                const userAgent = scan.scan_user_agent ? scan.scan_user_agent.substring(0, 100) + '...' : 'Unknown';
                const referer = scan.scan_referer || 'Direct';
                
                html += `
                    <div class="submission-item">
                        <div class="submission-header">
                            <span class="submission-title">${scan.scan_ip}</span>
                            <span class="submission-date">${new Date(scan.created_at).toLocaleString()}</span>
                        </div>
                        <div class="submission-details">
                            <strong>IP Address:</strong> ${scan.scan_ip}<br>
                            <strong>User Agent:</strong> ${userAgent}<br>
                            <strong>Referer:</strong> ${referer}
                        </div>
                    </div>
                `;
            });
            
            content.innerHTML = html;
        }
        
        // Copy invitation link
        function copyLink(invitationId) {
            const linkElement = document.getElementById(`link-${invitationId}`);
            const link = linkElement.textContent.trim();
            
            navigator.clipboard.writeText(link).then(() => {
                showSuccess('Link copied to clipboard!');
            }).catch(() => {
                showError('Failed to copy link.');
            });
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('submissionsModal').style.display = 'none';
        }
        
        // Play audio
        function playAudio(audioUrl) {
            // Create audio element and play
            const audio = new Audio(audioUrl);
            audio.controls = true;
            audio.style.width = '100%';
            audio.style.marginTop = '10px';
            
            // Show audio player in a modal or inline
            const audioContainer = document.createElement('div');
            audioContainer.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                z-index: 10000;
                min-width: 300px;
                text-align: center;
            `;
            
            const closeButton = document.createElement('button');
            closeButton.textContent = 'Close';
            closeButton.style.cssText = `
                margin-top: 10px;
                padding: 8px 16px;
                background: #667eea;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            `;
            closeButton.onclick = () => document.body.removeChild(audioContainer);
            
            audioContainer.appendChild(audio);
            audioContainer.appendChild(closeButton);
            document.body.appendChild(audioContainer);
            
            // Auto-play the audio
            audio.play().catch(e => {
                console.log('Auto-play prevented:', e);
                // Show a play button if auto-play is blocked
                const playButton = document.createElement('button');
                playButton.textContent = '▶️ Play Audio';
                playButton.style.cssText = `
                    margin-top: 10px;
                    padding: 10px 20px;
                    background: #38a169;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 16px;
                `;
                playButton.onclick = () => {
                    audio.play();
                    playButton.style.display = 'none';
                };
                audioContainer.insertBefore(playButton, closeButton);
            });
        }
        
        // Approve submission
        async function approveSubmission(submissionId) {
            try {
                const response = await fetch('invitation_api.php?action=approve', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        submission_id: submissionId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Submission approved successfully!');
                    // Refresh the submissions modal
                    if (currentInvitationId) {
                        viewSubmissions(currentInvitationId);
                    }
                } else {
                    showError(result.error || 'Failed to approve submission');
                }
            } catch (error) {
                showError('An error occurred while approving the submission');
            }
        }
        
        // Reject submission
        async function rejectSubmission(submissionId) {
            if (!confirm('Are you sure you want to reject this submission? This action cannot be undone.')) {
                return;
            }
            
            try {
                const response = await fetch('invitation_api.php?action=reject', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        submission_id: submissionId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Submission rejected successfully!');
                    // Refresh the submissions modal
                    if (currentInvitationId) {
                        viewSubmissions(currentInvitationId);
                    }
                } else {
                    showError(result.error || 'Failed to reject submission');
                }
            } catch (error) {
                showError('An error occurred while rejecting the submission');
            }
        }
        
        // Show success message
        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            document.getElementById('errorMessage').style.display = 'none';
            setTimeout(() => successDiv.style.display = 'none', 5000);
        }
        
        // Show error message
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            document.getElementById('successMessage').style.display = 'none';
            setTimeout(() => errorDiv.style.display = 'none', 5000);
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('submissionsModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
