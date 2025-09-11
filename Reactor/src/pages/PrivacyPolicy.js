import React from 'react';
import { Link } from 'react-router-dom';
import Header from '../components/Header';
import './LegalPages.css';

const PrivacyPolicy = () => {
  return (
    <div className="legal-page">
      <Header />
      <div className="legal-content">
        <div className="container">
          <div className="legal-header">
            <h1>Privacy Policy</h1>
            <p className="last-updated">Last updated: January 1, 2024</p>
          </div>

          <div className="legal-body">
            <section>
              <h2>1. Information We Collect</h2>
              <p>
                We collect information you provide directly to us, such as when you create an account, 
                upload audio files, create memories, or contact us for support.
              </p>
              <ul>
                <li><strong>Account Information:</strong> Name, email address, and profile information</li>
                <li><strong>Audio Files:</strong> Voice recordings and audio files you upload to create memories</li>
                <li><strong>Memory Data:</strong> Titles, descriptions, and metadata associated with your memories</li>
                <li><strong>Payment Information:</strong> Billing address and payment method details (processed securely by Stripe)</li>
                <li><strong>Usage Data:</strong> Information about how you use our service</li>
              </ul>
            </section>

            <section>
              <h2>2. How We Use Your Information</h2>
              <p>We use the information we collect to:</p>
              <ul>
                <li>Provide, maintain, and improve our services</li>
                <li>Process your orders and payments</li>
                <li>Generate waveform visualizations from your audio files</li>
                <li>Send you technical notices and support messages</li>
                <li>Respond to your comments and questions</li>
                <li>Monitor and analyze usage and trends</li>
                <li>Detect, investigate, and prevent fraudulent transactions</li>
              </ul>
            </section>

            <section>
              <h2>3. Information Sharing and Disclosure</h2>
              <p>
                We do not sell, trade, or otherwise transfer your personal information to third parties, 
                except in the following circumstances:
              </p>
              <ul>
                <li><strong>Service Providers:</strong> We may share information with trusted third parties who assist us in operating our service</li>
                <li><strong>Legal Requirements:</strong> We may disclose information if required by law or to protect our rights</li>
                <li><strong>Business Transfers:</strong> In the event of a merger or acquisition, user information may be transferred</li>
                <li><strong>Consent:</strong> We may share information with your explicit consent</li>
              </ul>
            </section>

            <section>
              <h2>4. Data Security</h2>
              <p>
                We implement appropriate security measures to protect your personal information against 
                unauthorized access, alteration, disclosure, or destruction. This includes:
              </p>
              <ul>
                <li>Encryption of data in transit and at rest</li>
                <li>Regular security assessments and updates</li>
                <li>Access controls and authentication</li>
                <li>Secure payment processing through Stripe</li>
              </ul>
            </section>

            <section>
              <h2>5. Data Retention</h2>
              <p>
                We retain your personal information for as long as necessary to provide our services 
                and fulfill the purposes outlined in this privacy policy. Audio files and memories 
                are retained according to your subscription plan:
              </p>
              <ul>
                <li><strong>Free Plan:</strong> Memories are retained for 1 year</li>
                <li><strong>Paid Plans:</strong> Memories are retained as long as your subscription is active</li>
                <li><strong>Account Deletion:</strong> All data is permanently deleted within 30 days</li>
              </ul>
            </section>

            <section>
              <h2>6. Your Rights and Choices</h2>
              <p>You have the right to:</p>
              <ul>
                <li>Access and update your personal information</li>
                <li>Delete your account and associated data</li>
                <li>Download your memories and audio files</li>
                <li>Opt out of marketing communications</li>
                <li>Request data portability</li>
                <li>Object to certain processing activities</li>
              </ul>
            </section>

            <section>
              <h2>7. Cookies and Tracking</h2>
              <p>
                We use cookies and similar technologies to enhance your experience, analyze usage, 
                and provide personalized content. You can control cookie settings through your browser.
              </p>
            </section>

            <section>
              <h2>8. Children's Privacy</h2>
              <p>
                Our service is not intended for children under 13. We do not knowingly collect 
                personal information from children under 13. If you become aware that a child 
                has provided us with personal information, please contact us.
              </p>
            </section>

            <section>
              <h2>9. International Data Transfers</h2>
              <p>
                Your information may be transferred to and processed in countries other than your 
                own. We ensure appropriate safeguards are in place to protect your data in accordance 
                with applicable privacy laws.
              </p>
            </section>

            <section>
              <h2>10. Changes to This Policy</h2>
              <p>
                We may update this privacy policy from time to time. We will notify you of any 
                material changes by posting the new policy on this page and updating the "Last updated" date.
              </p>
            </section>

            <section>
              <h2>11. Contact Us</h2>
              <p>
                If you have any questions about this privacy policy or our data practices, 
                please contact us at:
              </p>
              <div className="contact-info">
                <p><strong>Email:</strong> privacy@memowindow.com</p>
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

export default PrivacyPolicy;