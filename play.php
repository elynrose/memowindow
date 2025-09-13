<?php
/**
 * Play Page - Public memory playback
 * Allows users to play memories using a unique ID without requiring login
 */

require_once 'config.php';

// Get the unique ID or custom URL from URL parameter or path
$uniqueId = $_GET['uid'] ?? '';
$customUrl = $_GET['custom_url'] ?? '';

// If no custom_url from GET, check if this is a custom URL request (e.g., /play.php/my-custom-url)
if (empty($customUrl) && isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    $pathInfo = parse_url($requestUri, PHP_URL_PATH);
    $pathParts = explode('/', trim($pathInfo, '/'));
    
    // If the path has more than just 'play.php', treat it as a custom URL
    if (count($pathParts) > 1 && $pathParts[0] === 'play.php') {
        $customUrl = $pathParts[1];
    }
}

if (empty($uniqueId) && empty($customUrl)) {
    http_response_code(404);
    die('Memory not found');
}

// Get memory details from database using unique ID or custom URL
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    if (!empty($customUrl)) {
        // Look up by custom URL
        $stmt = $pdo->prepare("
            SELECT wa.*
            FROM wave_assets wa
            WHERE wa.custom_url = ? AND wa.is_public = 1
        ");
        $stmt->execute([$customUrl]);
    } else {
        // Look up by unique ID
        $stmt = $pdo->prepare("
            SELECT wa.*
            FROM wave_assets wa
            WHERE wa.unique_id = ? AND wa.is_public = 1
        ");
        $stmt->execute([$uniqueId]);
    }
    
    $memory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$memory) {
        http_response_code(404);
        die('Memory not found or not publicly accessible');
    }
    
    // Get user information separately to avoid collation issues
    $userInfo = null;
    if ($memory['user_id']) {
        try {
            $stmt = $pdo->prepare("SELECT email, display_name FROM users WHERE firebase_uid = ?");
            $stmt->execute([$memory['user_id']]);
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If user lookup fails, continue without user info
            $userInfo = null;
        }
    }
    
    // Add user info to memory array
    if ($userInfo) {
        $memory['user_email'] = $userInfo['email'];
        $memory['user_name'] = $userInfo['display_name'];
    }
    
    // Fetch approved submissions for this memory (if it's associated with an invitation)
    $approvedSubmissions = [];
    try {
        // Check if this memory is associated with any invitations
        $stmt = $pdo->prepare("
            SELECT ei.id as invitation_id, ei.invitation_title, ei.owner_user_id
            FROM email_invitations ei
            WHERE ei.owner_user_id = ? AND ei.status = 'pending'
        ");
        $stmt->execute([$memory['user_id']]);
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
            $stmt->execute([$memory['id']]);
            $generatedAudios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If there's an error fetching generated audio, continue without them
            $generatedAudios = [];
        }
    } catch (PDOException $e) {
        // If there's an error fetching submissions, continue without them
        $approvedSubmissions = [];
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    die('Database error');
}

// Set page title
$pageTitle = $memory['title'] ?: 'Memory Playback';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - MemoWindow</title>
    <link rel="stylesheet" href="includes/unified.css?v=<?php echo time(); ?>">
    <style>
        .play-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .play-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            text-align: center;
            color: white;
        }
        
        .play-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .memory-player {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .memory-image {
            width: 100%;
            max-width: 400px;
            height: auto;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .memory-title {
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .memory-meta {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .audio-player {
            width: 100%;
            margin-bottom: 30px;
        }
        
        .audio-player audio {
            width: 100%;
            height: 50px;
            border-radius: 25px;
        }
        
        .play-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #e9ecef;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
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
        
        .progress-bar {
            width: 100%;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .progress-fill {
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
        
        .share-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
        }
        
        .share-section h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .share-input {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .share-input input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .copy-btn {
            padding: 10px 15px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .copy-btn:hover {
            background: #5a6268;
        }
        
        .social-share {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .social-btn {
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .social-btn.facebook {
            background: #1877f2;
            color: white;
        }
        
        .social-btn.twitter {
            background: #1da1f2;
            color: white;
        }
        
        .social-btn.whatsapp {
            background: #25d366;
            color: white;
        }
        
        .footer-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #666;
            font-size: 14px;
        }
        
        .footer-info a {
            color: #667eea;
            text-decoration: none;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            margin: 20px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .memory-player {
                padding: 20px;
                margin: 20px;
            }
            
            .memory-title {
                font-size: 24px;
            }
            
            .play-actions {
                flex-direction: column;
            }
            
            .share-input {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="play-container">
        <div class="play-header">
            <h1>🎵 MemoWindow</h1>
            <p>Share your beautiful waveform memories</p>
        </div>
        
        <div class="play-content">
            <div class="memory-player">
                <?php if ($memory): ?>
                    <img src="<?php echo htmlspecialchars($memory['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars(html_entity_decode($memory['title'] ?: 'Memory', ENT_QUOTES, 'UTF-8')); ?>" 
                         class="memory-image">
                    
                    <h2 class="memory-title"><?php echo htmlspecialchars(html_entity_decode($memory['title'] ?: 'Untitled Memory', ENT_QUOTES, 'UTF-8')); ?></h2>
                    
                    <div class="memory-meta">
                        Created by <?php echo htmlspecialchars($memory['user_name'] ?: $memory['user_email'] ?: 'Anonymous'); ?>
                        <?php if ($memory['created_at']): ?>
                            • <?php echo date('F j, Y', strtotime($memory['created_at'])); ?>
                        <?php endif; ?>
                    </div>
                    
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
                            
                            <audio id="playlistAudio" controls preload="metadata">
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
                                    <span class="track-duration">--:--</span>
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
                    <?php endif; ?>
                    
                    <div class="play-actions">
                        <a href="<?php echo htmlspecialchars($memory['image_url']); ?>" 
                           target="_blank" class="btn btn-primary">
                            📷 View Full Image
                        </a>
                        
                        <?php if ($memory['audio_url']): ?>
                            <a href="<?php echo htmlspecialchars($memory['audio_url']); ?>" 
                               download class="btn btn-success">
                                💾 Download Audio
                            </a>
                        <?php endif; ?>
                        
                        <a href="index.php" class="btn btn-secondary">
                            🏠 Create Your Own
                        </a>
                    </div>
                    
                    
                    <div class="share-section">
                        <h3>Share this memory</h3>
                        
                        <div class="share-input">
                            <input type="text" id="shareUrl" 
                                   value="<?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                                   readonly>
                            <button class="copy-btn" onclick="copyToClipboard()">Copy</button>
                        </div>
                        
                        <div class="social-share">
                            <a href="#" class="social-btn facebook" onclick="shareOnFacebook()">
                                📘 Facebook
                            </a>
                            <a href="#" class="social-btn twitter" onclick="shareOnTwitter()">
                                🐦 Twitter
                            </a>
                            <a href="#" class="social-btn whatsapp" onclick="shareOnWhatsApp()">
                                💬 WhatsApp
                            </a>
                        </div>
                    </div>
                    
                    <div class="footer-info">
                        <p>This memory was created with <a href="index.php">MemoWindow</a></p>
                        <p>Transform your voice into beautiful waveform art</p>
                    </div>
                    
                <?php else: ?>
                    <div class="error-message">
                        <h3>Memory Not Found</h3>
                        <p>The memory you're looking for doesn't exist or is not publicly accessible.</p>
                        <a href="index.php" class="btn btn-primary">Create Your Own Memory</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
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
                    document.querySelector('.progress-fill').style.width = progress + '%';
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
        
        // Copy URL to clipboard
        function copyToClipboard() {
            const shareUrl = document.getElementById('shareUrl');
            shareUrl.select();
            shareUrl.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                document.execCommand('copy');
                showToast('URL copied to clipboard!', 'success');
            } catch (err) {
                // Fallback for modern browsers
                navigator.clipboard.writeText(shareUrl.value).then(() => {
                    showToast('URL copied to clipboard!', 'success');
                }).catch(() => {
                    showToast('Failed to copy URL', 'error');
                });
            }
        }
        
        // Social sharing functions
        function shareOnFacebook() {
            const url = encodeURIComponent(document.getElementById('shareUrl').value);
            const title = encodeURIComponent(<?php echo json_encode(html_entity_decode($memory['title'] ?: 'Check out this memory', ENT_QUOTES, 'UTF-8')); ?>);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${title}`, '_blank', 'width=600,height=400');
        }
        
        function shareOnTwitter() {
            const url = encodeURIComponent(document.getElementById('shareUrl').value);
            const title = encodeURIComponent(<?php echo json_encode(html_entity_decode($memory['title'] ?: 'Check out this memory', ENT_QUOTES, 'UTF-8')); ?>);
            window.open(`https://twitter.com/intent/tweet?url=${url}&text=${title}`, '_blank', 'width=600,height=400');
        }
        
        function shareOnWhatsApp() {
            const url = encodeURIComponent(document.getElementById('shareUrl').value);
            const title = encodeURIComponent(<?php echo json_encode(html_entity_decode($memory['title'] ?: 'Check out this memory', ENT_QUOTES, 'UTF-8')); ?>);
            window.open(`https://wa.me/?text=${title}%20${url}`, '_blank');
        }
        
        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                font-weight: 500;
                max-width: 300px;
                animation: slideIn 0.3s ease;
            `;
            toast.textContent = message;
            
            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(toast);
            
            // Remove after 3 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 3000);
        }
        
        // Auto-play audio if user prefers (optional)
        document.addEventListener('DOMContentLoaded', function() {
            const audio = document.querySelector('audio');
            if (audio) {
                // Check if user has interacted with the page before
                let hasInteracted = false;
                
                document.addEventListener('click', function() {
                    hasInteracted = true;
                }, { once: true });
                
                // Optional: Auto-play after a short delay if user has interacted
                setTimeout(() => {
                    if (hasInteracted && audio.paused) {
                        // Uncomment the next line to enable auto-play
                        // audio.play().catch(e => console.log('Auto-play prevented:', e));
                    }
                }, 1000);
            }
        });
    </script>
</body>
</html>
