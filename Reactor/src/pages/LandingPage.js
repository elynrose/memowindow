import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import Header from '../components/Header';
import './LandingPage.css';

const LandingPage = () => {
  const [activeFAQ, setActiveFAQ] = useState(null);

  const toggleFAQ = (index) => {
    setActiveFAQ(activeFAQ === index ? null : index);
  };

  const faqs = [
    {
      question: "How does MemoWindow work?",
      answer: "Simply upload your audio file, and our advanced technology will analyze the waveform and create a beautiful visual representation. You can then customize the design and order a high-quality print."
    },
    {
      question: "What audio formats do you support?",
      answer: "We support all major audio formats including MP3, WAV, M4A, and more. For best results, we recommend high-quality recordings with minimal background noise."
    },
    {
      question: "How long does processing take?",
      answer: "Processing typically takes just a few minutes. Once your waveform is ready, you can preview it and make any adjustments before ordering your print."
    },
    {
      question: "What's included with my order?",
      answer: "Your order includes a high-quality print on premium paper, ready for framing. We also provide a digital copy of your waveform for your records."
    },
    {
      question: "How long does shipping take?",
      answer: "Standard shipping takes 3-5 business days. We also offer expedited shipping options for faster delivery."
    },
    {
      question: "What is voice cloning and how does it work?",
      answer: "Voice cloning allows you to create a digital copy of a voice from your audio memories. Using AI technology, you can then generate new audio content in that voice, perfect for creating additional memories or messages."
    },
    {
      question: "How do subscriptions work?",
      answer: "We offer three plans: Basic (free with 3 memories), Standard ($9.99/month with 30 memories and 1 voice clone), and Premium ($19.99/month with unlimited memories and 10 voice clones). You can upgrade or downgrade anytime."
    },
    {
      question: "Can I make changes after ordering?",
      answer: "Changes can be made before your order goes to print. Once printing begins, changes cannot be made, but you can always create a new design with modifications."
    }
  ];

  return (
    <div className="landing-page">
      <Header />
      
      {/* Hero Section */}
      <section className="hero">
        <div className="hero-content">
          <h1>Transform Voice into Beautiful Waveform Art</h1>
          <p>Turn your precious voice recordings into stunning visual memories. Create unique waveform prints that capture the essence of your most meaningful moments.</p>
          <div className="hero-buttons">
            <Link to="/login" className="btn-primary">Start Creating</Link>
            <a href="#video" className="btn-secondary">Watch Demo</a>
          </div>
        </div>
      </section>

      {/* Intro Section */}
      <section className="intro">
        <div className="container">
          <h2 className="section-title">Why MemoWindow?</h2>
          <p className="section-subtitle">Every voice tells a story. We help you preserve those stories in beautiful, tangible form.</p>
          
          <div className="intro-content">
            <div className="intro-text">
              <h3>Preserve Your Most Precious Moments</h3>
              <p>Whether it's a loved one's voice, a child's first words, or a special message, MemoWindow transforms audio into stunning visual art that you can hold, frame, and treasure forever.</p>
              <p>Our advanced waveform technology captures every nuance of the audio, creating unique patterns that are as individual as the voice itself.</p>
            </div>
            <div className="intro-image">
              <div>Waveform Preview</div>
            </div>
          </div>
        </div>
      </section>

      {/* Video Section */}
      <section className="video-section" id="video">
        <div className="container">
          <h2 className="section-title">See MemoWindow in Action</h2>
          <p className="section-subtitle">Watch how easy it is to transform your voice recordings into beautiful artwork</p>
          
          <div className="video-container">
            <div className="video-placeholder">
              <div className="play-button">
                ▶
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="features" id="features">
        <div className="container">
          <h2 className="section-title">Powerful Features</h2>
          <p className="section-subtitle">Everything you need to create stunning waveform art</p>
          
          <div className="features-grid">
            <div className="feature-card fade-in-up">
              <div className="feature-icon">🎵</div>
              <h3>High-Quality Audio Processing</h3>
              <p>Advanced algorithms capture every detail of your audio, creating precise and beautiful waveform patterns.</p>
            </div>
            
            <div className="feature-card fade-in-up">
              <div className="feature-icon">🎨</div>
              <h3>Customizable Designs</h3>
              <p>Choose from multiple sizes, colors, and styles to create the perfect piece for your space.</p>
            </div>
            
            <div className="feature-card fade-in-up">
              <div className="feature-icon">🖼️</div>
              <h3>Premium Print Quality</h3>
              <p>Professional-grade printing on high-quality materials ensures your artwork looks stunning for years to come.</p>
            </div>
            
            <div className="feature-card fade-in-up">
              <div className="feature-icon">📱</div>
              <h3>Easy Upload & Processing</h3>
              <p>Simply upload your audio file and watch as we transform it into beautiful waveform art in minutes.</p>
            </div>
            
            <div className="feature-card fade-in-up">
              <div className="feature-icon">🚚</div>
              <h3>Fast Shipping</h3>
              <p>Quick turnaround times mean you can have your custom artwork delivered to your door in days, not weeks.</p>
            </div>
            
            <div className="feature-card fade-in-up">
              <div className="feature-icon">🎤</div>
              <h3>Voice Cloning Technology</h3>
              <p>Clone voices from your memories and generate new audio content using AI-powered voice synthesis.</p>
            </div>
            
            <div className="feature-card fade-in-up">
              <div className="feature-icon">💝</div>
              <h3>Perfect for Gifts</h3>
              <p>Create meaningful, personalized gifts that will be treasured for a lifetime.</p>
            </div>
          </div>
        </div>
      </section>

      {/* Pricing Section */}
      <section className="pricing" id="pricing">
        <div className="container">
          <h2 className="section-title">Choose Your Plan</h2>
          <p className="section-subtitle">Unlock the full potential of MemoWindow with our flexible subscription plans</p>
          
          <div className="pricing-grid">
            <div className="pricing-card">
              <h3>Basic</h3>
              <div className="price">Free</div>
              <div className="price-period">forever</div>
              <ul className="pricing-features">
                <li>Generate up to 3 memories</li>
                <li>Memories available for 1 year</li>
                <li>Basic support</li>
                <li>Standard quality audio</li>
              </ul>
              <Link to="/login" className="cta-button">Get Started Free</Link>
            </div>
            
            <div className="pricing-card featured">
              <h3>Standard</h3>
              <div className="price">$9.99</div>
              <div className="price-period">per month</div>
              <ul className="pricing-features">
                <li>Generate up to 30 memories</li>
                <li>Memories available as long as subscribed</li>
                <li>Generate 1 voice clone + messages</li>
                <li>Priority support</li>
                <li>High quality audio</li>
              </ul>
              <Link to="/login" className="cta-button">Start Standard Plan</Link>
            </div>
            
            <div className="pricing-card">
              <h3>Premium</h3>
              <div className="price">$19.99</div>
              <div className="price-period">per month</div>
              <ul className="pricing-features">
                <li>Unlimited memories</li>
                <li>Generate 10 voice clones + messages</li>
                <li>Memories never expire</li>
                <li>Premium support</li>
                <li>Highest quality audio</li>
                <li>Advanced features</li>
              </ul>
              <Link to="/login" className="cta-button">Start Premium Plan</Link>
            </div>
          </div>
        </div>
      </section>

      {/* FAQ Section */}
      <section className="faq" id="faq">
        <div className="container">
          <h2 className="section-title">Frequently Asked Questions</h2>
          <p className="section-subtitle">Everything you need to know about MemoWindow</p>
          
          <div className="faq-container">
            {faqs.map((faq, index) => (
              <div key={index} className="faq-item">
                <div 
                  className="faq-question" 
                  onClick={() => toggleFAQ(index)}
                >
                  {faq.question}
                  <span className={`faq-toggle ${activeFAQ === index ? 'active' : ''}`}>+</span>
                </div>
                <div className={`faq-answer ${activeFAQ === index ? 'active' : ''}`}>
                  {faq.answer}
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="footer" id="contact">
        <div className="container">
          <div className="footer-content">
            <div className="footer-section">
              <h3>MemoWindow</h3>
              <p>Transforming voice recordings into beautiful waveform art. Preserve your most precious moments in stunning visual form.</p>
            </div>
            
            <div className="footer-section">
              <h3>Quick Links</h3>
              <p><a href="#features">Features</a></p>
              <p><a href="#pricing">Pricing</a></p>
              <p><a href="#faq">FAQ</a></p>
              <p><Link to="/login">Get Started</Link></p>
            </div>
            
            <div className="footer-section">
              <h3>Support</h3>
              <p><a href="mailto:support@memorywindow.com">support@memorywindow.com</a></p>
              <p><a href="tel:+1-555-0123">+1 (555) 012-3456</a></p>
              <p>Mon-Fri 9AM-6PM EST</p>
            </div>
            
            <div className="footer-section">
              <h3>Legal</h3>
              <p><a href="/privacy-policy">Privacy Policy</a></p>
              <p><a href="/terms-of-service">Terms of Service</a></p>
              <p><a href="/refund-policy">Refund Policy</a></p>
            </div>
          </div>
          
          <div className="footer-bottom">
            <p>&copy; 2024 MemoWindow. All rights reserved. Made with ❤️ for preserving memories.</p>
          </div>
        </div>
      </footer>
    </div>
  );
};

export default LandingPage;