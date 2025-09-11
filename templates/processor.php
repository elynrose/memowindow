<?php
// Template processor for MemoWindow pages
class TemplateProcessor {
    private $templatePath;
    private $outputPath;
    
    public function __construct($templatePath = 'templates/base.html') {
        $this->templatePath = $templatePath;
    }
    
    /**
     * Process template and generate page
     */
    public function generatePage($pageData) {
        // Read the base template
        $template = file_get_contents($this->templatePath);
        
        // Replace template variables
        $page = $template;
        foreach ($pageData as $key => $value) {
            $page = str_replace('{{' . $key . '}}', $value, $page);
        }
        
        return $page;
    }
    
    /**
     * Save processed page to file
     */
    public function savePage($pageData, $outputFile) {
        $content = $this->generatePage($pageData);
        file_put_contents($outputFile, $content);
        return true;
    }
    
    /**
     * Generate app.php from template
     */
    public function generateAppPage() {
        $pageData = [
            'PAGE_TITLE' => 'Create Memory',
            'PAGE_STYLES' => $this->getAppStyles(),
            'PAGE_CONTENT' => $this->getAppContent(),
            'PAGE_SCRIPTS' => $this->getAppScripts()
        ];
        
        return $this->savePage($pageData, 'app.php');
    }
    
    /**
     * Generate memories.php from template
     */
    public function generateMemoriesPage() {
        $pageData = [
            'PAGE_TITLE' => 'My Memories',
            'PAGE_STYLES' => $this->getMemoriesStyles(),
            'PAGE_CONTENT' => $this->getMemoriesContent(),
            'PAGE_SCRIPTS' => $this->getMemoriesScripts()
        ];
        
        return $this->savePage($pageData, 'memories.php');
    }
    
    /**
     * Generate orders.php from template
     */
    public function generateOrdersPage() {
        $pageData = [
            'PAGE_TITLE' => 'My Orders',
            'PAGE_STYLES' => $this->getOrdersStyles(),
            'PAGE_CONTENT' => $this->getOrdersContent(),
            'PAGE_SCRIPTS' => $this->getOrdersScripts()
        ];
        
        return $this->savePage($pageData, 'orders.php');
    }
    
    /**
     * Get app-specific styles
     */
    private function getAppStyles() {
        return '
        /* App-specific styles */
        .upload-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f9fafb;
        }
        
        .file-upload-area:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .file-upload-area.has-files {
            border-color: #10b981;
            background: #f0fdf4;
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
        
        .waveform-list {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .waveform-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: #f9fafb;
        }
        
        .waveform-item:last-child {
            margin-bottom: 0;
        }
        
        .waveform-info {
            flex: 1;
        }
        
        .waveform-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        
        .waveform-date {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .waveform-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-link {
            color: #374151;
            text-decoration: none;
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .action-link:hover {
            background: #e5e7eb;
        }
        
        .action-link.delete {
            color: #dc2626;
        }
        
        .action-link.delete:hover {
            background: #fef2f2;
        }
        ';
    }
    
    /**
     * Get memories-specific styles
     */
    private function getMemoriesStyles() {
        return '
        /* Memories-specific styles */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .page-subtitle {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
        }
        
        .create-memory-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .create-memory-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }
        
        .memories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .memory-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .memory-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        
        .memory-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
        }
        
        .memory-content {
            padding: 1.5rem;
        }
        
        .memory-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .memory-date {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }
        
        .memory-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .memory-action {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: #374151;
            text-decoration: none;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            transition: background-color 0.2s;
        }
        
        .memory-action:hover {
            background: #f3f4f6;
        }
        
        .memory-action.order {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .memory-action.order:hover {
            background: #bfdbfe;
        }
        
        .memory-action.delete {
            color: #dc2626;
        }
        
        .memory-action.delete:hover {
            background: #fef2f2;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            font-size: 1rem;
            margin-bottom: 2rem;
        }
        ';
    }
    
    /**
     * Get app-specific content
     */
    private function getAppContent() {
        return '
        <!-- Memory Title Card -->
        <div class="card">
            <h2>Memory Title</h2>
            <div class="form-group">
                <input id="titleInput" type="text" class="form-input" placeholder="Memory title (e.g., \'Mom\'s Laughter\', \'Dad\'s Bedtime Story\')" required>
            </div>
        </div>

        <!-- Upload Audio Files Card -->
        <div class="card">
            <h2>Upload Audio Files</h2>
            <div class="file-upload-area" id="fileUploadArea">
                <div style="margin-bottom: 1rem;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #6b7280; margin: 0 auto;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7,10 12,15 17,10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                </div>
                <p style="font-size: 1.125rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Drop audio files here or click to browse</p>
                <p style="color: #6b7280; font-size: 0.875rem;">Supports MP3, WAV, M4A, and other audio formats</p>
                <input type="file" id="fileInput" multiple accept="audio/*" style="display: none;">
            </div>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <p style="color: #6b7280; margin-bottom: 1rem;">Or record your voice</p>
                <button id="btnRecord" type="button" class="record-button" style="display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 1c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2s2-.9 2-2V3c0-1.1-.9-2-2-2zm-1 19.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                    </svg>
                </button>
            </div>
            <div style="margin-top: 1.5rem; text-align: center;">
                <button id="btnCreate" class="btn btn-primary btn-full" disabled>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    Create Memory
                </button>
            </div>
        </div>

        <!-- Hidden Memory Preview -->
        <div id="memoryPreview" class="card hidden" style="display: none;">
            <h2>Memory Preview</h2>
            <div style="text-align: center;">
                <canvas id="previewCanvas" width="400" height="200" style="border: 1px solid #e5e7eb; border-radius: 8px; max-width: 100%;"></canvas>
                <p id="previewStatus" style="margin-top: 1rem; color: #6b7280;"></p>
            </div>
        </div>

        <!-- Your MemoWindows Section -->
        <div class="waveform-list">
            <h2>Your MemoWindows</h2>
            <div id="waveformList">
                <div class="loading">
                    <div class="loading-spinner"></div>
                    Loading your memories...
                </div>
            </div>
        </div>
        ';
    }
    
    /**
     * Get memories-specific content
     */
    private function getMemoriesContent() {
        return '
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">My Memories</h1>
            <p class="page-subtitle">Your beautiful waveform memories, ready to share and print</p>
            <a href="app.php" class="create-memory-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/>
                </svg>
                Create New Memory
            </a>
        </div>

        <!-- Memories Container -->
        <div id="memoriesContainer">
            <div class="loading">
                <div class="loading-spinner"></div>
                Loading your memories...
            </div>
        </div>
        ';
    }
    
    /**
     * Get orders-specific styles
     */
    private function getOrdersStyles() {
        return '
        /* Orders-specific styles */
        .order-card {
            background: white;
            border: 1px solid #e6e9f2;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .order-title {
            font-size: 18px;
            font-weight: 600;
            color: #0b0d12;
            margin: 0;
        }
        
        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-shipped {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 16px;
            align-items: center;
        }
        
        .order-image {
            width: 80px;
            height: 50px;
            border-radius: 8px;
            border: 1px solid #e6e9f2;
            object-fit: cover;
            background: white;
        }
        
        .order-info {
            flex: 1;
        }
        
        .order-info h4 {
            margin: 0 0 4px 0;
            color: #0b0d12;
            font-size: 14px;
        }
        
        .order-info p {
            margin: 0;
            color: #6b7280;
            font-size: 12px;
        }
        
        .order-price {
            font-size: 16px;
            font-weight: 600;
            color: #0b0d12;
            text-align: right;
        }
        
        .order-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f3f4f6;
            font-size: 12px;
            color: #6b7280;
        }
        
        .cancel-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 10px;
            transition: background-color 0.2s;
        }
        
        .cancel-btn:hover {
            background: #b91c1c;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .empty-state h3 {
            color: #0b0d12;
            margin-bottom: 8px;
        }
        
        @media (max-width: 640px) {
            .order-details {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .order-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .order-meta {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
        }
        ';
    }
    
    /**
     * Get orders-specific content
     */
    private function getOrdersContent() {
        return '
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">My Orders</h1>
            <p class="page-subtitle">Track your MemoryWave print orders</p>
        </div>

        <!-- Orders Container -->
        <div id="ordersContainer">
            <div class="loading">
                <div class="loading-spinner"></div>
                Loading your orders...
            </div>
        </div>
        ';
    }
    
    /**
     * Get app-specific scripts
     */
    private function getAppScripts() {
        return '
        // App-specific initialization
        console.log("🎵 App page loaded");
        
        // Import and initialize app functionality
        import("./src/app.js").then(module => {
            module.initApp();
        }).catch(error => {
            console.error("Failed to load app module:", error);
        });
        ';
    }
    
    /**
     * Get memories-specific scripts
     */
    private function getMemoriesScripts() {
        return '
        // Memories-specific initialization
        console.log("💕 Memories page loaded");
        
        // Import globals first (for order functionality)
        import("./src/globals.js").then(() => {
            console.log("✅ Globals module loaded successfully");
            
            // Import and initialize memories functionality
            return import("./src/memories.js");
        }).then(module => {
            console.log("✅ Memories module loaded successfully");
            module.initMemories();
        }).catch(error => {
            console.error("❌ Failed to load memories module:", error);
            // Fallback: show error message
            const container = document.getElementById("memoriesContainer");
            if (container) {
                container.innerHTML = "<div class=\"empty-state\">" +
                    "<div class=\"empty-state-icon\">⚠️</div>" +
                    "<h3>Error Loading Memories</h3>" +
                    "<p>There was a problem loading the memories module. Please refresh the page.</p>" +
                    "<button onclick=\"location.reload()\" class=\"create-memory-btn\" style=\"margin-top: 1rem;\">" +
                        "Refresh Page" +
                    "</button>" +
                "</div>";
            }
        });
        ';
    }
    
    /**
     * Get orders-specific scripts
     */
    private function getOrdersScripts() {
        return '
        // Orders-specific initialization
        console.log("📦 Orders page loaded");
        
        // Import and initialize orders functionality
        import("./src/orders.js").then(module => {
            console.log("✅ Orders module loaded successfully");
            module.initOrders();
        }).catch(error => {
            console.error("❌ Failed to load orders module:", error);
            // Fallback: show error message
            const container = document.getElementById("ordersContainer");
            if (container) {
                container.innerHTML = `
                    <div class="order-card" style="text-align: center; color: #dc2626;">
                        <h3>Error Loading Orders</h3>
                        <p>Failed to load orders functionality. Please refresh the page.</p>
                    </div>
                `;
            }
        });
        ';
    }
}

// Auto-generate pages if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $processor = new TemplateProcessor();
    
    echo "🔄 Generating pages from template...\n";
    
    if ($processor->generateAppPage()) {
        echo "✅ Generated app.php\n";
    } else {
        echo "❌ Failed to generate app.php\n";
    }
    
    if ($processor->generateMemoriesPage()) {
        echo "✅ Generated memories.php\n";
    } else {
        echo "❌ Failed to generate memories.php\n";
    }
    
    if ($processor->generateOrdersPage()) {
        echo "✅ Generated orders.php\n";
    } else {
        echo "❌ Failed to generate orders.php\n";
    }
    
    echo "🎉 Template generation complete!\n";
}
?>
