<?php
/**
 * invitation_system.php - Email invitation system for memory submissions
 * Uses unified authentication system
 */

require_once 'config.php';

class InvitationSystem {
    private $pdo;
    
    public function __construct() {
        $this->pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
    
    /**
     * Create a new email invitation
     */
    public function createInvitation($ownerUserId, $invitedEmail, $title, $message = '', $maxSubmissions = null, $allowPublic = false, $expiresAt = null) {
        try {
            // Generate unique invitation token
            $token = $this->generateInvitationToken();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO email_invitations 
                (owner_user_id, invited_email, invitation_token, invitation_title, invitation_message, max_submissions, allow_public, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $ownerUserId,
                $invitedEmail,
                $token,
                $title,
                $message,
                $maxSubmissions,
                $allowPublic ? 1 : 0,
                $expiresAt
            ]);
            
            $invitationId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'invitation_id' => $invitationId,
                'token' => $token,
                'message' => 'Invitation created successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create invitation: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get invitation by token with validation
     */
    public function getInvitationByToken($token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM email_invitations 
                WHERE invitation_token = ? AND status = 'pending'
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->execute([$token]);
            
            $invitation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Track scan if invitation found
            if ($invitation) {
                $this->trackScan($invitation['id']);
            }
            
            return $invitation;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Validate invitation for submission
     */
    public function validateInvitationForSubmission($token, $submitterEmail = null) {
        try {
            $invitation = $this->getInvitationByToken($token);
            
            if (!$invitation) {
                return [
                    'valid' => false,
                    'error' => 'Invalid or expired invitation'
                ];
            }
            
            // Check if invitation is closed
            if ($invitation['status'] === 'closed') {
                return [
                    'valid' => false,
                    'error' => 'This invitation is no longer accepting submissions'
                ];
            }
            
            // Check if invitation has expired
            if ($invitation['expires_at'] && strtotime($invitation['expires_at']) < time()) {
                return [
                    'valid' => false,
                    'error' => 'This invitation has expired'
                ];
            }
            
            // Check submission limits
            if ($invitation['max_submissions'] && $invitation['current_submissions'] >= $invitation['max_submissions']) {
                return [
                    'valid' => false,
                    'error' => 'Maximum number of submissions reached for this invitation'
                ];
            }
            
            // Check if specific email is required
            if (!$invitation['allow_public'] && $submitterEmail) {
                if (strtolower($submitterEmail) !== strtolower($invitation['invited_email'])) {
                    return [
                        'valid' => false,
                        'error' => 'This invitation is only for: ' . $invitation['invited_email']
                    ];
                }
            }
            
            return [
                'valid' => true,
                'invitation' => $invitation
            ];
            
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => 'Validation error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get invitations by owner
     */
    public function getInvitationsByOwner($ownerUserId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ei.*, 
                       COUNT(ms.id) as submission_count
                FROM email_invitations ei
                LEFT JOIN memory_submissions ms ON ei.id = ms.invitation_id
                WHERE ei.owner_user_id = ?
                GROUP BY ei.id
                ORDER BY ei.created_at DESC
            ");
            $stmt->execute([$ownerUserId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Close an invitation (stop accepting submissions)
     */
    public function closeInvitation($invitationId, $ownerUserId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE email_invitations 
                SET status = 'closed', closed_at = NOW()
                WHERE id = ? AND owner_user_id = ?
            ");
            $stmt->execute([$invitationId, $ownerUserId]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Submit a memory for an invitation
     */
    public function submitMemory($invitationId, $submitterEmail, $title, $audioUrl, $imageUrl = null, $notes = '') {
        try {
            // Check if invitation is still open
            $invitation = $this->getInvitationById($invitationId);
            if (!$invitation || $invitation['status'] !== 'pending') {
                return [
                    'success' => false,
                    'error' => 'Invitation is no longer accepting submissions'
                ];
            }
            
            // Check submission limits
            if ($invitation['max_submissions'] && $invitation['current_submissions'] >= $invitation['max_submissions']) {
                return [
                    'success' => false,
                    'error' => 'Maximum number of submissions reached'
                ];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO memory_submissions 
                (invitation_id, submitter_email, memory_title, audio_url, image_url, submission_notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $invitationId,
                $submitterEmail,
                $title,
                $audioUrl,
                $imageUrl,
                $notes
            ]);
            
            // Update submission count
            $this->updateSubmissionCount($invitationId);
            
            return [
                'success' => true,
                'submission_id' => $this->pdo->lastInsertId(),
                'message' => 'Memory submitted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to submit memory: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get submissions for an invitation
     */
    public function getSubmissionsByInvitation($invitationId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM memory_submissions 
                WHERE invitation_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$invitationId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Generate unique invitation token
     */
    private function generateInvitationToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Get invitation by ID
     */
    public function getInvitationById($invitationId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM email_invitations WHERE id = ?");
            $stmt->execute([$invitationId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Update submission count for invitation
     */
    private function updateSubmissionCount($invitationId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE email_invitations 
                SET current_submissions = (
                    SELECT COUNT(*) FROM memory_submissions 
                    WHERE invitation_id = ?
                )
                WHERE id = ?
            ");
            $stmt->execute([$invitationId, $invitationId]);
            
            // Update daily analytics
            $this->updateDailyAnalytics($invitationId);
            
        } catch (Exception $e) {
            // Log error but don't fail the submission
            error_log("Failed to update submission count: " . $e->getMessage());
        }
    }
    
    /**
     * Track a scan for an invitation
     */
    private function trackScan($invitationId) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            
            // Insert scan record
            $stmt = $this->pdo->prepare("
                INSERT INTO invitation_scans 
                (invitation_id, scan_ip, scan_user_agent, scan_referer)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$invitationId, $ip, $userAgent, $referer]);
            
            // Update scan counts
            $this->updateScanCounts($invitationId);
            
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("Failed to track scan: " . $e->getMessage());
        }
    }
    
    /**
     * Update scan counts for invitation
     */
    private function updateScanCounts($invitationId) {
        try {
            // Get total scans
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total_scans,
                       COUNT(DISTINCT scan_ip) as unique_scans,
                       MAX(created_at) as last_scan
                FROM invitation_scans 
                WHERE invitation_id = ?
            ");
            $stmt->execute([$invitationId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Update invitation table
            $stmt = $this->pdo->prepare("
                UPDATE email_invitations 
                SET qr_scans = ?, unique_scans = ?, last_scan_at = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $stats['total_scans'],
                $stats['unique_scans'],
                $stats['last_scan'],
                $invitationId
            ]);
            
            // Update daily analytics
            $this->updateDailyAnalytics($invitationId);
            
        } catch (Exception $e) {
            error_log("Failed to update scan counts: " . $e->getMessage());
        }
    }
    
    /**
     * Update daily analytics
     */
    private function updateDailyAnalytics($invitationId) {
        try {
            $today = date('Y-m-d');
            
            // Get today's stats
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as scans,
                       COUNT(DISTINCT scan_ip) as unique_scans,
                       (SELECT COUNT(*) FROM memory_submissions 
                        WHERE invitation_id = ? AND DATE(created_at) = ?) as submissions
                FROM invitation_scans 
                WHERE invitation_id = ? AND DATE(created_at) = ?
            ");
            $stmt->execute([$invitationId, $today, $invitationId, $today]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Insert or update daily analytics
            $stmt = $this->pdo->prepare("
                INSERT INTO invitation_analytics 
                (invitation_id, date, scans, unique_scans, submissions)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                scans = VALUES(scans),
                unique_scans = VALUES(unique_scans),
                submissions = VALUES(submissions),
                updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([
                $invitationId,
                $today,
                $stats['scans'],
                $stats['unique_scans'],
                $stats['submissions']
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to update daily analytics: " . $e->getMessage());
        }
    }
    
    /**
     * Get analytics for an invitation
     */
    public function getInvitationAnalytics($invitationId, $days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT date, scans, unique_scans, submissions
                FROM invitation_analytics 
                WHERE invitation_id = ? 
                AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY date DESC
            ");
            $stmt->execute([$invitationId, $days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get scan details for an invitation
     */
    public function getInvitationScans($invitationId, $limit = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT scan_ip, scan_user_agent, scan_referer, created_at
                FROM invitation_scans 
                WHERE invitation_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$invitationId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Update submission status (approve/reject)
     */
    public function updateSubmissionStatus($submissionId, $status, $ownerUserId) {
        try {
            // First verify the submission belongs to an invitation owned by this user
            $stmt = $this->pdo->prepare("
                SELECT ms.*, ei.owner_user_id 
                FROM memory_submissions ms
                JOIN email_invitations ei ON ms.invitation_id = ei.id
                WHERE ms.id = ? AND ei.owner_user_id = ?
            ");
            $stmt->execute([$submissionId, $ownerUserId]);
            $submission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$submission) {
                return [
                    'success' => false,
                    'error' => 'Submission not found or access denied'
                ];
            }
            
            // Update the submission status
            if ($status === 'approved') {
                $stmt = $this->pdo->prepare("
                    UPDATE memory_submissions 
                    SET status = ?, approved_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
            } else {
                $stmt = $this->pdo->prepare("
                    UPDATE memory_submissions 
                    SET status = ?
                    WHERE id = ?
                ");
            }
            $stmt->execute([$status, $submissionId]);
            
            return [
                'success' => true,
                'message' => 'Submission ' . $status . ' successfully',
                'submission_id' => $submissionId,
                'status' => $status
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to update submission status: ' . $e->getMessage()
            ];
        }
    }
}
?>
