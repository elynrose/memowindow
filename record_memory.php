<?php
/**
 * record_memory.php - Public recorder page for invited users to submit memories
 * No authentication required - uses invitation token validation
 */

require_once 'config.php';
require_once 'invitation_system.php';

// Get invitation token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: index.php?error=invalid_invitation');
    exit;
}

$invitationSystem = new InvitationSystem();

// Validate invitation with comprehensive checks
$validation = $invitationSystem->validateInvitationForSubmission($token);

if (!$validation['valid']) {
    $error = 'invitation_validation_failed';
    header('Location: index.php?error=' . $error . '&message=' . urlencode($validation['error']));
    exit;
}

$invitation = $validation['invitation'];

// Get owner's subscription information
require_once 'SubscriptionManager.php';
$subscriptionManager = new SubscriptionManager();
$ownerLimits = $subscriptionManager->getUserLimits($invitation['owner_user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Memory - <?php echo htmlspecialchars($invitation['invitation_title']); ?></title>
    <link rel="stylesheet" href="css/app.css?v=<?php echo time(); ?>">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 20px;
        }
        
        .recorder-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
            width: 100%;
        }
        
        .invitation-header {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .invitation-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .invitation-message {
            color: #4a5568;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .submission-info {
            background: #f7fafc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .recorder-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        .subscription-limit {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .subscription-limit h3 {
            margin: 0 0 10px 0;
            color: #2d3748;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .subscription-limit .limit-info {
            color: #4a5568;
            font-size: 0.9rem;
        }
        
        .limit-warning {
            background: #fef5e7;
            border-color: #f6ad55;
            color: #c05621;
        }
        
        .limit-exceeded {
            background: #fed7d7;
            border-color: #fc8181;
            color: #c53030;
        }
        
        .memory-progress {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .memory-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #48bb78, #38a169);
            transition: width 0.3s ease;
        }
        
        .memory-progress-bar.warning {
            background: linear-gradient(90deg, #f6ad55, #ed8936);
        }
        
        .memory-progress-bar.danger {
            background: linear-gradient(90deg, #fc8181, #e53e3e);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
            box-sizing: border-box;
            max-width: 100%;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .recorder-section {
            margin-bottom: 30px;
        }
        
        .recorder-controls {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .record-button {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .record-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }
        
        .record-button:active {
            transform: translateY(0);
        }
        
        .record-button.recording {
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .recording-status {
            font-weight: 600;
            color: #e53e3e;
        }
        
        .recording-status.recording {
            color: #38a169;
        }
        
        .audio-preview {
            margin-top: 20px;
        }
        
        .audio-preview audio {
            width: 100%;
        }
        
        .submit-button {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
        }
        
        .submit-button:hover {
            background: #5a67d8;
        }
        
        .submit-button:disabled {
            background: #a0aec0;
            cursor: not-allowed;
        }
        
        /* CAPTCHA styling */
        .g-recaptcha {
            margin: 20px 0;
            display: flex;
            justify-content: center;
        }
        
        .error-message {
            color: #e53e3e;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .recorder-container {
                padding: 15px;
            }
            
            .invitation-header,
            .recorder-card {
                padding: 20px;
            }
            
            .invitation-title {
                font-size: 1.5rem;
            }
            
            .recorder-controls {
                flex-direction: column;
                gap: 10px;
            }
            
            .record-button {
                width: 70px;
                height: 70px;
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 480px) {
            .recorder-container {
                padding: 10px;
            }
            
            .invitation-header,
            .recorder-card {
                padding: 15px;
            }
            
            .form-input {
                font-size: 16px; /* Prevents zoom on iOS */
            }
        }
        
        .success-message {
            background: #c6f6d5;
            color: #22543d;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error-message {
            background: #fed7d7;
            color: #742a2a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="recorder-container">
        <a href="index.php" class="back-link">← Back to Home</a>
        
        <div class="invitation-header">
            <h1 class="invitation-title"><?php echo htmlspecialchars($invitation['invitation_title']); ?></h1>
            <?php if (!empty($invitation['invitation_message'])): ?>
                <div class="invitation-message">
                    <?php echo nl2br(htmlspecialchars($invitation['invitation_message'])); ?>
                </div>
            <?php endif; ?>
            
            <div class="submission-info">
                <strong>Submissions:</strong> <?php echo $invitation['current_submissions']; ?>
                <?php if ($invitation['max_submissions']): ?>
                    / <?php echo $invitation['max_submissions']; ?>
                <?php endif; ?>
                <?php if ($invitation['allow_public']): ?>
                    <br><small>This invitation is open to the public</small>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Subscription Limits Card -->
        <div class="recorder-card" style="margin-bottom: 40px;">
            <div class="subscription-limit <?php 
                if ($ownerLimits['memory_used'] >= $ownerLimits['memory_limit']) {
                    echo 'limit-exceeded';
                } elseif ($ownerLimits['memory_used'] >= ($ownerLimits['memory_limit'] * 0.8)) {
                    echo 'limit-warning';
                }
            ?>">
                <h3>📊 Owner's Subscription: <?php echo htmlspecialchars($ownerLimits['package_name']); ?></h3>
                <div class="limit-info">
                    <strong>Memory Limit:</strong> <?php echo $ownerLimits['memory_used']; ?> / <?php echo $ownerLimits['memory_limit']; ?> memories
                    
                    <?php 
                    $usagePercentage = ($ownerLimits['memory_used'] / $ownerLimits['memory_limit']) * 100;
                    $progressClass = '';
                    if ($usagePercentage >= 100) {
                        $progressClass = 'danger';
                    } elseif ($usagePercentage >= 80) {
                        $progressClass = 'warning';
                    }
                    ?>
                    
                    <div class="memory-progress">
                        <div class="memory-progress-bar <?php echo $progressClass; ?>" 
                             style="width: <?php echo min($usagePercentage, 100); ?>%"></div>
                    </div>
                    
                    <?php if ($ownerLimits['memory_used'] >= $ownerLimits['memory_limit']): ?>
                        <span style="color: #c53030; font-weight: bold;">⚠️ Memory limit reached - submissions may be limited</span>
                    <?php elseif ($ownerLimits['memory_used'] >= ($ownerLimits['memory_limit'] * 0.8)): ?>
                        <span style="color: #c05621; font-weight: bold;">⚠️ Approaching memory limit</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="recorder-card">
            <h2>🎤 Record Your Memory</h2>
            
            <div id="successMessage" class="success-message" style="display: none;"></div>
            <div id="errorMessage" class="error-message" style="display: none;"></div>
            
            <form id="memoryForm">
                <input type="hidden" id="invitationId" value="<?php echo $invitation['id']; ?>">
                <input type="hidden" id="invitationToken" value="<?php echo $token; ?>">
                
                <div class="form-group">
                    <label for="submitterEmail" class="form-label">Your Email Address</label>
                    <?php if (!$invitation['allow_public']): ?>
                        <p style="color: #e53e3e; font-size: 0.9rem; margin-bottom: 10px;">
                            ⚠️ This invitation is only for: <strong><?php echo htmlspecialchars($invitation['invited_email']); ?></strong>
                        </p>
                    <?php endif; ?>
                    <input type="email" id="submitterEmail" class="form-input" required 
                           placeholder="<?php echo $invitation['allow_public'] ? 'Enter your email address' : 'Enter the invited email address'; ?>"
                           value="<?php echo $invitation['allow_public'] ? '' : htmlspecialchars($invitation['invited_email']); ?>">
                    <div id="email-error" class="error-message" style="display: none; margin-top: 5px;"></div>
                </div>
                
                <div class="form-group">
                    <label for="memoryTitle" class="form-label">Memory Title</label>
                    <input type="text" id="memoryTitle" class="form-input" required 
                           placeholder="Give your memory a title">
                </div>
                
                <div class="form-group">
                    <label for="submissionNotes" class="form-label">Additional Notes (Optional)</label>
                    <textarea id="submissionNotes" class="form-input" rows="4" 
                              placeholder="Add any additional context or notes about this memory"></textarea>
                </div>
                
                <div class="recorder-section">
                    <div style="text-align: center; margin: 2rem 0;">
                        <p style="color: #6b7280; margin-bottom: 1rem;">Or record your voice</p>
                        <div class="recorder-controls">
                            <button type="button" id="recordButton" class="record-button">
                                🎤
                            </button>
                            <span id="recordingStatus" class="recording-status">Click to start recording</span>
                        </div>
                        <div id="audioPreview" class="audio-preview" style="display: none;"></div>
                    </div>
                </div>
                
                <!-- reCAPTCHA -->
                <div class="form-group">
                    <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"></div>
                    <div id="captcha-error" class="error-message" style="display: none; margin-top: 10px;"></div>
                </div>
                
                <button type="submit" id="submitButton" class="submit-button" disabled>
                    Submit Memory
                </button>
            </form>
        </div>
    </div>
    
    <script>
        let mediaRecorder;
        let audioChunks = [];
        let isRecording = false;
        let audioBlob = null;
        
        const recordButton = document.getElementById('recordButton');
        const recordingStatus = document.getElementById('recordingStatus');
        const audioPreview = document.getElementById('audioPreview');
        const submitButton = document.getElementById('submitButton');
        const memoryForm = document.getElementById('memoryForm');
        
        // Initialize recorder
        async function initRecorder() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                
                mediaRecorder.ondataavailable = (event) => {
                    audioChunks.push(event.data);
                };
                
                mediaRecorder.onstop = () => {
                    audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                    const audioUrl = URL.createObjectURL(audioBlob);
                    
                    audioPreview.innerHTML = `
                        <audio controls>
                            <source src="${audioUrl}" type="audio/wav">
                            Your browser does not support the audio element.
                        </audio>
                    `;
                    audioPreview.style.display = 'block';
                    submitButton.disabled = false;
                };
                
            } catch (error) {
                showError('Microphone access denied. Please allow microphone access to record.');
            }
        }
        
        // Toggle recording
        function toggleRecording() {
            if (!isRecording) {
                startRecording();
            } else {
                stopRecording();
            }
        }
        
        function startRecording() {
            audioChunks = [];
            mediaRecorder.start();
            isRecording = true;
            
            recordButton.classList.add('recording');
            recordButton.innerHTML = '⏹️';
            recordingStatus.textContent = 'Recording... Click to stop';
            recordingStatus.classList.add('recording');
        }
        
        function stopRecording() {
            mediaRecorder.stop();
            isRecording = false;
            
            recordButton.classList.remove('recording');
            recordButton.innerHTML = '🎤';
            recordingStatus.textContent = 'Recording complete. Click to record again';
            recordingStatus.classList.remove('recording');
        }
        
        // Form submission
        async function submitMemory(event) {
            event.preventDefault();
            
            // Clear previous errors
            clearErrors();
            
            // Validate CAPTCHA
            const captchaResponse = grecaptcha.getResponse();
            if (!captchaResponse) {
                showCaptchaError('Please complete the CAPTCHA verification.');
                return;
            }
            
            // Validate email if invitation is not public
            const submitterEmail = document.getElementById('submitterEmail').value;
            const invitationEmail = '<?php echo $invitation['invited_email']; ?>';
            const allowPublic = <?php echo $invitation['allow_public'] ? 'true' : 'false'; ?>;
            
            if (!allowPublic && submitterEmail.toLowerCase() !== invitationEmail.toLowerCase()) {
                showEmailError('Email must match the invited email address: ' + invitationEmail);
                return;
            }
            
            if (!audioBlob) {
                showError('Please record an audio memory first.');
                return;
            }
            
            const formData = new FormData();
            formData.append('audio', audioBlob, 'memory.wav');
            formData.append('invitation_id', document.getElementById('invitationId').value);
            formData.append('submitter_email', document.getElementById('submitterEmail').value);
            formData.append('title', document.getElementById('memoryTitle').value);
            formData.append('notes', document.getElementById('submissionNotes').value);
            formData.append('captcha_response', captchaResponse);
            
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';
            
            try {
                // Upload audio file
                const uploadResponse = await fetch('upload_invitation_audio.php', {
                    method: 'POST',
                    body: formData
                });
                
                const uploadResult = await uploadResponse.json();
                
                if (uploadResult.success) {
                    // Submit memory data
                    const submitResponse = await fetch('invitation_api.php?action=submit', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            invitation_id: document.getElementById('invitationId').value,
                            submitter_email: document.getElementById('submitterEmail').value,
                            title: document.getElementById('memoryTitle').value,
                            audio_url: uploadResult.audio_url,
                            notes: document.getElementById('submissionNotes').value
                        })
                    });
                    
                    const submitResult = await submitResponse.json();
                    
                    if (submitResult.success) {
                        showSuccess('Memory submitted successfully! Thank you for sharing.');
                        memoryForm.reset();
                        audioPreview.style.display = 'none';
                        audioBlob = null;
                        submitButton.disabled = true;
                    } else {
                        showError(submitResult.error || 'Failed to submit memory.');
                    }
                } else {
                    showError(uploadResult.error || 'Failed to upload audio.');
                }
                
            } catch (error) {
                showError('An error occurred while submitting your memory.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Submit Memory';
            }
        }
        
        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            document.getElementById('errorMessage').style.display = 'none';
        }
        
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            document.getElementById('successMessage').style.display = 'none';
        }
        
        function showCaptchaError(message) {
            const errorDiv = document.getElementById('captcha-error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
        
        function showEmailError(message) {
            const errorDiv = document.getElementById('email-error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
        
        function clearErrors() {
            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('captcha-error').style.display = 'none';
            document.getElementById('email-error').style.display = 'none';
        }
        
        // Validate invitation on page load
        async function validateInvitation() {
            try {
                const response = await fetch(`invitation_api.php?action=validate&token=<?php echo $token; ?>`);
                const result = await response.json();
                
                if (!result.success || !result.valid) {
                    showError(result.error || 'Invalid invitation');
                    disableForm();
                    return;
                }
                
                // Update page title with invitation title
                document.title = `Record Memory - ${result.invitation.invitation_title}`;
                
                // Check if email validation is required
                if (!result.invitation.allow_public) {
                    const emailField = document.querySelector('input[type="email"]');
                    if (emailField) {
                        emailField.required = true;
                        emailField.placeholder = `Enter your email (${result.invitation.invited_email})`;
                    }
                }
                
                // Check owner's memory limit
                const ownerLimits = <?php echo json_encode($ownerLimits); ?>;
                if (ownerLimits.memory_used >= ownerLimits.memory_limit) {
                    showError('The owner has reached their memory limit. Submissions are currently disabled.');
                    disableForm();
                    return;
                }
                
                console.log('Invitation validated successfully:', result.invitation);
                
            } catch (error) {
                console.error('Validation error:', error);
                showError('Failed to validate invitation');
                disableForm();
            }
        }
        
        // Disable form when validation fails
        function disableForm() {
            const titleInput = document.getElementById('memoryTitle');
            const recordButton = document.getElementById('recordButton');
            const submitButton = document.getElementById('submitButton');
            
            if (titleInput) titleInput.disabled = true;
            if (recordButton) recordButton.disabled = true;
            if (submitButton) submitButton.disabled = true;
        }
        
        // Event listeners
        recordButton.addEventListener('click', toggleRecording);
        memoryForm.addEventListener('submit', submitMemory);
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            validateInvitation();
            initRecorder();
        });
    </script>
</body>
</html>
