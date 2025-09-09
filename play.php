<?php
// play.php - Audio player page for QR code links
header('Content-Type: text/html; charset=UTF-8');

// Database configuration
// Load configuration
require_once 'config.php';

$dbHost = DB_HOST;
$dbName = DB_NAME;
$dbUser = DB_USER;
$dbPass = DB_PASS;
$table = 'wave_assets';

// Get the memory identifier from URL parameter
$memoryId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$uniqueId = isset($_GET['uid']) ? $_GET['uid'] : '';

if (!$memoryId && !$uniqueId) {
    http_response_code(404);
    echo "Memory not found";
    exit;
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Get memory details by ID or unique_id
    if ($memoryId) {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                title,
                original_name,
                image_url,
                audio_url,
                created_at
            FROM `$table` 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $memoryId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                title,
                original_name,
                image_url,
                audio_url,
                created_at
            FROM `$table` 
            WHERE unique_id = :unique_id
        ");
        $stmt->execute([':unique_id' => $uniqueId]);
    }
    $memory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$memory) {
        http_response_code(404);
        echo "Memory not found";
        exit;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($memory['title'] ?: 'MemoWindow'); ?> - MemoWindow</title>
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .player-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            color: #0b0d12;
        }
        .memory-title {
            font-size: 28px;
            font-weight: 600;
            margin: 0 0 8px 0;
            color: #0b0d12;
        }
        .memory-subtitle {
            font-size: 16px;
            color: #6b7280;
            margin: 0 0 32px 0;
        }
        .waveform-preview {
            width: 100%;
            height: 120px;
            background: #f8fafc;
            border-radius: 16px;
            margin: 0 0 32px 0;
            background-image: url('<?php echo htmlspecialchars($memory['image_url']); ?>');
            background-size: cover;
            background-position: center;
            border: 2px solid #e5e7eb;
        }
        .audio-player {
            width: 100%;
            margin: 0 0 24px 0;
        }
        .audio-player audio {
            width: 100%;
            height: 60px;
            border-radius: 12px;
        }
        .play-button {
            background: #2a4df5;
            border: none;
            color: white;
            padding: 16px 32px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 0 auto 24px auto;
            transition: all 0.2s ease;
            min-width: 200px;
        }
        .play-button:hover {
            background: #1e3a8a;
            transform: translateY(-2px);
        }
        .play-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        .memory-info {
            font-size: 14px;
            color: #6b7280;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        .logo {
            font-size: 20px;
            font-weight: 600;
            color: #2a4df5;
            margin-top: 32px;
        }
        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 16px;
            border-radius: 12px;
            margin: 16px 0;
        }
        @media (max-width: 640px) {
            .player-card {
                padding: 24px;
                margin: 10px;
            }
            .memory-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="player-card">
        <h1 class="memory-title"><?php echo htmlspecialchars($memory['title'] ?: 'Untitled Memory'); ?></h1>
        <p class="memory-subtitle"><?php echo htmlspecialchars($memory['original_name']); ?></p>
        
        <div class="waveform-preview"></div>
        
        <?php if (isset($memory['audio_url']) && $memory['audio_url']): ?>
            <div class="audio-player">
                <audio id="audioPlayer" controls preload="metadata">
                    <source src="<?php echo htmlspecialchars($memory['audio_url']); ?>" type="audio/mpeg">
                    <source src="<?php echo htmlspecialchars($memory['audio_url']); ?>" type="audio/wav">
                    <source src="<?php echo htmlspecialchars($memory['audio_url']); ?>" type="audio/mp4">
                    Your browser does not support the audio element.
                </audio>
            </div>
            
            <button class="play-button" id="playButton" onclick="togglePlay()">
                <svg id="playIcon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8,5.14V19.14L19,12.14L8,5.14Z" />
                </svg>
                <svg id="pauseIcon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                    <path d="M14,19H18V5H14M6,19H10V5H6V19Z" />
                </svg>
                <span id="playText">Play Memory</span>
            </button>
        <?php else: ?>
            <div class="error-message">
                Audio file not available for this memory.
            </div>
        <?php endif; ?>
        
        <div class="memory-info">
            Created: <?php echo date('F j, Y \a\t g:i A', strtotime($memory['created_at'])); ?><br>
            Original file: <?php echo htmlspecialchars($memory['original_name']); ?>
        </div>
        
        <div class="logo">MemoWindow</div>
    </div>

    <script>
        const audio = document.getElementById('audioPlayer');
        const playButton = document.getElementById('playButton');
        const playIcon = document.getElementById('playIcon');
        const pauseIcon = document.getElementById('pauseIcon');
        const playText = document.getElementById('playText');

        function togglePlay() {
            if (audio.paused) {
                audio.play();
            } else {
                audio.pause();
            }
        }

        audio.addEventListener('play', () => {
            playIcon.style.display = 'none';
            pauseIcon.style.display = 'block';
            playText.textContent = 'Pause';
        });

        audio.addEventListener('pause', () => {
            playIcon.style.display = 'block';
            pauseIcon.style.display = 'none';
            playText.textContent = 'Play Memory';
        });

        audio.addEventListener('ended', () => {
            playIcon.style.display = 'block';
            pauseIcon.style.display = 'none';
            playText.textContent = 'Play Again';
        });

        // Auto-play on mobile with user interaction
        playButton.addEventListener('click', () => {
            if (audio.paused) {
                audio.play().catch(e => {
                    console.log('Autoplay prevented:', e);
                });
            }
        });
    </script>
</body>
</html>
