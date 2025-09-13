<?php
/**
 * memory_detail.php - Frontend memory detail page
 */

require_once 'unified_auth.php';
require_once 'config.php';

// Get memory ID from URL parameter
$memoryId = $_GET['id'] ?? null;

if (!$memoryId) {
    header('Location: app.php');
    exit;
}

// Check if user is authenticated
$currentUser = getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Get memory details
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            original_name,
            image_url,
            audio_url,
            custom_url,
            audio_size,
            audio_duration,
            created_at,
            user_id
        FROM wave_assets
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$memoryId, $currentUser['uid']]);
    $memory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$memory) {
        // Debug: Log the issue
        error_log("Memory detail: Memory not found for ID $memoryId and user " . $currentUser['uid']);
        
        // Check if memory exists but belongs to different user
        $stmt = $pdo->prepare("SELECT id, title, user_id FROM wave_assets WHERE id = ?");
        $stmt->execute([$memoryId]);
        $memoryExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($memoryExists) {
            error_log("Memory detail: Memory exists but belongs to user " . $memoryExists['user_id'] . ", current user is " . $currentUser['uid']);
        } else {
            error_log("Memory detail: Memory with ID $memoryId does not exist");
        }
        
        header('Location: app.php');
        exit;
    }
    
    // Fetch approved submissions for this memory owner's invitations
    $approvedSubmissions = [];
    try {
        // Check if this memory owner has any invitations
        $stmt = $pdo->prepare("
            SELECT ei.id as invitation_id, ei.invitation_title, ei.owner_user_id
            FROM email_invitations ei
            WHERE ei.owner_user_id = ? AND ei.status = 'pending'
        ");
        $stmt->execute([$currentUser['uid']]);
        $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get approved submissions for each invitation
        foreach ($invitations as $invitation) {
            $stmt = $pdo->prepare("
                SELECT ms.*, ei.invitation_title
                FROM memory_submissions ms
                JOIN email_invitations ei ON ms.invitation_id = ei.id
                WHERE ms.invitation_id = ? AND ms.status = 'approved'
                ORDER BY ms.approved_at DESC
            ");
            $stmt->execute([$invitation['invitation_id']]);
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $approvedSubmissions = array_merge($approvedSubmissions, $submissions);
        }
        
        // Fetch generated audio for this memory
        $generatedAudios = [];
        try {
            $stmt = $pdo->prepare("
                SELECT ga.*, vc.voice_name, vc.voice_id
                FROM generated_audio ga
                JOIN voice_clones vc ON ga.voice_clone_id = vc.id
                WHERE vc.source_memory_id = ?
                ORDER BY ga.created_at DESC
            ");
            $stmt->execute([$memoryId]);
            $generatedAudios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If there's an error fetching generated audio, continue without them
            $generatedAudios = [];
        }
    } catch (PDOException $e) {
        // If there's an error fetching submissions, continue without them
        $approvedSubmissions = [];
    }
    
    // Get user information separately to avoid collation issues
    $stmt = $pdo->prepare("SELECT display_name, email FROM users WHERE firebase_uid = ?");
    $stmt->execute([$currentUser['uid']]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Add user info to memory array
    $memory['user_name'] = $userInfo['display_name'] ?? null;
    $memory['user_email'] = $userInfo['email'] ?? null;
    
    // Get backup information
    $stmt = $pdo->prepare("
        SELECT 
            backup_type,
            backup_url,
            file_size,
            status,
            created_at
        FROM audio_backups 
        WHERE memory_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$memoryId]);
    $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
} catch (Exception $e) {
    error_log("Memory detail error: " . $e->getMessage());
    header('Location: app.php');
    exit;
}

// Helper functions
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

function formatDuration($seconds) {
    if (!$seconds) return 'Unknown';
    
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    
    if ($minutes > 0) {
        return sprintf('%d:%02d', $minutes, $seconds);
    } else {
        return sprintf('0:%02d', $seconds);
    }
}

function formatDate($date) {
    return date('F j, Y \a\t g:i A', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(html_entity_decode($memory['title'] ?: 'Untitled Memory', ENT_QUOTES, 'UTF-8')); ?> - MemoWindow</title>
    <link rel="stylesheet" href="includes/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/app.css?v=<?php echo time(); ?>">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 80px; /* Add top padding to account for fixed navigation */
        }
        
        .memory-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .memory-header {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .memory-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0 0 10px 0;
            line-height: 1.2;
        }
        
        .memory-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
            color: #718096;
            font-size: 0.95rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .meta-icon {
            width: 16px;
            height: 16px;
            opacity: 0.7;
        }
        
        .memory-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .memory-image {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .memory-image img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .memory-audio {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .audio-player {
            width: 100%;
            margin-bottom: 15px;
        }
        
        .audio-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .play-button {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .play-button:hover {
            background: #5a67d8;
            transform: scale(1.05);
        }
        
        .audio-info {
            flex: 1;
        }
        
        .audio-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .audio-duration {
            color: #718096;
            font-size: 0.9rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: #667eea;
            width: 0%;
            transition: width 0.1s ease;
        }
        
        .time-display {
            display: flex;
            justify-content: space-between;
            color: #718096;
            font-size: 0.85rem;
        }
        
        /* Playlist Player Styles */
        .playlist-player {
            margin-top: 20px;
        }
        
        .playlist-info {
            margin-bottom: 20px;
        }
        
        .current-track {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .track-title {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }
        
        .track-counter {
            color: #666;
            font-size: 14px;
        }
        
        .playlist-progress {
            margin-bottom: 15px;
        }
        
        .playlist-progress .progress-bar {
            width: 100%;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .playlist-progress .progress-fill {
            height: 100%;
            background: #667eea;
            width: 0%;
            transition: width 0.1s ease;
        }
        
        .playlist-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .playlist-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background: #f8f9fa;
            color: #333;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .playlist-btn:hover:not(:disabled) {
            background: #e9ecef;
            transform: translateY(-1px);
        }
        
        .playlist-btn.primary {
            background: #667eea;
            color: white;
        }
        
        .playlist-btn.primary:hover {
            background: #5a6fd8;
        }
        
        .playlist-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .playlist-tracks {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .track-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .track-item:hover {
            background: #e9ecef;
        }
        
        .track-item.active {
            background: #667eea;
            color: white;
        }
        
        .track-item.active .track-subtitle {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .track-info {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        
        .track-item .track-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .track-subtitle {
            font-size: 12px;
            color: #666;
        }
        
        .track-duration {
            font-size: 12px;
            color: #666;
            font-family: monospace;
        }
        
        .track-item.active .track-duration {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .memory-stats {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: none; /* Hidden for now */
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
            background: #f7fafc;
            border-radius: 12px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .backup-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 10px;
            background: #f7fafc;
        }
        
        .backup-info {
            flex: 1;
        }
        
        .backup-type {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .backup-meta {
            color: #718096;
            font-size: 0.85rem;
        }
        
        .backup-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .back-to-memories {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #667eea;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .back-to-memories:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .memory-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .btn-icon {
            font-size: 16px;
        }
        
        .view-btn {
            background: #4299e1;
            color: white;
        }
        
        .view-btn:hover {
            background: #3182ce;
            transform: translateY(-2px);
        }
        
        .qr-btn {
            background: #38a169;
            color: white;
        }
        
        .qr-btn:hover {
            background: #2f855a;
            transform: translateY(-2px);
        }
        
        .order-btn {
            background: #ed8936;
            color: white;
        }
        
        .order-btn:hover {
            background: #dd6b20;
            transform: translateY(-2px);
        }
        
        .voice-clone-btn {
            background: #8b5cf6;
            color: white;
        }
        
        .voice-clone-btn:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }
        
        .generate-audio-btn {
            background: #10b981;
            color: white;
        }
        
        .generate-audio-btn:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .delete-btn {
            background: #e53e3e;
            color: white;
        }
        
        .delete-btn:hover {
            background: #c53030;
            transform: translateY(-2px);
        }
        
        .custom-url-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        
        .custom-url-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        
        .url-input-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .url-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }
        
        .url-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .copy-btn, .save-btn {
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .copy-btn {
            background: #6b7280;
            color: white;
        }
        
        .copy-btn:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }
        
        .save-btn {
            background: #10b981;
            color: white;
        }
        
        .save-btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        .save-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        
        .url-help code {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .memory-content {
                grid-template-columns: 1fr;
            }
            
            .memory-title {
                font-size: 2rem;
            }
            
            .memory-meta {
                flex-direction: column;
                gap: 10px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .custom-url-content {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .url-input-group {
                flex-direction: column;
                gap: 8px;
            }
            
            .copy-btn, .save-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>
    
    <div class="memory-detail-container">
        <a href="memories.php" class="back-to-memories">
            ← Back to Memories
        </a>
        
        <div class="memory-header">
            <h1 class="memory-title"><?php echo htmlspecialchars(html_entity_decode($memory['title'] ?: 'Untitled Memory', ENT_QUOTES, 'UTF-8')); ?></h1>
            <div class="memory-meta">
                <div class="meta-item">
                    <span class="meta-icon">📁</span>
                    <span><?php echo htmlspecialchars($memory['original_name']); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">📅</span>
                    <span><?php echo formatDate($memory['created_at']); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">👤</span>
                    <span><?php echo htmlspecialchars($memory['user_name'] ?: $memory['user_email']); ?></span>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="memory-actions">
                <a href="<?php echo htmlspecialchars($memory['image_url']); ?>" target="_blank" class="action-btn view-btn">
                    <span class="btn-icon">👁️</span>
                    View Image
                </a>
                <?php if ($memory['qr_url']): ?>
                <a href="<?php echo htmlspecialchars($memory['qr_url']); ?>" target="_blank" class="action-btn qr-btn">
                    <span class="btn-icon">📱</span>
                    QR Code
                </a>
                <?php endif; ?>
                <button class="action-btn order-btn" data-memory-id="<?php echo $memory['id']; ?>" data-image-url="<?php echo htmlspecialchars($memory['image_url']); ?>" data-title="<?php echo htmlspecialchars(html_entity_decode($memory['title'] ?: 'Untitled', ENT_QUOTES, 'UTF-8')); ?>">
                    <span class="btn-icon">🛒</span>
                    Order Print
                </button>
                <?php if ($memory['audio_url']): ?>
                <button class="action-btn voice-clone-btn" data-memory-id="<?php echo $memory['id']; ?>" data-audio-url="<?php echo htmlspecialchars($memory['audio_url']); ?>" data-title="<?php echo htmlspecialchars(html_entity_decode($memory['title'] ?: 'Untitled', ENT_QUOTES, 'UTF-8')); ?>">
                    <span class="btn-icon">🎤</span>
                    Clone Voice
                </button>
                <button class="action-btn generate-audio-btn" data-memory-id="<?php echo $memory['id']; ?>" data-title="<?php echo htmlspecialchars(html_entity_decode($memory['title'] ?: 'Untitled', ENT_QUOTES, 'UTF-8')); ?>">
                    <span class="btn-icon">🎵</span>
                    Generate Audio
                </button>
                <?php endif; ?>
                <button class="action-btn delete-btn" data-memory-id="<?php echo $memory['id']; ?>" data-title="<?php echo htmlspecialchars(html_entity_decode($memory['title'] ?: 'Untitled', ENT_QUOTES, 'UTF-8')); ?>">
                    <span class="btn-icon">🗑️</span>
                    Delete Memory
                </button>
            </div>
        </div>
        
        <!-- Custom URL Section -->
        <div class="custom-url-section">
            <h3 style="margin: 0 0 16px 0; color: #2d3748; font-size: 1.25rem;">🔗 Custom URL</h3>
            <div class="custom-url-content">
                <div class="url-display">
                    <label for="currentUrl" style="display: block; margin-bottom: 8px; font-weight: 500; color: #4a5568;">Current URL:</label>
                    <div class="url-input-group">
                        <input type="text" id="currentUrl" class="url-input" 
                               value="<?php echo htmlspecialchars($memory['audio_url']); ?>" 
                               readonly>
                        <button id="copyUrlBtn" class="copy-btn" title="Copy URL">
                            <span class="btn-icon">📋</span>
                        </button>
                    </div>
                </div>
                
                <div class="url-edit">
                    <label for="customUrlInput" style="display: block; margin-bottom: 8px; font-weight: 500; color: #4a5568;">Custom URL (Standard & Premium only):</label>
                    <div class="url-input-group">
                        <input type="text" id="customUrlInput" class="url-input" 
                               placeholder="Enter your custom URL (e.g., my-memory-name)"
                               value="<?php echo htmlspecialchars($memory['custom_url']); ?>">
                        <button id="saveCustomUrlBtn" class="save-btn" title="Save Custom URL">
                            <span class="btn-icon">💾</span>
                        </button>
                    </div>
                    <div class="url-help">
                        <p style="margin: 8px 0 0 0; font-size: 0.875rem; color: #718096;">
                            Custom URLs allow you to create memorable links to your memories. 
                            <br>Examples: <code>my-wedding-day</code>, <code>https://example.com/my-memory</code>, <code>family/reunion/2024</code>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="memory-content">
            <div class="memory-image">
                <h3 style="margin-top: 0; color: #2d3748;">📸 Memory Image</h3>
                <?php if ($memory['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($memory['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars(html_entity_decode($memory['title'] ?: 'Memory image', ENT_QUOTES, 'UTF-8')); ?>"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <div style="display: none; color: #718096; padding: 40px;">
                        <p>📷 Image not available</p>
                    </div>
                <?php else: ?>
                    <div style="color: #718096; padding: 40px;">
                        <p>📷 No image available</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="memory-audio">
                <h3 style="margin-top: 0; color: #2d3748;">🎵 Audio Playlist</h3>
                <?php if ($memory['audio_url']): ?>
                    <div class="playlist-player">
                        <div class="playlist-info">
                            <div class="current-track">
                                <span class="track-title">Loading playlist...</span>
                                <span class="track-counter">1 of <?php echo 1 + count($approvedSubmissions) + count($generatedAudios); ?></span>
                            </div>
                            <div class="playlist-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                            </div>
                        </div>
                        
                        <audio id="playlistAudio" class="audio-player" preload="metadata">
                            <source src="<?php echo htmlspecialchars($memory['audio_url']); ?>" type="audio/mpeg">
                            <source src="<?php echo htmlspecialchars($memory['audio_url']); ?>" type="audio/wav">
                            Your browser does not support the audio element.
                        </audio>
                        
                        <div class="playlist-controls">
                            <button id="prevBtn" class="playlist-btn" disabled>⏮️ Previous</button>
                            <button id="playPauseBtn" class="playlist-btn primary">▶️ Play</button>
                            <button id="nextBtn" class="playlist-btn">⏭️ Next</button>
                        </div>
                        
                        <div class="playlist-tracks">
                            <div class="track-item active" data-index="0">
                                <div class="track-info">
                                    <span class="track-title"><?php echo htmlspecialchars(html_entity_decode($memory['title'] ?: 'Main Memory', ENT_QUOTES, 'UTF-8')); ?></span>
                                    <span class="track-subtitle">Original Memory</span>
                                </div>
                                <span class="track-duration"><?php echo formatDuration($memory['audio_duration']); ?></span>
                            </div>
                            
                            <?php foreach ($approvedSubmissions as $index => $submission): ?>
                                <div class="track-item" data-index="<?php echo $index + 1; ?>">
                                    <div class="track-info">
                                        <span class="track-title"><?php echo htmlspecialchars(html_entity_decode($submission['memory_title'], ENT_QUOTES, 'UTF-8')); ?></span>
                                        <span class="track-subtitle">by <?php echo htmlspecialchars($submission['submitter_email']); ?></span>
                                    </div>
                                    <span class="track-duration">--:--</span>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php foreach ($generatedAudios as $index => $generated): ?>
                                <div class="track-item" data-index="<?php echo $index + 1 + count($approvedSubmissions); ?>">
                                    <div class="track-info">
                                        <span class="track-title"><?php echo htmlspecialchars(html_entity_decode($generated['memory_title'], ENT_QUOTES, 'UTF-8')); ?></span>
                                        <span class="track-subtitle">Generated by AI</span>
                                    </div>
                                    <span class="track-duration">--:--</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="color: #718096; padding: 40px; text-align: center;">
                        <p>🎵 No audio available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="memory-stats">
            <h3 style="margin-top: 0; color: #2d3748;">📊 Memory Statistics</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo formatBytes($memory['audio_size'] ?: 0); ?></div>
                    <div class="stat-label">File Size</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo formatDuration($memory['audio_duration']); ?></div>
                    <div class="stat-label">Duration</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($backups); ?></div>
                    <div class="stat-label">Backups</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo date('M j, Y', strtotime($memory['created_at'])); ?></div>
                    <div class="stat-label">Created</div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($backups)): ?>
        <div class="backup-section">
            <h3 style="margin-top: 0; color: #2d3748;">💾 Backup Information</h3>
            <?php foreach ($backups as $backup): ?>
            <div class="backup-item">
                <div class="backup-info">
                    <div class="backup-type"><?php echo ucfirst(str_replace('_', ' ', $backup['backup_type'])); ?></div>
                    <div class="backup-meta">
                        Created: <?php echo formatDate($backup['created_at']); ?> • 
                        Size: <?php echo formatBytes($backup['file_size'] ?: 0); ?>
                    </div>
                </div>
                <span class="backup-status status-<?php echo $backup['status']; ?>">
                    <?php echo ucfirst($backup['status']); ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Include necessary JavaScript modules -->
    <script src="js/globals.js?v=<?php echo time(); ?>"></script>
    <script type="module">
        // Import and initialize the modules
        import { initMemories } from './src/memories.js';
        import { initVoiceClone } from './src/voice-clone.js';
        
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize modules
            initMemories();
            initVoiceClone();
        });
    </script>
    
    <script>
        // Playlist functionality
        class PlaylistPlayer {
            constructor() {
                this.audio = document.getElementById('playlistAudio');
                this.tracks = [];
                this.currentTrackIndex = 0;
                this.isPlaying = false;
                
                // Initialize playlist data
                this.initializePlaylist();
                this.setupEventListeners();
                this.updateUI();
            }
            
            initializePlaylist() {
                // Add main memory as first track
                this.tracks.push({
                    title: <?php echo json_encode(html_entity_decode($memory['title'] ?: 'Main Memory', ENT_QUOTES, 'UTF-8')); ?>,
                    subtitle: 'Original Memory',
                    url: <?php echo json_encode($memory['audio_url']); ?>
                });
                
                // Add approved submissions
                <?php foreach ($approvedSubmissions as $submission): ?>
                this.tracks.push({
                    title: <?php echo json_encode(html_entity_decode($submission['memory_title'], ENT_QUOTES, 'UTF-8')); ?>,
                    subtitle: 'by ' + <?php echo json_encode($submission['submitter_email']); ?>,
                    url: <?php echo json_encode($submission['audio_url']); ?>
                });
                <?php endforeach; ?>
                
                // Add generated audio
                <?php foreach ($generatedAudios as $generated): ?>
                this.tracks.push({
                    title: <?php echo json_encode(html_entity_decode($generated['memory_title'], ENT_QUOTES, 'UTF-8')); ?>,
                    subtitle: 'Generated by AI',
                    url: <?php echo json_encode($generated['audio_url']); ?>
                });
                <?php endforeach; ?>
            }
            
            setupEventListeners() {
                // Audio events
                this.audio.addEventListener('ended', () => this.nextTrack());
                this.audio.addEventListener('timeupdate', () => this.updateProgress());
                this.audio.addEventListener('loadedmetadata', () => this.updateDuration());
                
                // Button events
                document.getElementById('playPauseBtn').addEventListener('click', () => this.togglePlayPause());
                document.getElementById('prevBtn').addEventListener('click', () => this.previousTrack());
                document.getElementById('nextBtn').addEventListener('click', () => this.nextTrack());
                
                // Track click events
                document.querySelectorAll('.track-item').forEach((item, index) => {
                    item.addEventListener('click', () => this.playTrack(index));
                });
            }
            
            playTrack(index) {
                if (index >= 0 && index < this.tracks.length) {
                    this.currentTrackIndex = index;
                    this.loadTrack();
                    this.updateUI();
                }
            }
            
            loadTrack() {
                const track = this.tracks[this.currentTrackIndex];
                this.audio.src = track.url;
                this.audio.load();
            }
            
            togglePlayPause() {
                if (this.isPlaying) {
                    this.audio.pause();
                    this.isPlaying = false;
                } else {
                    this.audio.play();
                    this.isPlaying = true;
                }
                this.updatePlayPauseButton();
            }
            
            nextTrack() {
                if (this.currentTrackIndex < this.tracks.length - 1) {
                    this.currentTrackIndex++;
                    this.loadTrack();
                    this.updateUI();
                    if (this.isPlaying) {
                        this.audio.play();
                    }
                }
            }
            
            previousTrack() {
                if (this.currentTrackIndex > 0) {
                    this.currentTrackIndex--;
                    this.loadTrack();
                    this.updateUI();
                    if (this.isPlaying) {
                        this.audio.play();
                    }
                }
            }
            
            updateUI() {
                const track = this.tracks[this.currentTrackIndex];
                
                // Update current track info
                document.querySelector('.current-track .track-title').textContent = track.title;
                document.querySelector('.track-counter').textContent = `${this.currentTrackIndex + 1} of ${this.tracks.length}`;
                
                // Update track list
                document.querySelectorAll('.track-item').forEach((item, index) => {
                    item.classList.toggle('active', index === this.currentTrackIndex);
                });
                
                // Update buttons
                document.getElementById('prevBtn').disabled = this.currentTrackIndex === 0;
                document.getElementById('nextBtn').disabled = this.currentTrackIndex === this.tracks.length - 1;
                
                this.updatePlayPauseButton();
            }
            
            updatePlayPauseButton() {
                const btn = document.getElementById('playPauseBtn');
                btn.textContent = this.isPlaying ? '⏸️ Pause' : '▶️ Play';
            }
            
            updateProgress() {
                if (this.audio.duration && isFinite(this.audio.duration) && this.audio.duration > 0) {
                    const progress = (this.audio.currentTime / this.audio.duration) * 100;
                    document.querySelector('.playlist-progress .progress-fill').style.width = progress + '%';
                }
            }
            
            updateDuration() {
                const trackItem = document.querySelector(`.track-item[data-index="${this.currentTrackIndex}"]`);
                if (trackItem && this.audio.duration && isFinite(this.audio.duration)) {
                    const duration = this.formatTime(this.audio.duration);
                    trackItem.querySelector('.track-duration').textContent = duration;
                } else if (trackItem) {
                    trackItem.querySelector('.track-duration').textContent = '--:--';
                }
            }
            
            formatTime(seconds) {
                if (isNaN(seconds) || !isFinite(seconds) || seconds < 0) {
                    return '0:00';
                }
                const mins = Math.floor(seconds / 60);
                const secs = Math.floor(seconds % 60);
                return `${mins}:${secs.toString().padStart(2, '0')}`;
            }
        }
        
        // Initialize playlist when page loads
        document.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('playlistAudio')) {
                new PlaylistPlayer();
            }
        });
        
        function formatTime(seconds) {
            if (isNaN(seconds) || !isFinite(seconds) || seconds < 0) {
                return '0:00';
            }
            
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        }
        
        // Order functionality
        document.addEventListener('click', function(e) {
            if (e.target.closest('.order-btn')) {
                const btn = e.target.closest('.order-btn');
                const memoryId = btn.dataset.memoryId;
                const imageUrl = btn.dataset.imageUrl;
                const title = btn.dataset.title;
                
                if (typeof window.showOrderOptions === 'function') {
                    window.showOrderOptions(memoryId, imageUrl, title, btn);
                } else {
                    alert('Order functionality not available. Please refresh the page.');
                }
            }
        });
        
        // Voice clone functionality
        document.addEventListener('click', function(e) {
            if (e.target.closest('.voice-clone-btn')) {
                const btn = e.target.closest('.voice-clone-btn');
                const memoryId = btn.dataset.memoryId;
                const audioUrl = btn.dataset.audioUrl;
                const title = btn.dataset.title;
                
                if (typeof window.checkVoiceCloneStatus === 'function') {
                    window.checkVoiceCloneStatus(memoryId, audioUrl, title);
                } else {
                    alert('Voice clone functionality not available. Please refresh the page.');
                }
            }
        });
        
        // Generate audio functionality
        document.addEventListener('click', function(e) {
            if (e.target.closest('.generate-audio-btn')) {
                const btn = e.target.closest('.generate-audio-btn');
                const memoryId = btn.dataset.memoryId;
                const title = btn.dataset.title;
                
                if (typeof window.showGenerateAudioModal === 'function') {
                    window.showGenerateAudioModal(memoryId, title);
                } else {
                    alert('Generate audio functionality not available. Please refresh the page.');
                }
            }
        });
        
        // Delete functionality
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-btn')) {
                const btn = e.target.closest('.delete-btn');
                const memoryId = btn.dataset.memoryId;
                const title = btn.dataset.title;
                
                if (confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) {
                    deleteMemory(memoryId);
                }
            }
        });
        
        // Delete memory function
        async function deleteMemory(memoryId) {
            try {
                const response = await fetch('delete_memory.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `memory_id=${memoryId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Memory deleted successfully!');
                    window.location.href = 'memories.php';
                } else {
                    alert('Error deleting memory: ' + result.error);
                }
            } catch (error) {
                alert('Error deleting memory: ' + error.message);
            }
        }
        
        // Custom URL functionality
        document.addEventListener('DOMContentLoaded', function() {
            const copyUrlBtn = document.getElementById('copyUrlBtn');
            const saveCustomUrlBtn = document.getElementById('saveCustomUrlBtn');
            const customUrlInput = document.getElementById('customUrlInput');
            const currentUrlInput = document.getElementById('currentUrl');
            
            // Copy URL functionality
            if (copyUrlBtn) {
                copyUrlBtn.addEventListener('click', function() {
                    const url = currentUrlInput.value;
                    navigator.clipboard.writeText(url).then(function() {
                        // Show success feedback
                        const originalText = copyUrlBtn.innerHTML;
                        copyUrlBtn.innerHTML = '<span class="btn-icon">✅</span>';
                        copyUrlBtn.style.background = '#10b981';
                        
                        setTimeout(function() {
                            copyUrlBtn.innerHTML = originalText;
                            copyUrlBtn.style.background = '#6b7280';
                        }, 2000);
                    }).catch(function(err) {
                        alert('Failed to copy URL: ' + err);
                    });
                });
            }
            
            // Save custom URL functionality
            if (saveCustomUrlBtn) {
                saveCustomUrlBtn.addEventListener('click', async function() {
                    const customUrl = customUrlInput.value.trim();
                    const memoryId = <?php echo $memory['id']; ?>;
                    
                    if (!customUrl) {
                        alert('Please enter a custom URL');
                        return;
                    }
                    
                    // No validation - accept any custom URL
                    
                    // Show loading state
                    const originalText = saveCustomUrlBtn.innerHTML;
                    saveCustomUrlBtn.innerHTML = '<span class="btn-icon">⏳</span>';
                    saveCustomUrlBtn.disabled = true;
                    
                    try {
                        const response = await fetch('save_custom_url.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `memory_id=${memoryId}&custom_url=${encodeURIComponent(customUrl)}`
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // Update current URL display
                            currentUrlInput.value = result.new_url;
                            
                            // Show success feedback
                            saveCustomUrlBtn.innerHTML = '<span class="btn-icon">✅</span>';
                            saveCustomUrlBtn.style.background = '#10b981';
                            
                            setTimeout(function() {
                                saveCustomUrlBtn.innerHTML = originalText;
                                saveCustomUrlBtn.style.background = '#10b981';
                                saveCustomUrlBtn.disabled = false;
                            }, 2000);
                        } else {
                            throw new Error(result.error || 'Failed to save custom URL');
                        }
                    } catch (error) {
                        alert('Error saving custom URL: ' + error.message);
                        
                        // Reset button state
                        saveCustomUrlBtn.innerHTML = originalText;
                        saveCustomUrlBtn.disabled = false;
                    }
                });
            }
        });
    </script>
</body>
</html>
