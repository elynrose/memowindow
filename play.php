<?php
/**
 * Play Page - Public memory playback
 * Allows users to play memories using a unique ID without requiring login
 */

require_once 'config.php';

// Get the unique ID from URL parameter
$uniqueId = $_GET['uid'] ?? '';

if (empty($uniqueId)) {
    http_response_code(404);
    die('Memory not found');
}

// Get memory details from database using unique ID
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $stmt = $pdo->prepare("
        SELECT wa.*, u.email as user_email, u.display_name as user_name
        FROM wave_assets wa
        LEFT JOIN users u ON wa.user_id = u.firebase_uid
        WHERE wa.unique_id = ? AND wa.is_public = 1
    ");
    $stmt->execute([$uniqueId]);
    $memory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$memory) {
        http_response_code(404);
        die('Memory not found or not publicly accessible');
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
                         alt="<?php echo htmlspecialchars($memory['title'] ?: 'Memory'); ?>" 
                         class="memory-image">
                    
                    <h2 class="memory-title"><?php echo htmlspecialchars($memory['title'] ?: 'Untitled Memory'); ?></h2>
                    
                    <div class="memory-meta">
                        Created by <?php echo htmlspecialchars($memory['user_name'] ?: $memory['user_email'] ?: 'Anonymous'); ?>
                        <?php if ($memory['created_at']): ?>
                            • <?php echo date('F j, Y', strtotime($memory['created_at'])); ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($memory['audio_url']): ?>
                        <div class="audio-player">
                            <audio controls preload="metadata">
                                <source src="<?php echo htmlspecialchars($memory['audio_url']); ?>" type="audio/mpeg">
                                <source src="<?php echo htmlspecialchars($memory['audio_url']); ?>" type="audio/wav">
                                Your browser does not support the audio element.
                            </audio>
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
            const title = encodeURIComponent('<?php echo addslashes($memory['title'] ?: 'Check out this memory'); ?>');
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${title}`, '_blank', 'width=600,height=400');
        }
        
        function shareOnTwitter() {
            const url = encodeURIComponent(document.getElementById('shareUrl').value);
            const title = encodeURIComponent('<?php echo addslashes($memory['title'] ?: 'Check out this memory'); ?>');
            window.open(`https://twitter.com/intent/tweet?url=${url}&text=${title}`, '_blank', 'width=600,height=400');
        }
        
        function shareOnWhatsApp() {
            const url = encodeURIComponent(document.getElementById('shareUrl').value);
            const title = encodeURIComponent('<?php echo addslashes($memory['title'] ?: 'Check out this memory'); ?>');
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
