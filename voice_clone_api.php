<?php
/**
 * ElevenLabs Voice Cloning API Integration
 * Handles voice cloning and text-to-speech generation
 */

// Suppress PHP warnings but allow JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Increase output buffer and memory limits for large audio data
ini_set('memory_limit', '256M');
ini_set('output_buffering', '8192');

require_once 'config.php';
require_once 'secure_auth.php';
require_once 'VoiceCloneSettings.php';

class VoiceCloneAPI {
    private $apiKey;
    private $baseUrl = 'https://api.elevenlabs.io/v1';
    
    public function __construct() {
        $this->apiKey = ELEVENLABS_API_KEY ?? null;
        if (!$this->apiKey) {
            throw new Exception('ElevenLabs API key not configured');
        }
    }
    
    /**
     * Create a voice clone from audio file
     * @param string $audioUrl URL to the audio file
     * @param string $voiceName Name for the cloned voice
     * @return array Result with voice_id or error
     */
    public function createVoiceClone($audioUrl, $voiceName) {
        try {
            // Download audio file
            $audioData = file_get_contents($audioUrl);
            if (!$audioData) {
                throw new Exception('Failed to download audio file');
            }
            
            // Create temporary file for multipart upload
            $tempDir = sys_get_temp_dir();
            if (!is_writable($tempDir)) {
                $tempDir = __DIR__ . '/temp';
                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }
            }
            
            $tempFile = tempnam($tempDir, 'voice_clone_');
            if (!$tempFile) {
                throw new Exception('Failed to create temporary file');
            }
            
            if (file_put_contents($tempFile, $audioData) === false) {
                throw new Exception('Failed to write audio data to temporary file');
            }
            
            try {
                // Use ElevenLabs Instant Voice Cloning API
                $url = $this->baseUrl . '/voices/add';
                
                $postData = [
                    'name' => $voiceName,
                    'files' => new CURLFile($tempFile, 'audio/mpeg', 'audio.mp3')
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'xi-api-key: ' . $this->apiKey
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_VERBOSE, true);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    throw new Exception('cURL error: ' . $curlError);
                }
                
                // Log the response for debugging
                error_log('ElevenLabs API Response (HTTP ' . $httpCode . '): ' . $response);
                
                if ($httpCode !== 200 && $httpCode !== 201) {
                    throw new Exception('ElevenLabs API error (HTTP ' . $httpCode . '): ' . $response);
                }
                
                if (empty($response)) {
                    throw new Exception('Empty response from ElevenLabs API');
                }
                
                $result = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON response from ElevenLabs API: ' . $response);
                }
                
                return [
                    'success' => true,
                    'voice_id' => $result['voice_id'] ?? null,
                    'voice_name' => $voiceName,
                    'voice_data' => $result
                ];
                
            } finally {
                // Clean up temporary file
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate speech from text using cloned voice
     * @param string $voiceId The cloned voice ID
     * @param string $text Text to convert to speech
     * @return array Result with audio URL or error
     */
    public function generateSpeech($voiceId, $text) {
        try {
            $url = $this->baseUrl . '/text-to-speech/' . $voiceId;
            
            $postData = [
                'text' => $text,
                'model_id' => 'eleven_multilingual_v2',
                'voice_settings' => [
                    'stability' => 0.5,
                    'similarity_boost' => 0.8,
                    'style' => 0.0,
                    'use_speaker_boost' => true
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'xi-api-key: ' . $this->apiKey
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                throw new Exception('cURL error: ' . $curlError);
            }
            
            if ($httpCode !== 200) {
                throw new Exception('ElevenLabs TTS API error (HTTP ' . $httpCode . '): ' . $response);
            }
            
            // Generate a unique ID for this audio
            $audioId = uniqid('audio_', true);
            
            // Store the audio data in local backup cache
            $cacheDir = __DIR__ . '/audio_cache';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            
            $cacheFile = $cacheDir . '/' . $audioId . '.mp3';
            $localBackupSuccess = file_put_contents($cacheFile, $response) !== false;
            
            // Return data for Firebase upload (primary storage)
            return [
                'success' => true,
                'audio_url' => 'PENDING_FIREBASE_UPLOAD', // Will be updated after Firebase upload
                'audio_id' => $audioId,
                'text' => $text,
                'voice_id' => $voiceId,
                'audio_size' => strlen($response),
                'audio_data' => base64_encode($response), // Include base64 data for Firebase upload
                'needs_firebase_upload' => true, // Flag to indicate frontend should upload to Firebase
                'local_backup_success' => $localBackupSuccess, // Track if local backup succeeded
                'local_backup_url' => $localBackupSuccess ? 'audio_cache/' . $audioId . '.mp3' : null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * List available voice clones for a user
     * @param string $userId User ID
     * @return array List of voice clones
     */
    public function getUserVoiceClones($userId) {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            
            $stmt = $pdo->prepare("
                SELECT vc.*, wa.memory_title, wa.audio_url 
                FROM voice_clones vc 
                LEFT JOIN wave_assets wa ON vc.source_memory_id = wa.id 
                WHERE vc.user_id = ? 
                ORDER BY vc.created_at DESC
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// API endpoints
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    // Get user ID from session
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    $userId = getCurrentUser()['user_id'];
    
    try {
        $voiceAPI = new VoiceCloneAPI();
        $settings = new VoiceCloneSettings();
        
        switch ($action) {
            case 'create_clone':
                $memoryId = intval($_POST['memory_id'] ?? 0);
                $voiceName = $_POST['voice_name'] ?? '';
                $audioUrl = $_POST['audio_url'] ?? '';
                
                if (!$memoryId || !$voiceName || !$audioUrl) {
                    throw new Exception('Missing required parameters');
                }
                
                // Check if user can create a voice clone
                $canCreate = $settings->canCreateClone($userId, true); // TODO: Check actual subscription status
                if (!$canCreate['allowed']) {
                    throw new Exception($canCreate['reason']);
                }
                
                // Create voice clone
                $result = $voiceAPI->createVoiceClone($audioUrl, $voiceName);
                
                if ($result['success']) {
                    // Save to database
                    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ]);
                    
                    // Check if the memory exists first
                    $checkStmt = $pdo->prepare("SELECT id FROM wave_assets WHERE id = ? AND user_id = ?");
                    $checkStmt->execute([$memoryId, $userId]);
                    $memoryExists = $checkStmt->fetch();
                    
                    if ($memoryExists) {
                        $stmt = $pdo->prepare("
                            INSERT INTO voice_clones (user_id, source_memory_id, voice_id, voice_name, created_at) 
                            VALUES (?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$userId, $memoryId, $result['voice_id'], $voiceName]);
                        $result['clone_id'] = $pdo->lastInsertId();
                    } else {
                        // Memory doesn't exist, save without the foreign key reference
                        $stmt = $pdo->prepare("
                            INSERT INTO voice_clones (user_id, source_memory_id, voice_id, voice_name, created_at) 
                            VALUES (?, NULL, ?, ?, NOW())
                        ");
                        $stmt->execute([$userId, $result['voice_id'], $voiceName]);
                        $result['clone_id'] = $pdo->lastInsertId();
                        $result['warning'] = 'Memory reference not found, voice clone saved without source reference';
                    }
                    
                    // Increment usage count
                    $settings->incrementUsage($userId);
                    $result['usage_info'] = [
                        'current_usage' => $settings->getUserUsage($userId),
                        'monthly_limit' => $settings->getMonthlyLimit()
                    ];
                }
                
                echo json_encode($result);
                break;
                
            case 'check_status':
                // Check user's voice clone status and limits
                $canCreate = $settings->canCreateClone($userId, true); // TODO: Check actual subscription status
                $usage = $settings->getUserUsage($userId);
                $limit = $settings->getMonthlyLimit();
                
                echo json_encode([
                    'success' => true,
                    'enabled' => $settings->isEnabled(),
                    'can_create' => $canCreate['allowed'],
                    'reason' => $canCreate['reason'] ?? null,
                    'usage' => $usage,
                    'limit' => $limit,
                    'requires_subscription' => $settings->requiresSubscription()
                ]);
                break;
                
            case 'get_user_voices':
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                
                $stmt = $pdo->prepare("
                    SELECT vc.id, vc.voice_name, vc.voice_id, vc.created_at, 
                           wa.title as memory_title, wa.id as memory_id
                    FROM voice_clones vc
                    LEFT JOIN wave_assets wa ON vc.source_memory_id = wa.id
                    WHERE vc.user_id = ?
                    ORDER BY vc.created_at DESC
                ");
                $stmt->execute([$userId]);
                $voices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'voices' => $voices
                ]);
                break;
                
            case 'generate_speech':
                $voiceId = $_POST['voice_id'] ?? '';
                $text = $_POST['text'] ?? '';
                $memoryId = $_POST['memory_id'] ?? '';
                
                if (!$voiceId || !$text) {
                    throw new Exception('Missing voice_id or text');
                }
                
                $result = $voiceAPI->generateSpeech($voiceId, $text);
                
                // If generation was successful and we have a memory_id, save the audio
                if ($result['success'] && $memoryId) {
                    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ]);
                    // Get the voice clone ID from the voice_clones table
                    $stmt = $pdo->prepare("SELECT id FROM voice_clones WHERE user_id = ? AND voice_id = ?");
                    $stmt->execute([$userId, $voiceId]);
                    $voiceClone = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($voiceClone) {
                        // Get memory title
                        $stmt = $pdo->prepare("SELECT title FROM wave_assets WHERE id = ?");
                        $stmt->execute([$memoryId]);
                        $memory = $stmt->fetch(PDO::FETCH_ASSOC);
                        $memoryTitle = $memory ? $memory['title'] : 'Untitled';
                        
                        // Save generated audio to database
                        $stmt = $pdo->prepare("
                            INSERT INTO generated_audio (user_id, memory_id, voice_clone_id, text_content, audio_url, memory_title, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$userId, $memoryId, $voiceClone['id'], $text, $result['audio_url'], $memoryTitle]);
                        $result['generated_audio_id'] = $pdo->lastInsertId();
                    }
                }
                
                echo json_encode($result);
                break;
                
            case 'update_audio_url':
                $generatedAudioId = $_POST['generated_audio_id'] ?? '';
                $firebaseUrl = $_POST['firebase_url'] ?? '';
                $localBackupUrl = $_POST['local_backup_url'] ?? '';
                
                if (!$generatedAudioId || !$firebaseUrl) {
                    throw new Exception('Missing generated_audio_id or firebase_url');
                }
                
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                
                // Update the audio_url with the Firebase URL (primary) and store local backup URL
                $stmt = $pdo->prepare("UPDATE generated_audio SET audio_url = ?, local_backup_url = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$firebaseUrl, $localBackupUrl, $generatedAudioId, $userId]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Audio URL updated with Firebase URL',
                        'firebase_url' => $firebaseUrl,
                        'local_backup_url' => $localBackupUrl
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'No records updated']);
                }
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    exit;
}

// GET endpoint for listing voice clones or serving audio files
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    // Check if this is a request for an audio file
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($requestUri, '/temp/') !== false || strpos($requestUri, '/audio_cache/') !== false) {
        // Serve audio file
        $audioFile = basename(parse_url($requestUri, PHP_URL_PATH));
        
        // Try audio_cache first, then temp
        $cacheDir = __DIR__ . '/audio_cache';
        $tempDir = __DIR__ . '/temp';
        
        $filePath = null;
        if (strpos($requestUri, '/audio_cache/') !== false && file_exists($cacheDir . '/' . $audioFile)) {
            $filePath = $cacheDir . '/' . $audioFile;
        } elseif (strpos($requestUri, '/temp/') !== false && file_exists($tempDir . '/' . $audioFile)) {
            $filePath = $tempDir . '/' . $audioFile;
        }
        
        if ($filePath && is_file($filePath)) {
            header('Content-Type: audio/mpeg');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: public, max-age=3600');
            readfile($filePath);
            exit;
        } else {
            http_response_code(404);
            echo 'Audio file not found';
            exit;
        }
    }
    
    // Regular API request
    header('Content-Type: application/json');
    
        // Get user ID from session or URL parameter (for backward compatibility)
    $userId = null;
    
    // Check session first
    if (isLoggedIn()) {
        $userId = getCurrentUser()['user_id'];
    } else {
        // Fallback to URL parameter for backward compatibility
        $userId = $_GET['user_id'] ?? null;
    }
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        exit;
    }
    
    try {
        $voiceAPI = new VoiceCloneAPI();
        $clones = $voiceAPI->getUserVoiceClones($userId);
        echo json_encode(['success' => true, 'clones' => $clones]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
