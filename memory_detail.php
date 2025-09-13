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
    
    // Get memory statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_memories,
            SUM(audio_size) as total_size,
            AVG(audio_duration) as avg_duration
        FROM wave_assets 
        WHERE user_id = ?
    ");
    $stmt->execute([$currentUser['uid']]);
    $userStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
    <title><?php echo htmlspecialchars($memory['title'] ?: 'Untitled Memory'); ?> - MemoWindow</title>
    <link rel="stylesheet" href="includes/navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/app.css?v=<?php echo time(); ?>">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
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
        
        .memory-stats {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
            <h1 class="memory-title"><?php echo htmlspecialchars($memory['title'] ?: 'Untitled Memory'); ?></h1>
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
                <button class="action-btn order-btn" data-memory-id="<?php echo $memory['id']; ?>" data-image-url="<?php echo htmlspecialchars($memory['image_url']); ?>" data-title="<?php echo htmlspecialchars($memory['title'] ?: 'Untitled'); ?>">
                    <span class="btn-icon">🛒</span>
                    Order Print
                </button>
                <?php if ($memory['audio_url']): ?>
                <button class="action-btn voice-clone-btn" data-memory-id="<?php echo $memory['id']; ?>" data-audio-url="<?php echo htmlspecialchars($memory['audio_url']); ?>" data-title="<?php echo htmlspecialchars($memory['title'] ?: 'Untitled'); ?>">
                    <span class="btn-icon">🎤</span>
                    Clone Voice
                </button>
                <button class="action-btn generate-audio-btn" data-memory-id="<?php echo $memory['id']; ?>" data-title="<?php echo htmlspecialchars($memory['title'] ?: 'Untitled'); ?>">
                    <span class="btn-icon">🎵</span>
                    Generate Audio
                </button>
                <?php endif; ?>
                <button class="action-btn delete-btn" data-memory-id="<?php echo $memory['id']; ?>" data-title="<?php echo htmlspecialchars($memory['title'] ?: 'Untitled'); ?>">
                    <span class="btn-icon">🗑️</span>
                    Delete Memory
                </button>
            </div>
        </div>
        
        <div class="memory-content">
            <div class="memory-image">
                <h3 style="margin-top: 0; color: #2d3748;">📸 Memory Image</h3>
                <?php if ($memory['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($memory['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($memory['title'] ?: 'Memory image'); ?>"
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
                <h3 style="margin-top: 0; color: #2d3748;">🎵 Audio Recording</h3>
                <?php if ($memory['audio_url']): ?>
                    <audio id="audioPlayer" class="audio-player" preload="metadata">
                        <source src="<?php echo htmlspecialchars($memory['audio_url']); ?>" type="audio/mpeg">
                        <source src="<?php echo htmlspecialchars($memory['audio_url']); ?>" type="audio/wav">
                        Your browser does not support the audio element.
                    </audio>
                    
                    <div class="audio-controls">
                        <button id="playButton" class="play-button">▶️</button>
                        <div class="audio-info">
                            <div class="audio-title"><?php echo htmlspecialchars($memory['original_name']); ?></div>
                            <div class="audio-duration"><?php echo formatDuration($memory['audio_duration']); ?></div>
                        </div>
                    </div>
                    
                    <div class="progress-bar">
                        <div id="progressFill" class="progress-fill"></div>
                    </div>
                    
                    <div class="time-display">
                        <span id="currentTime">0:00</span>
                        <span id="totalTime"><?php echo formatDuration($memory['audio_duration']); ?></span>
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
                    <div class="stat-number"><?php echo $userStats['total_memories']; ?></div>
                    <div class="stat-label">Total Memories</div>
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
        // Audio player functionality
        const audio = document.getElementById('audioPlayer');
        const playButton = document.getElementById('playButton');
        const progressFill = document.getElementById('progressFill');
        const currentTimeSpan = document.getElementById('currentTime');
        const totalTimeSpan = document.getElementById('totalTime');
        
        if (audio) {
            let isPlaying = false;
            
            playButton.addEventListener('click', () => {
                if (isPlaying) {
                    audio.pause();
                    playButton.textContent = '▶️';
                    isPlaying = false;
                } else {
                    audio.play();
                    playButton.textContent = '⏸️';
                    isPlaying = true;
                }
            });
            
            audio.addEventListener('timeupdate', () => {
                const progress = (audio.currentTime / audio.duration) * 100;
                progressFill.style.width = progress + '%';
                currentTimeSpan.textContent = formatTime(audio.currentTime);
            });
            
            audio.addEventListener('ended', () => {
                playButton.textContent = '▶️';
                isPlaying = false;
                progressFill.style.width = '0%';
                currentTimeSpan.textContent = '0:00';
            });
            
            audio.addEventListener('loadedmetadata', () => {
                totalTimeSpan.textContent = formatTime(audio.duration);
            });
        }
        
        function formatTime(seconds) {
            if (isNaN(seconds)) return '0:00';
            
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
    </script>
</body>
</html>
