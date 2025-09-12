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
    
    <!-- Three.js for 3D sound wave animation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    
    <!-- Unified Styles - Load after inline styles to override -->
    <link rel="stylesheet" href="includes/unified.css?v=<?php echo time(); ?>">
</head>
<body class="home-page">
    <?php include 'includes/navigation.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <!-- 3D Sound Wave Canvas -->
        <canvas id="sound-wave-canvas" class="sound-wave-canvas"></canvas>
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
        
        // 3D Sound Wave Animation
        function initSoundWaveAnimation() {
            const canvas = document.getElementById('sound-wave-canvas');
            if (!canvas) return;
            
            // Scene setup
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ 
                canvas: canvas, 
                alpha: true, 
                antialias: true 
            });
            
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setClearColor(0x000000, 0); // Transparent background
            
            // Create realistic sound wave geometry
            const waveCount = 3; // Fewer, more detailed waves
            const waves = [];
            const colors = [0xffffff, 0xf8f8f8, 0xf0f0f0];
            
            for (let i = 0; i < waveCount; i++) {
                // Create detailed waveform geometry
                const segments = 200; // More segments for detailed waveform
                const geometry = new THREE.BufferGeometry();
                
                const positions = [];
                const indices = [];
                
                // Generate waveform vertices
                for (let j = 0; j <= segments; j++) {
                    const x = (j / segments) * 40 - 20; // Spread across width
                    const y = 0;
                    const z = 0;
                    positions.push(x, y, z);
                }
                
                // Create triangles for the waveform
                for (let j = 0; j < segments; j++) {
                    const base = j * 2;
                    indices.push(base, base + 1, base + 2);
                    indices.push(base + 1, base + 3, base + 2);
                }
                
                geometry.setIndex(indices);
                geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
                
                const material = new THREE.MeshBasicMaterial({ 
                    color: colors[i % colors.length],
                    transparent: true,
                    opacity: 0.6 - (i * 0.1),
                    wireframe: false
                });
                
                const wave = new THREE.Mesh(geometry, material);
                wave.position.z = -5 - (i * 2);
                wave.rotation.x = Math.PI / 2;
                
                // Store original positions for animation
                wave.userData = {
                    originalPositions: [...positions],
                    segments: segments
                };
                
                waves.push(wave);
                scene.add(wave);
            }
            
            // Add particle system for granular effect
            const particleCount = 1000;
            const particles = new THREE.BufferGeometry();
            const particlePositions = new Float32Array(particleCount * 3);
            const particleColors = new Float32Array(particleCount * 3);
            
            for (let i = 0; i < particleCount; i++) {
                const i3 = i * 3;
                particlePositions[i3] = (Math.random() - 0.5) * 40; // x
                particlePositions[i3 + 1] = (Math.random() - 0.5) * 10; // y
                particlePositions[i3 + 2] = (Math.random() - 0.5) * 10; // z
                
                // White particles
                particleColors[i3] = 1; // r
                particleColors[i3 + 1] = 1; // g
                particleColors[i3 + 2] = 1; // b
            }
            
            particles.setAttribute('position', new THREE.BufferAttribute(particlePositions, 3));
            particles.setAttribute('color', new THREE.BufferAttribute(particleColors, 3));
            
            const particleMaterial = new THREE.PointsMaterial({
                size: 0.5,
                transparent: true,
                opacity: 0.3,
                vertexColors: true
            });
            
            const particleSystem = new THREE.Points(particles, particleMaterial);
            scene.add(particleSystem);
            
            // Camera position
            camera.position.z = 8;
            camera.position.y = 0;
            camera.position.x = 0;
            
            // Animation variables
            let time = 0;
            const waveSpeed = 0.03;
            
            // Animation loop
            function animate() {
                requestAnimationFrame(animate);
                
                time += waveSpeed;
                
                // Animate each wave with realistic sound wave patterns
                waves.forEach((wave, index) => {
                    const positions = wave.geometry.attributes.position.array;
                    const originalPositions = wave.userData.originalPositions;
                    const segments = wave.userData.segments;
                    
                    for (let j = 0; j <= segments; j++) {
                        const i = j * 3;
                        const x = originalPositions[i];
                        
                        // Create realistic sound wave pattern
                        const waveOffset = index * 0.3;
                        const frequency1 = 0.1 + (index * 0.05);
                        const frequency2 = 0.3 + (index * 0.1);
                        const frequency3 = 0.7 + (index * 0.2);
                        
                        // Multiple sine waves for complex pattern
                        const wave1 = Math.sin((time + x * frequency1) + waveOffset) * 2;
                        const wave2 = Math.sin((time * 1.5 + x * frequency2) + waveOffset) * 1;
                        const wave3 = Math.sin((time * 2 + x * frequency3) + waveOffset) * 0.5;
                        
                        // Add some noise for granular effect
                        const noise = (Math.random() - 0.5) * 0.3;
                        
                        const y = wave1 + wave2 + wave3 + noise;
                        positions[i + 1] = y;
                    }
                    
                    wave.geometry.attributes.position.needsUpdate = true;
                });
                
                // Animate particles
                const particlePositions = particleSystem.geometry.attributes.position.array;
                for (let i = 0; i < particleCount; i++) {
                    const i3 = i * 3;
                    
                    // Move particles in wave-like motion
                    const x = particlePositions[i3];
                    const waveOffset = i * 0.01;
                    const waveHeight = Math.sin(time + x * 0.1 + waveOffset) * 2;
                    
                    particlePositions[i3 + 1] = waveHeight + (Math.random() - 0.5) * 0.5;
                }
                particleSystem.geometry.attributes.position.needsUpdate = true;
                
                // Subtle camera movement
                camera.position.x = Math.sin(time * 0.05) * 1;
                camera.position.y = Math.cos(time * 0.08) * 0.5;
                camera.lookAt(0, 0, -5);
                
                renderer.render(scene, camera);
            }
            
            // Handle window resize
            function onWindowResize() {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            }
            
            window.addEventListener('resize', onWindowResize);
            
            // Start animation
            animate();
            
            console.log('🎵 Realistic 3D Sound Wave animation initialized');
        }
        
        // Make auth globally available after a short delay to ensure initialization
        setTimeout(() => {
            window.auth = auth;
            console.log('Auth object made globally available:', window.auth);
            
            // Initialize navigation
            initNavigation();
            
            // Initialize 3D sound wave animation
            initSoundWaveAnimation();
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
                // Show hamburger menu button when user is logged in
                const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
                if (mobileMenuToggle) {
                    mobileMenuToggle.classList.remove('hidden');
                    console.log('✅ Hamburger menu button shown for logged-in user');
                } else {
                    console.error('❌ Hamburger menu button not found');
                }
                
                // Get user's subscription status
                const response = await fetch(`get_user_subscription.php?user_id=${encodeURIComponent(user.uid)}`);
                const data = await response.json();
                
                const navSection = document.getElementById('userInfo');
                
                if (data.success) {
                    const subscription = data.subscription;
                    const isFree = subscription.package_slug === 'free' || !data.has_subscription;
                    
                    navSection.innerHTML = `
                        <a href="memories.php" class="header-link">My Memories</a>
                        <a href="orders.php" class="header-link">My Orders</a>
                        <a href="subscription_management.php" class="header-link">Manage Subscription</a>
                        <div class="subscription-status">
                            <div class="subscription-info">
                                <div class="subscription-plan">${subscription.package_name} Plan</div>
                                <div class="subscription-status-text">${isFree ? 'Free Tier' : 'Active'}</div>
                            </div>
                            ${isFree ? 
                                '<a href="#pricing" class="upgrade-button">Upgrade</a>' :
                                '<a href="subscription_checkout.php?user_id=' + encodeURIComponent(user.uid) + '" class="upgrade-button">Manage</a>'
                            }
                        </div>
                        <div class="user-profile">
                            <img class="user-avatar" src="${user.photoURL || 'images/default-avatar.png'}" alt="User avatar">
                            <div class="user-details">
                                <span>${user.displayName || user.email || 'User'}</span>
                                <div class="user-submenu">
                                    <a href="#" id="btnLogout" class="header-link submenu-link">Sign Out</a>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    // Fallback if subscription check fails
                    navSection.innerHTML = `
                        <a href="memories.php" class="header-link">My Memories</a>
                        <a href="orders.php" class="header-link">My Orders</a>
                        <a href="subscription_management.php" class="header-link">Manage Subscription</a>
                        <div class="subscription-status">
                            <div class="subscription-info">
                                <div class="subscription-plan">Free Plan</div>
                                <div class="subscription-status-text">Free Tier</div>
                            </div>
                            <a href="#pricing" class="upgrade-button">Upgrade</a>
                        </div>
                        <div class="user-profile">
                            <img class="user-avatar" src="${user.photoURL || 'images/default-avatar.png'}" alt="User avatar">
                            <div class="user-details">
                                <span>${user.displayName || user.email || 'User'}</span>
                                <div class="user-submenu">
                                    <a href="#" id="btnLogout" class="header-link submenu-link">Sign Out</a>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                // Add logout event listener
                const logoutBtn = document.getElementById('btnLogout');
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', async (e) => {
                        e.preventDefault();
                        try {
                            console.log('🚪 Logging out...');
                            const response = await fetch('logout.php', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
                            const result = await response.json();
                            console.log('🔍 Server logout response:', result);
                            await auth.signOut();
                            console.log('✅ Firebase sign out successful');
                            sessionStorage.removeItem('currentUser');
                            localStorage.removeItem('currentUser');
                            window.location.href = 'login.php';
                        } catch (error) {
                            console.error('❌ Logout failed:', error);
                            window.location.href = 'login.php';
                        }
                    });
                }
            } catch (error) {
                console.error('Error fetching subscription status:', error);
                // Fallback to upgrade button
                const navSection = document.getElementById('userInfo');
                navSection.innerHTML = `
                    <a href="memories.php" class="header-link">My Memories</a>
                    <a href="orders.php" class="header-link">My Orders</a>
                    <a href="subscription_management.php" class="header-link">Manage Subscription</a>
                    <div class="subscription-status">
                        <div class="subscription-info">
                            <div class="subscription-plan">Free Plan</div>
                            <div class="subscription-status-text">Free Tier</div>
                        </div>
                        <a href="#pricing" class="upgrade-button">Upgrade</a>
                    </div>
                    <div class="user-profile">
                        <img class="user-avatar" src="${user.photoURL || 'images/default-avatar.png'}" alt="User avatar">
                        <div class="user-details">
                            <span>${user.displayName || user.email || 'User'}</span>
                            <div class="user-submenu">
                                <a href="#" id="btnLogout" class="header-link submenu-link">Sign Out</a>
                            </div>
                        </div>
                    </div>
                `;
                
                // Add logout event listener for error fallback
                const logoutBtn = document.getElementById('btnLogout');
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', async (e) => {
                        e.preventDefault();
                        try {
                            console.log('🚪 Logging out...');
                            const response = await fetch('logout.php', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
                            const result = await response.json();
                            console.log('🔍 Server logout response:', result);
                            await auth.signOut();
                            console.log('✅ Firebase sign out successful');
                            sessionStorage.removeItem('currentUser');
                            localStorage.removeItem('currentUser');
                            window.location.href = 'login.php';
                        } catch (error) {
                            console.error('❌ Logout failed:', error);
                            window.location.href = 'login.php';
                        }
                    });
                }
            }
        }
        
        // Function to update navigation for logged-out users
        function updateNavigationForLoggedOutUser() {
            // Hide hamburger menu button when user is logged out
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            if (mobileMenuToggle) {
                mobileMenuToggle.classList.add('hidden');
                console.log('✅ Hamburger menu button hidden for logged-out user');
            }
            
            const navSection = document.getElementById('userInfo');
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
                const url = `create_direct_checkout.php?package=${encodeURIComponent(packageSlug)}&billing=${encodeURIComponent(billingCycle)}&user_id=${encodeURIComponent(userId)}&user_email=${encodeURIComponent(userEmail)}&user_name=${encodeURIComponent(userName)}`;
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