<?php
/**
 * invitation_api.php - API endpoints for invitation management
 * Uses unified authentication system
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'config.php';
require_once 'unified_auth.php';
require_once 'invitation_system.php';

// Get current user from unified auth system
$currentUser = getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$invitationSystem = new InvitationSystem();

try {
    switch ($method) {
        case 'POST':
            if ($action === 'create') {
                createInvitation();
            } elseif ($action === 'submit') {
                submitMemory();
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
            break;
            
        case 'PUT':
            if ($action === 'approve') {
                approveSubmission();
            } elseif ($action === 'reject') {
                rejectSubmission();
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
            break;
            
        case 'GET':
            if ($action === 'list') {
                listInvitations();
            } elseif ($action === 'get') {
                getInvitation();
            } elseif ($action === 'submissions') {
                getSubmissions();
            } elseif ($action === 'analytics') {
                getAnalytics();
            } elseif ($action === 'scans') {
                getScans();
            } elseif ($action === 'validate') {
                validateInvitation();
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
            break;
            
        case 'PUT':
            if ($action === 'close') {
                closeInvitation();
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Create a new invitation
 */
function createInvitation() {
    global $currentUser, $invitationSystem;
    
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['invited_email', 'title'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            return;
        }
    }
    
    $result = $invitationSystem->createInvitation(
        $currentUser['uid'],
        $input['invited_email'],
        $input['title'],
        $input['message'] ?? '',
        $input['max_submissions'] ?? null,
        $input['allow_public'] ?? false,
        $input['expires_at'] ?? null
    );
    
    if ($result['success']) {
        // TODO: Send email invitation here
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * List invitations for current user
 */
function listInvitations() {
    global $currentUser, $invitationSystem;
    
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    $invitations = $invitationSystem->getInvitationsByOwner($currentUser['uid']);
    
    echo json_encode([
        'success' => true,
        'invitations' => $invitations
    ]);
}

/**
 * Get invitation by token (public access)
 */
function getInvitation() {
    global $invitationSystem;
    
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Token required']);
        return;
    }
    
    $invitation = $invitationSystem->getInvitationByToken($token);
    
    if ($invitation) {
        echo json_encode([
            'success' => true,
            'invitation' => $invitation
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Invitation not found or expired']);
    }
}

/**
 * Submit a memory for an invitation
 */
function submitMemory() {
    global $invitationSystem;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['invitation_id', 'submitter_email', 'title', 'audio_url'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            return;
        }
    }
    
    // Get invitation details for email validation
    $invitation = $invitationSystem->getInvitationById($input['invitation_id']);
    if (!$invitation) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid invitation']);
        return;
    }
    
    // Validate email if invitation is not public
    if (!$invitation['allow_public'] && strtolower($input['submitter_email']) !== strtolower($invitation['invited_email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email must match the invited email address']);
        return;
    }
    
    $result = $invitationSystem->submitMemory(
        $input['invitation_id'],
        $input['submitter_email'],
        $input['title'],
        $input['audio_url'],
        $input['image_url'] ?? null,
        $input['notes'] ?? ''
    );
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Get submissions for an invitation
 */
function getSubmissions() {
    global $currentUser, $invitationSystem;
    
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    $invitationId = $_GET['invitation_id'] ?? '';
    if (empty($invitationId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invitation ID required']);
        return;
    }
    
    $submissions = $invitationSystem->getSubmissionsByInvitation($invitationId);
    
    echo json_encode([
        'success' => true,
        'submissions' => $submissions
    ]);
}

/**
 * Close an invitation
 */
function closeInvitation() {
    global $currentUser, $invitationSystem;
    
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $invitationId = $input['invitation_id'] ?? '';
    
    if (empty($invitationId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invitation ID required']);
        return;
    }
    
    $success = $invitationSystem->closeInvitation($invitationId, $currentUser['uid']);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Invitation closed successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Failed to close invitation']);
    }
}

/**
 * Get analytics for an invitation
 */
function getAnalytics() {
    global $currentUser, $invitationSystem;
    
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    $invitationId = $_GET['invitation_id'] ?? '';
    $days = $_GET['days'] ?? 30;
    
    if (empty($invitationId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invitation ID required']);
        return;
    }
    
    $analytics = $invitationSystem->getInvitationAnalytics($invitationId, $days);
    
    echo json_encode([
        'success' => true,
        'analytics' => $analytics
    ]);
}

/**
 * Get scan details for an invitation
 */
function getScans() {
    global $currentUser, $invitationSystem;
    
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    $invitationId = $_GET['invitation_id'] ?? '';
    $limit = $_GET['limit'] ?? 50;
    
    if (empty($invitationId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invitation ID required']);
        return;
    }
    
    $scans = $invitationSystem->getInvitationScans($invitationId, $limit);
    
    echo json_encode([
        'success' => true,
        'scans' => $scans
    ]);
}

/**
 * Validate invitation (public endpoint - no auth required)
 */
function validateInvitation() {
    global $invitationSystem;
    
    $token = $_GET['token'] ?? '';
    $submitterEmail = $_GET['email'] ?? null;
    
    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invitation token required']);
        return;
    }
    
    $validation = $invitationSystem->validateInvitationForSubmission($token, $submitterEmail);
    
    if ($validation['valid']) {
        echo json_encode([
            'success' => true,
            'valid' => true,
            'invitation' => $validation['invitation']
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'valid' => false,
            'error' => $validation['error']
        ]);
    }
}

/**
 * Approve a memory submission
 */
function approveSubmission() {
    global $currentUser, $invitationSystem;
    
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $submissionId = $input['submission_id'] ?? '';
    
    if (empty($submissionId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Submission ID required']);
        return;
    }
    
    $result = $invitationSystem->updateSubmissionStatus($submissionId, 'approved', $currentUser['uid']);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Reject a memory submission
 */
function rejectSubmission() {
    global $currentUser, $invitationSystem;
    
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $submissionId = $input['submission_id'] ?? '';
    
    if (empty($submissionId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Submission ID required']);
        return;
    }
    
    $result = $invitationSystem->updateSubmissionStatus($submissionId, 'rejected', $currentUser['uid']);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}
?>
