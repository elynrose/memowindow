<?php
require_once 'config.php';
require_once 'voice_clone_api.php';

// Test the voice clone API
try {
    $voiceClone = new VoiceCloneAPI();
    
    // Test with a sample audio URL (you'll need to replace this with an actual audio file)
    $testAudioUrl = 'https://www.soundjay.com/misc/sounds/bell-ringing-05.wav';
    $voiceName = 'Test Voice ' . date('Y-m-d H:i:s');
    
    echo "Testing voice clone API with subscription...\n";
    echo "Audio URL: $testAudioUrl\n";
    echo "Voice Name: $voiceName\n\n";
    
    $result = $voiceClone->createVoiceClone($testAudioUrl, $voiceName);
    
    echo "Result: " . print_r($result, true) . "\n";
    
    if ($result['success']) {
        echo "✅ Success! Voice cloning is working.\n";
        echo "Voice ID: " . ($result['voice_id'] ?? 'Not provided') . "\n";
        echo "Voice Name: " . ($result['voice_name'] ?? 'Not provided') . "\n";
    } else {
        echo "❌ Error: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
?>

