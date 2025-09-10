<?php
/**
 * ElevenLabs Voice Cloning API Integration
 * Handles voice cloning and text-to-speech generation
 */

require_once 'config.php';

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
            
            // Create voice clone
            $url = $this->baseUrl . '/voices/ivc';
            
            $postData = [
                'name' => $voiceName,
                'files' => [
                    [
                        'name' => 'audio.mp3',
                        'content' => base64_encode($audioData),
                        'type' => 'audio/mpeg'
                    ]
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
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('ElevenLabs API error: ' . $response);
            }
            
            $result = json_decode($response, true);
            
            return [
                'success' => true,
                'voice_id' => $result['voice_id'] ?? null,
                'voice_name' => $voiceName
            ];
            
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
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('ElevenLabs TTS API error: ' . $response);
            }
            
            // Save generated audio to temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'generated_audio_');
            file_put_contents($tempFile, $response);
            
            return [
                'success' => true,
                'audio_file' => $tempFile,
                'text' => $text
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? '';
    
    try {
        $voiceAPI = new VoiceCloneAPI();
        
        switch ($action) {
            case 'create_clone':
                $memoryId = intval($_POST['memory_id'] ?? 0);
                $voiceName = $_POST['voice_name'] ?? '';
                $audioUrl = $_POST['audio_url'] ?? '';
                
                if (!$memoryId || !$voiceName || !$audioUrl) {
                    throw new Exception('Missing required parameters');
                }
                
                // Create voice clone
                $result = $voiceAPI->createVoiceClone($audioUrl, $voiceName);
                
                if ($result['success']) {
                    // Save to database
                    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ]);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO voice_clones (user_id, source_memory_id, voice_id, voice_name, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$userId, $memoryId, $result['voice_id'], $voiceName]);
                    
                    $result['clone_id'] = $pdo->lastInsertId();
                }
                
                echo json_encode($result);
                break;
                
            case 'generate_speech':
                $voiceId = $_POST['voice_id'] ?? '';
                $text = $_POST['text'] ?? '';
                
                if (!$voiceId || !$text) {
                    throw new Exception('Missing voice_id or text');
                }
                
                $result = $voiceAPI->generateSpeech($voiceId, $text);
                echo json_encode($result);
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

// GET endpoint for listing voice clones
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    
    $userId = $_GET['user_id'] ?? '';
    
    if (!$userId) {
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
