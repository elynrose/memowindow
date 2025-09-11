import React from 'react';
import { Link } from 'react-router-dom';
import Header from '../components/Header';
import './LegalPages.css';

const TermsOfService = () => {
  return (
    <div className="legal-page">
      <Header />
      <div className="legal-content">
        <div className="container">
          <div className="legal-header">
            <h1>Terms of Service</h1>
            <p className="last-updated">Last updated: January 1, 2024</p>
          </div>

          <div className="legal-body">
            <section>
              <h2>1. Acceptance of Terms</h2>
              <p>
                By accessing and using MemoWindow ("the Service"), you accept and agree to be bound 
                by the terms and provision of this agreement. If you do not agree to abide by the 
                above, please do not use this service.
              </p>
            </section>

            <section>
              <h2>2. Description of Service</h2>
              <p>
                MemoWindow is a service that allows users to transform voice recordings into 
                beautiful waveform art. Our service includes:
              </p>
              <ul>
                <li>Audio file upload and processing</li>
                <li>Waveform generation and visualization</li>
                <li>Memory creation and management</li>
                <li>Print ordering and fulfillment</li>
                <li>Voice cloning features (premium plans)</li>
              </ul>
            </section>

            <section>
              <h2>3. User Accounts</h2>
              <p>
                To use certain features of our service, you must create an account. You are 
                responsible for:
              </p>
              <ul>
                <li>Maintaining the confidentiality of your account credentials</li>
                <li>All activities that occur under your account</li>
                <li>Providing accurate and complete information</li>
                <li>Notifying us immediately of any unauthorized use</li>
              </ul>
            </section>

            <section>
              <h2>4. Acceptable Use</h2>
              <p>You agree not to use the service to:</p>
              <ul>
                <li>Upload content that violates any laws or regulations</li>
                <li>Infringe on intellectual property rights of others</li>
                <li>Upload malicious software or harmful content</li>
                <li>Attempt to gain unauthorized access to our systems</li>
                <li>Use the service for any illegal or unauthorized purpose</li>
                <li>Interfere with or disrupt the service or servers</li>
              </ul>
            </section>

            <section>
              <h2>5. Content and Intellectual Property</h2>
              <p>
                <strong>Your Content:</strong> You retain ownership of all audio files and content 
                you upload. By uploading content, you grant us a license to process, store, and 
                display your content as necessary to provide our services.
              </p>
              <p>
                <strong>Our Content:</strong> The MemoWindow service, including its design, 
                functionality, and software, is owned by us and protected by intellectual 
                property laws.
              </p>
            </section>

            <section>
              <h2>6. Subscription Plans and Billing</h2>
              <p>
                We offer various subscription plans with different features and limits:
              </p>
              <ul>
                <li><strong>Free Plan:</strong> Limited to 3 memories, 1-year retention</li>
                <li><strong>Standard Plan:</strong> 30 memories, voice cloning features</li>
                <li><strong>Premium Plan:</strong> Unlimited memories, advanced features</li>
              </ul>
              <p>
                Subscription fees are billed in advance and are non-refundable except as 
                required by law. You may cancel your subscription at any time.
              </p>
            </section>

            <section>
              <h2>7. Print Orders and Fulfillment</h2>
              <p>
                When you place a print order:
              </p>
              <ul>
                <li>Orders are processed within 1-2 business days</li>
                <li>Shipping typically takes 3-5 business days</li>
                <li>We reserve the right to refuse orders that violate our terms</li>
                <li>Print quality is guaranteed to meet our standards</li>
              </ul>
            </section>

            <section>
              <h2>8. Privacy and Data Protection</h2>
              <p>
                Your privacy is important to us. Please review our Privacy Policy to understand 
                how we collect, use, and protect your information. By using our service, you 
                consent to the collection and use of information as described in our Privacy Policy.
              </p>
            </section>

            <section>
              <h2>9. Service Availability</h2>
              <p>
                We strive to maintain high service availability but cannot guarantee uninterrupted 
                access. We may temporarily suspend the service for maintenance, updates, or other 
                operational reasons.
              </p>
            </section>

            <section>
              <h2>10. Limitation of Liability</h2>
              <p>
                To the maximum extent permitted by law, MemoWindow shall not be liable for any 
                indirect, incidental, special, consequential, or punitive damages, including but 
                not limited to loss of profits, data, or use, arising out of or relating to your 
                use of the service.
              </p>
            </section>

            <section>
              <h2>11. Indemnification</h2>
              <p>
                You agree to indemnify and hold harmless MemoWindow from any claims, damages, 
                or expenses arising from your use of the service or violation of these terms.
              </p>
            </section>

            <section>
              <h2>12. Termination</h2>
              <p>
                We may terminate or suspend your account and access to the service immediately, 
                without prior notice, for any reason, including breach of these terms. Upon 
                termination, your right to use the service will cease immediately.
              </p>
            </section>

            <section>
              <h2>13. Governing Law</h2>
              <p>
                These terms shall be governed by and construed in accordance with the laws of 
                the State of California, without regard to conflict of law principles.
              </p>
            </section>

            <section>
              <h2>14. Changes to Terms</h2>
              <p>
                We reserve the right to modify these terms at any time. We will notify users 
                of material changes via email or through the service. Continued use of the 
                service after changes constitutes acceptance of the new terms.
              </p>
            </section>

            <section>
              <h2>15. Contact Information</h2>
              <p>
                If you have any questions about these terms, please contact us at:
              </p>
              <div className="contact-info">
                <p><strong>Email:</strong> legal@memowindow.com</p>
                <p><strong>Address:</strong> MemoWindow, Inc.<br />
                123 Memory Lane<br />
                San Francisco, CA 94105</p>
              </div>
            </section>
          </div>

          <div className="legal-footer">
            <Link to="/" className="btn btn-primary">Back to MemoWindow</Link>
          </div>
        </div>
      </div>
    </div>
  );
};

export default TermsOfService;