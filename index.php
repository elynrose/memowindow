<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MemoWindow - Transform Voice into Beautiful Waveform Art</title>
    <meta name="description" content="Transform precious voice recordings into beautiful waveform art and create lasting visual memories. Turn your memories into stunning prints.">
    <meta name="theme-color" content="#667eea">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Firebase -->
    <script type="module" src="firebase-config.php"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Navigation Styles -->
    <link rel="stylesheet" href="includes/navigation.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }
        
        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .cta-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .subscription-status {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .subscription-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            font-size: 0.9rem;
        }
        
        .subscription-plan {
            font-weight: 600;
            color: #333;
        }
        
        .subscription-status-text {
            font-size: 0.8rem;
            color: #666;
        }
        
        .upgrade-button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .upgrade-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12rem 0 6rem!important;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            background: white;
            color: #667eea;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .btn-secondary {
            background: transparent;
            color: white;
            padding: 1rem 2rem;
            border: 2px solid white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: white;
            color: #667eea;
        }
        
        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .section-subtitle {
            text-align: center;
            font-size: 1.25rem;
            color: #666;
            margin-bottom: 4rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Intro Section */
        .intro {
            padding: 6rem 0;
            background: white;
        }
        
        .intro-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }
        
        .intro-text h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .intro-text p {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .intro-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            position: relative;
            overflow: hidden;
        }
        
        .intro-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M10,50 Q30,20 50,50 T90,50" stroke="rgba(255,255,255,0.3)" stroke-width="2" fill="none"/><path d="M10,60 Q30,30 50,60 T90,60" stroke="rgba(255,255,255,0.2)" stroke-width="2" fill="none"/><path d="M10,40 Q30,10 50,40 T90,40" stroke="rgba(255,255,255,0.2)" stroke-width="2" fill="none"/></svg>');
        }
        
        /* Video Section */
        .video-section {
            padding: 6rem 0;
            background: #f8fafc;
        }
        
        .video-container {
            max-width: 800px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .video-placeholder {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            position: relative;
        }
        
        .play-button {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .play-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        /* Features Section */
        .features {
            padding: 6rem 0;
            background: white;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            text-align: center;
            padding: 2rem;
            border-radius: 12px;
            background: #f8fafc;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: #667eea;
            font-size: 2.5rem;
        }
        
        .feature-card h3 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.6;
        }
        
        /* Pricing Section */
        .pricing {
            padding: 6rem 0;
            background: #f8fafc;
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .pricing-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .pricing-card.featured {
            border: 2px solid #667eea;
            transform: scale(1.05);
        }
        
        .pricing-card.featured::before {
            content: 'Most Popular';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #667eea;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .loading-packages {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .pricing-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .price {
            font-size: 3rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .price-period {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .pricing-features {
            list-style: none;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .pricing-features li {
            padding: 0.5rem 0;
            color: #666;
            text-align: left;
        }
        
        .pricing-features li::before {
            content: '✓';
            color: #667eea;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        /* FAQ Section */
        .faq {
            padding: 6rem 0;
            background: white;
        }
        
        .faq-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .faq-item {
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem 0;
        }
        
        .faq-question {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-answer {
            color: #666;
            line-height: 1.6;
            display: none;
        }
        
        .faq-answer.active {
            display: block;
        }
        
        .faq-toggle {
            font-size: 1.5rem;
            color: #667eea;
            transition: transform 0.3s;
        }
        
        .faq-toggle.active {
            transform: rotate(45deg);
        }
        
        /* Footer */
        .footer {
            background: #1a202c;
            color: white;
            padding: 4rem 0 2rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h3 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
        
        .footer-section p,
        .footer-section a {
            color: #a0aec0;
            text-decoration: none;
            line-height: 1.6;
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-bottom {
            border-top: 1px solid #2d3748;
            padding-top: 2rem;
            text-align: center;
            color: #a0aec0;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .subscription-status {
                flex-direction: column;
                gap: 0.5rem;
                align-items: center;
            }
            
            .subscription-info {
                align-items: center;
                text-align: center;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .intro-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .pricing-card.featured {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Transform Voice into Beautiful Waveform Art</h1>
            <p>Turn your precious voice recordings into stunning visual memories. Create unique waveform prints that capture the essence of your most meaningful moments.</p>
            <div class="hero-buttons">
                <a href="login.php" class="btn-primary">Start Creating</a>
                <a href="#video" class="btn-secondary">Watch Demo</a>
            </div>
        </div>
    </section>

    <!-- Intro Section -->
    <section class="intro">
        <div class="container">
            <h2 class="section-title">Why MemoWindow?</h2>
            <p class="section-subtitle">Every voice tells a story. We help you preserve those stories in beautiful, tangible form.</p>
            
            <div class="intro-content">
                <div class="intro-text">
                    <h3>Preserve Your Most Precious Moments</h3>
                    <p>Whether it's a loved one's voice, a child's first words, or a special message, MemoWindow transforms audio into stunning visual art that you can hold, frame, and treasure forever.</p>
                    <p>Our advanced waveform technology captures every nuance of the audio, creating unique patterns that are as individual as the voice itself.</p>
                </div>
                <div class="intro-image">
                    <div>Waveform Preview</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Video Section -->
    <section class="video-section" id="video">
        <div class="container">
            <h2 class="section-title">See MemoWindow in Action</h2>
            <p class="section-subtitle">Watch how easy it is to transform your voice recordings into beautiful artwork</p>
            
            <div class="video-container">
                <div class="video-placeholder">
                    <div class="play-button">
                        ▶
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title">Powerful Features</h2>
            <p class="section-subtitle">Everything you need to create stunning waveform art</p>
            
            <div class="features-grid">
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">🎵</div>
                    <h3>High-Quality Audio Processing</h3>
                    <p>Advanced algorithms capture every detail of your audio, creating precise and beautiful waveform patterns.</p>
                </div>
                
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">🎨</div>
                    <h3>Customizable Designs</h3>
                    <p>Choose from multiple sizes, colors, and styles to create the perfect piece for your space.</p>
                </div>
                
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">🖼️</div>
                    <h3>Premium Print Quality</h3>
                    <p>Professional-grade printing on high-quality materials ensures your artwork looks stunning for years to come.</p>
                </div>
                
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">📱</div>
                    <h3>Easy Upload & Processing</h3>
                    <p>Simply upload your audio file and watch as we transform it into beautiful waveform art in minutes.</p>
                </div>
                
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">🚚</div>
                    <h3>Fast Shipping</h3>
                    <p>Quick turnaround times mean you can have your custom artwork delivered to your door in days, not weeks.</p>
                </div>
                
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">🎤</div>
                    <h3>Voice Cloning Technology</h3>
                    <p>Clone voices from your memories and generate new audio content using AI-powered voice synthesis.</p>
                </div>
                
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">💝</div>
                    <h3>Perfect for Gifts</h3>
                    <p>Create meaningful, personalized gifts that will be treasured for a lifetime.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing" id="pricing">
        <div class="container">
            <h2 class="section-title">Choose Your Plan</h2>
            <p class="section-subtitle">Unlock the full potential of MemoWindow with our flexible subscription plans</p>
            
            <div class="pricing-grid" id="pricingGrid">
                <!-- Packages will be loaded dynamically here -->
                <div class="loading-packages">
                    <div class="loading-spinner"></div>
                    <p>Loading packages...</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq" id="faq">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <p class="section-subtitle">Everything you need to know about MemoWindow</p>
            
            <div class="faq-container">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        How does MemoWindow work?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        Simply upload your audio file, and our advanced technology will analyze the waveform and create a beautiful visual representation. You can then customize the design and order a high-quality print.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        What audio formats do you support?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        We support all major audio formats including MP3, WAV, M4A, and more. For best results, we recommend high-quality recordings with minimal background noise.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        How long does processing take?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        Processing typically takes just a few minutes. Once your waveform is ready, you can preview it and make any adjustments before ordering your print.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        What's included with my order?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        Your order includes a high-quality print on premium paper, ready for framing. We also provide a digital copy of your waveform for your records.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        How long does shipping take?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        Standard shipping takes 3-5 business days. We also offer expedited shipping options for faster delivery.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        What is voice cloning and how does it work?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        Voice cloning allows you to create a digital copy of a voice from your audio memories. Using AI technology, you can then generate new audio content in that voice, perfect for creating additional memories or messages.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        How do subscriptions work?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        We offer three plans: Basic (free with 3 memories), Standard ($9.99/month with 30 memories and 1 voice clone), and Premium ($19.99/month with unlimited memories and 10 voice clones). You can upgrade or downgrade anytime.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        Can I make changes after ordering?
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        Changes can be made before your order goes to print. Once printing begins, changes cannot be made, but you can always create a new design with modifications.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>MemoWindow</h3>
                    <p>Transforming voice recordings into beautiful waveform art. Preserve your most precious moments in stunning visual form.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <p><a href="#features">Features</a></p>
                    <p><a href="#pricing">Pricing</a></p>
                    <p><a href="#faq">FAQ</a></p>
                    <p><a href="login.php">Get Started</a></p>
                </div>
                
                <div class="footer-section">
                    <h3>Support</h3>
                    <p><a href="mailto:support@memorywindow.com">support@memorywindow.com</a></p>
                    <p><a href="tel:+1-203-450-2800">+1 203-450-2800</a></p>
                    <p>Mon-Fri 9AM-6PM EST</p>
                </div>
                
                <div class="footer-section">
                    <h3>Legal</h3>
                    <p><a href="privacy-policy.html">Privacy Policy</a></p>
                    <p><a href="terms-of-service.html">Terms of Service</a></p>
                    <p><a href="refund-policy.html">Refund Policy</a></p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 MemoWindow. All rights reserved. Made with ❤️ for preserving memories.</p>
            </div>
        </div>
    </footer>

    <script type="module">
        // Import Firebase modules
        import { auth } from './firebase-config.php';
        import { initNavigation } from './includes/navigation.js';
        
        // Make auth globally available after a short delay to ensure initialization
        setTimeout(() => {
            window.auth = auth;
            console.log('Auth object made globally available:', window.auth);
            
            // Initialize navigation
            initNavigation();
        }, 100);
        
        // Load packages dynamically
        async function loadPackages() {
            try {
                console.log('Loading packages from get_packages.php...');
                const response = await fetch(`get_packages.php?t=${Date.now()}`);
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Response data:', data);
                
                if (data.success && data.packages) {
                    console.log('Rendering packages:', data.packages.length);
                    renderPackages(data.packages);
                } else {
                    console.error('Failed to load packages:', data.error || 'No packages in response');
                    showPackageError();
                }
            } catch (error) {
                console.error('Error loading packages:', error);
                console.error('Error details:', error.message);
                
                // If it's a JSON parse error, log the response text
                if (error.message.includes('Unexpected token')) {
                    console.error('This might be an HTML response instead of JSON');
                }
                
                showPackageError();
            }
        }
        
        function renderPackages(packages) {
            try {
                console.log('renderPackages called with:', packages);
                const pricingGrid = document.getElementById('pricingGrid');
                
                if (!pricingGrid) {
                    console.error('pricingGrid element not found');
                    return;
                }
                
                if (!packages || packages.length === 0) {
                    console.error('No packages to render');
                    showPackageError();
                    return;
                }
                
                // Filter out disabled packages
                const activePackages = packages.filter(pkg => pkg.is_active !== false);
                console.log('Rendering', activePackages.length, 'active packages (filtered from', packages.length, 'total)');
                
                if (activePackages.length === 0) {
                    console.error('No active packages to render');
                    showPackageError();
                    return;
                }
                
                const packagesHTML = activePackages.map(pkg => {
                    const isPopular = pkg.is_popular;
                    const isFree = pkg.price_monthly === 0;
                    
                    return `
                        <div class="pricing-card ${isPopular ? 'featured' : ''}">
                            <h3>${pkg.name}</h3>
                            <div class="price">
                                ${isFree ? 'Free' : `$${pkg.price_monthly.toFixed(2)}`}
                            </div>
                            <div class="price-period">
                                ${isFree ? 'forever' : 'per month'}
                            </div>
                            <ul class="pricing-features">
                                ${pkg.features.map(feature => `<li>${feature}</li>`).join('')}
                            </ul>
                            ${isFree ? 
                                '<a href="#" class="cta-button" onclick="startSubscription(\'basic\', \'monthly\', this)">Get Started Free</a>' :
                                `<a href="#" class="cta-button" onclick="startSubscription('${pkg.slug}', 'monthly', this)">Start ${pkg.name} Plan</a>`
                            }
                        </div>
                    `;
                }).join('');
                
                pricingGrid.innerHTML = packagesHTML;
                console.log('Packages rendered successfully');
                
            } catch (error) {
                console.error('Error in renderPackages:', error);
                showPackageError();
            }
        }
        
        function showPackageError() {
            const pricingGrid = document.getElementById('pricingGrid');
            pricingGrid.innerHTML = `
                <div class="loading-packages">
                    <p>Unable to load packages. Please refresh the page.</p>
                    <p><small>Make sure you're accessing the local development server: <strong>http://localhost/memowindow</strong></small></p>
                    <button onclick="loadPackages()" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-top: 10px;">
                        Try Again
                    </button>
                </div>
            `;
        }

        // Load packages immediately (don't wait for window load)
        console.log('Loading packages immediately...');
        loadPackages();
        
        // Check if user is already logged in and redirect to app
        window.addEventListener('load', function() {
            console.log('Index page loaded, checking auth state...');
            console.log('Auth object:', auth);
            console.log('Auth currentUser:', auth.currentUser);
            
            // Check current user immediately
            const currentUser = auth.currentUser;
            if (currentUser) {
                console.log('Current user found, staying on homepage for subscription access');
                // Don't redirect - let logged-in users access subscription buttons
                updateNavigationForLoggedInUser(currentUser);
            }
            
            // Also set up auth state listener as backup
            auth.onAuthStateChanged(function(user) {
                console.log('Auth state changed on index:', user ? 'Logged in' : 'Logged out');
                if (user) {
                    // User is signed in, but don't redirect - let them access subscription buttons
                    console.log('User is logged in, staying on homepage for subscription access');
                    updateNavigationForLoggedInUser(user);
                } else {
                    // User logged out, show login button
                    updateNavigationForLoggedOutUser();
                }
            });
        });
        
        // Function to update navigation for logged-in users
        async function updateNavigationForLoggedInUser(user) {
            try {
                // Get user's subscription status
                const response = await fetch(`get_user_subscription.php
                const data = await response.json();
                
                const navSection = document.getElementById('nav-auth-section');
                
                if (data.success) {
                    const subscription = data.subscription;
                    const isFree = subscription.package_slug === 'free' || !data.has_subscription;
                    
                    navSection.innerHTML = `
                        <div class="subscription-status">
                            <div class="subscription-info">
                                <div class="subscription-plan">${subscription.package_name} Plan</div>
                                <div class="subscription-status-text">${isFree ? 'Free Tier' : 'Active'}</div>
                            </div>
                            ${isFree ? 
                                '<a href="#pricing" class="upgrade-button">Upgrade</a>' :
                                '<a href="" class="upgrade-button">Manage</a>'
                            }
                        </div>
                    `;
                } else {
                    // Fallback if subscription check fails
                    navSection.innerHTML = `
                        <div class="subscription-status">
                            <div class="subscription-info">
                                <div class="subscription-plan">Free Plan</div>
                                <div class="subscription-status-text">Free Tier</div>
                            </div>
                            <a href="#pricing" class="upgrade-button">Upgrade</a>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error fetching subscription status:', error);
                // Fallback to upgrade button
                const navSection = document.getElementById('nav-auth-section');
                navSection.innerHTML = `
                    <div class="subscription-status">
                        <div class="subscription-info">
                            <div class="subscription-plan">Free Plan</div>
                            <div class="subscription-status-text">Free Tier</div>
                        </div>
                        <a href="#pricing" class="upgrade-button">Upgrade</a>
                    </div>
                `;
            }
        }
        
        // Function to update navigation for logged-out users
        function updateNavigationForLoggedOutUser() {
            const navSection = document.getElementById('nav-auth-section');
            navSection.innerHTML = '<a href="login.php" class="cta-button">Get Started</a>';
        }

    </script>

    <script>
        // FAQ Toggle Functionality
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const toggle = element.querySelector('.faq-toggle');
            
            if (answer.classList.contains('active')) {
                answer.classList.remove('active');
                toggle.classList.remove('active');
            } else {
                // Close all other FAQs
                document.querySelectorAll('.faq-answer').forEach(item => {
                    item.classList.remove('active');
                });
                document.querySelectorAll('.faq-toggle').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Open current FAQ
                answer.classList.add('active');
                toggle.classList.add('active');
            }
        }

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Header background on scroll
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
            }
        });

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                }
            });
        }, observerOptions);

        // Observe all feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            observer.observe(card);
        });

        // Video play button functionality
        document.querySelector('.play-button').addEventListener('click', function() {
            // This would open a video modal or redirect to a video
            Swal.fire({
                icon: 'info',
                title: 'Demo Coming Soon!',
                text: 'Video demo coming soon! For now, try our app by clicking "Get Started"',
                confirmButtonText: 'OK',
                confirmButtonColor: '#667eea'
            });
        });

        // Subscription functionality
        window.startSubscription = async function(packageSlug, billingCycle, buttonElement) {
            console.log('startSubscription called with:', packageSlug, billingCycle);
            console.log('window.auth available:', !!window.auth);
            console.log('window.auth object:', window.auth);
            
            // Wait a moment for auth to be available if it's not yet
            if (!window.auth) {
                console.log('Auth not immediately available, waiting 200ms...');
                await new Promise(resolve => setTimeout(resolve, 200));
                console.log('After wait - window.auth available:', !!window.auth);
            }
            
            // Check if auth is available
            if (!window.auth) {
                console.error('Auth not available after wait, redirecting to login');
                window.location.href = 'login.php';
                return;
            }
            
            // Check if user is authenticated
            const currentUser = window.auth.currentUser;
            console.log('Current user:', currentUser);
            
            if (!currentUser) {
                // User not authenticated, redirect to login
                console.log('User not authenticated, redirecting to login');
                window.location.href = 'login.php';
                return;
            }
            
            // User is authenticated, create Stripe checkout session
            const userId = currentUser.uid;
            const userEmail = currentUser.email;
            const userName = currentUser.displayName || currentUser.email;
            
            console.log('User authenticated, creating Stripe checkout session...');
            console.log('User details:', { userId, userEmail, userName });
            
            try {
                // Show loading state
                const button = buttonElement;
                const originalText = button.textContent;
                button.textContent = 'Creating checkout...';
                button.disabled = true;
                
                // Create checkout session
                const url = `create_direct_checkout.php?package=${encodeURIComponent(packageSlug)}&billing=${encodeURIComponent(billingCycle)}&user_email=${encodeURIComponent(userEmail)}&user_name=${encodeURIComponent(userName)}`;
                console.log('Making request to:', url);
                
                const response = await fetch(url);
                const data = await response.json();
                
                console.log('Response:', data);
                
                if (data.success) {
                    if (data.redirect_url) {
                        // Free plan - redirect to app
                        console.log('Free plan activated, redirecting to app');
                        window.location.href = data.redirect_url;
                    } else if (data.checkout_url) {
                        // Paid plan - redirect to Stripe checkout
                        console.log('Redirecting to Stripe checkout:', data.checkout_url);
                        window.location.href = data.checkout_url;
                    }
                } else {
                    console.error('Failed to create checkout session:', data.error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Checkout Error',
                        text: 'Error creating checkout session: ' + data.error,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc2626'
                    });
                    // Restore button
                    button.textContent = originalText;
                    button.disabled = false;
                }
            } catch (error) {
                console.error('Error creating checkout session:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Error creating checkout session. Please try again.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc2626'
                });
                // Restore button
                const button = buttonElement;
                button.textContent = originalText;
                button.disabled = false;
            }
        };
    </script>
</body>
</html>