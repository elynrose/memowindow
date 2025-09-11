import React from 'react';
import { Link } from 'react-router-dom';
import Header from '../components/Header';
import './LegalPages.css';

const RefundPolicy = () => {
  return (
    <div className="legal-page">
      <Header />
      <div className="legal-content">
        <div className="container">
          <div className="legal-header">
            <h1>Refund Policy</h1>
            <p className="last-updated">Last updated: January 1, 2024</p>
          </div>

          <div className="legal-body">
            <section>
              <h2>1. Overview</h2>
              <p>
                At MemoWindow, we strive to provide high-quality services and products. This 
                refund policy outlines the circumstances under which refunds may be issued for 
                our subscription services and print orders.
              </p>
            </section>

            <section>
              <h2>2. Subscription Refunds</h2>
              <h3>2.1 Monthly Subscriptions</h3>
              <p>
                Monthly subscription fees are generally non-refundable. However, we may provide 
                refunds in the following circumstances:
              </p>
              <ul>
                <li>Technical issues that prevent you from using the service</li>
                <li>Billing errors on our part</li>
                <li>Service outages lasting more than 24 hours</li>
                <li>Duplicate charges due to system errors</li>
              </ul>

              <h3>2.2 Annual Subscriptions</h3>
              <p>
                Annual subscriptions may be eligible for a prorated refund if cancelled within 
                the first 30 days of the subscription period, minus any usage fees for premium 
                features already utilized.
              </p>
            </section>

            <section>
              <h2>3. Print Order Refunds</h2>
              <h3>3.1 Order Cancellation</h3>
              <p>
                You may cancel a print order within 24 hours of placement for a full refund. 
                Orders that have entered the production process cannot be cancelled.
              </p>

              <h3>3.2 Quality Issues</h3>
              <p>
                We guarantee the quality of our prints. If you receive a print that does not 
                meet our quality standards, we will provide a full refund or replacement. 
                Quality issues include:
              </p>
              <ul>
                <li>Blurry or distorted images</li>
                <li>Incorrect colors or poor color reproduction</li>
                <li>Physical damage during shipping</li>
                <li>Print errors or missing elements</li>
              </ul>

              <h3>3.3 Shipping Issues</h3>
              <p>
                If your order is lost or damaged during shipping, we will provide a full refund 
                or replacement at no additional cost. You must report shipping issues within 
                7 days of the estimated delivery date.
              </p>
            </section>

            <section>
              <h2>4. Refund Process</h2>
              <h3>4.1 How to Request a Refund</h3>
              <p>To request a refund:</p>
              <ol>
                <li>Contact our support team at support@memowindow.com</li>
                <li>Provide your order number or account information</li>
                <li>Explain the reason for your refund request</li>
                <li>Include any relevant photos or documentation</li>
              </ol>

              <h3>4.2 Refund Review Process</h3>
              <p>
                All refund requests are reviewed by our support team within 2-3 business days. 
                We may request additional information or documentation to process your request.
              </p>

              <h3>4.3 Refund Processing Time</h3>
              <p>
                Approved refunds are typically processed within 5-10 business days. The time 
                for the refund to appear in your account depends on your payment method and 
                financial institution.
              </p>
            </section>

            <section>
              <h2>5. Non-Refundable Items</h2>
              <p>The following items are generally non-refundable:</p>
              <ul>
                <li>Digital downloads and generated content</li>
                <li>Voice cloning credits that have been used</li>
                <li>Custom orders that have been personalized</li>
                <li>Orders cancelled after the 24-hour window</li>
                <li>Subscription fees for services already used</li>
              </ul>
            </section>

            <section>
              <h2>6. Chargebacks and Disputes</h2>
              <p>
                If you initiate a chargeback or dispute with your bank or credit card company, 
                we may suspend your account until the dispute is resolved. We encourage you to 
                contact us directly to resolve any issues before initiating a chargeback.
              </p>
            </section>

            <section>
              <h2>7. Special Circumstances</h2>
              <h3>7.1 Service Discontinuation</h3>
              <p>
                If we discontinue a service you have paid for, we will provide a prorated 
                refund for the unused portion of your subscription.
              </p>

              <h3>7.2 Force Majeure</h3>
              <p>
                In cases of force majeure (natural disasters, pandemics, etc.) that prevent 
                order fulfillment, we will work with customers to provide appropriate solutions, 
                which may include refunds or extended delivery times.
              </p>
            </section>

            <section>
              <h2>8. Refund Methods</h2>
              <p>
                Refunds are issued using the same payment method used for the original purchase. 
                If the original payment method is no longer available, we will work with you to 
                find an alternative refund method.
              </p>
            </section>

            <section>
              <h2>9. Customer Satisfaction</h2>
              <p>
                Our goal is customer satisfaction. If you are not completely satisfied with 
                our service or products, please contact us. We will work with you to find a 
                solution that meets your needs.
              </p>
            </section>

            <section>
              <h2>10. Policy Changes</h2>
              <p>
                We reserve the right to modify this refund policy at any time. Changes will 
                be posted on this page with an updated "Last updated" date. Continued use of 
                our service after changes constitutes acceptance of the new policy.
              </p>
            </section>

            <section>
              <h2>11. Contact Information</h2>
              <p>
                For refund requests or questions about this policy, please contact us:
              </p>
              <div className="contact-info">
                <p><strong>Email:</strong> support@memowindow.com</p>
                <p><strong>Phone:</strong> 1-800-MEMOWINDOW</p>
                <p><strong>Address:</strong> MemoWindow, Inc.<br />
                123 Memory Lane<br />
                San Francisco, CA 94105</p>
                <p><strong>Business Hours:</strong> Monday - Friday, 9 AM - 6 PM PST</p>
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

export default RefundPolicy;