import sys

def process_file(filepath, title, new_main_content):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    import re
    content = re.sub(r'<title>.*?</title>', f'<title>{title}</title>', content)
    
    start_marker = '<!-- ============ MAIN CONTACT HERO ============ -->'
    end_marker = '<!-- ============ FOOTER ============ -->'
    
    start_idx = content.find(start_marker)
    end_idx = content.find(end_marker)
    
    if start_idx != -1 and end_idx != -1:
        new_content = content[:start_idx] + new_main_content + '\n  ' + content[end_idx:]
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f'Successfully updated {filepath}')
    else:
        print(f'Could not find markers in {filepath}')

privacy_content = """<!-- ============ PRIVACY POLICY ============ -->
  <main class="page-content-section" style="padding-top: 130px; padding-bottom: 80px; min-height: 60vh;">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-10">
          <h1 class="fw-bold mb-4">Privacy Policy</h1>
          <p class="text-muted mb-5">Last updated: October 2026</p>

          <div class="policy-content bg-white p-5 rounded-4 shadow-sm border border-light">
            <h3 class="fw-bold mt-4 mb-3">1. Information We Collect</h3>
            <p>At InternBoot, we collect various types of information in connection with the services we provide, including:</p>
            <ul>
              <li><strong>Personal Information:</strong> Name, email address, phone number, academic records, and payment information.</li>
              <li><strong>Usage Data:</strong> Information on how you interact with our platform, such as your assessment scores and activity logs.</li>
            </ul>

            <h3 class="fw-bold mt-4 mb-3">2. How We Use Your Information</h3>
            <p>We use the information we collect to:</p>
            <ul>
              <li>Provide, maintain, and improve our services.</li>
              <li>Process transactions and send related information, including confirmations and invoices.</li>
              <li>Communicate with you about products, services, offers, and provide customer support.</li>
              <li>Share assessment scores with hiring partners, with your consent, to facilitate placement opportunities.</li>
            </ul>

            <h3 class="fw-bold mt-4 mb-3">3. Information Sharing and Disclosure</h3>
            <p>We may share your information with:</p>
            <ul>
              <li><strong>Hiring Partners:</strong> With your explicit consent, we share your assessment results and profile with verified hiring partners.</li>
              <li><strong>Service Providers:</strong> Third-party vendors who perform services on our behalf, such as payment processing and hosting.</li>
              <li><strong>Legal Requirements:</strong> When required by law or in response to valid requests by public authorities.</li>
            </ul>

            <h3 class="fw-bold mt-4 mb-3">4. Data Security</h3>
            <p>We implement robust security measures to protect your personal information. However, no method of transmission over the internet or electronic storage is 100% secure. We cannot guarantee absolute security but strive to use commercially acceptable means to protect your data.</p>

            <h3 class="fw-bold mt-4 mb-3">5. Your Rights</h3>
            <p>You have the right to access, update, or delete your personal information. You can manage your preferences by logging into your candidate dashboard or contacting our support team at <a href="mailto:support@internboot.com">support@internboot.com</a>.</p>

            <h3 class="fw-bold mt-4 mb-3">6. Changes to This Policy</h3>
            <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date. We encourage you to review this Privacy Policy periodically for any changes.</p>
          </div>
        </div>
      </div>
    </div>
  </main>"""

terms_content = """<!-- ============ TERMS OF SERVICE ============ -->
  <main class="page-content-section" style="padding-top: 130px; padding-bottom: 80px; min-height: 60vh;">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-10">
          <h1 class="fw-bold mb-4">Terms of Service</h1>
          <p class="text-muted mb-5">Last updated: October 2026</p>

          <div class="terms-content bg-white p-5 rounded-4 shadow-sm border border-light">
            <h3 class="fw-bold mt-4 mb-3">1. Acceptance of Terms</h3>
            <p>By accessing or using the InternBoot platform, you agree to be bound by these Terms of Service. If you disagree with any part of the terms, you may not access our services.</p>

            <h3 class="fw-bold mt-4 mb-3">2. Description of Service</h3>
            <p>InternBoot provides a standardized assessment platform for fresher talent and connects candidates with hiring partners. Our services include online examinations, scoring, and placement assistance for candidates who meet the required criteria.</p>

            <h3 class="fw-bold mt-4 mb-3">3. User Accounts</h3>
            <p>To access certain features, you must register for an account. You agree to provide accurate, current, and complete information during the registration process and to update such information to keep it accurate, current, and complete.</p>
            <p>You are responsible for safeguarding the password that you use to access the service and for any activities or actions under your password.</p>

            <h3 class="fw-bold mt-4 mb-3">4. Fees and Payment</h3>
            <p>Candidates must pay the required assessment fee to secure an exam slot. Fees are non-refundable except as expressly provided in our refund policy. Payment processing is handled by third-party providers, and you agree to their terms and conditions.</p>

            <h3 class="fw-bold mt-4 mb-3">5. Assessment and Certification</h3>
            <p>Assessments are conducted under strict proctoring conditions. Any attempt at academic dishonesty, including but not limited to cheating, plagiarism, or unauthorized assistance, will result in immediate disqualification and forfeiture of the assessment fee.</p>
            <p>Certificates are awarded based on performance. InternBoot makes no guarantee of employment or placement, although we actively facilitate connections with hiring partners for successful candidates.</p>

            <h3 class="fw-bold mt-4 mb-3">6. Intellectual Property</h3>
            <p>The Service and its original content, features, and functionality are and will remain the exclusive property of InternBoot and its licensors. The Service is protected by copyright, trademark, and other laws.</p>

            <h3 class="fw-bold mt-4 mb-3">7. Limitation of Liability</h3>
            <p>In no event shall InternBoot, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your access to or use of or inability to access or use the Service.</p>

            <h3 class="fw-bold mt-4 mb-3">8. Contact Us</h3>
            <p>If you have any questions about these Terms, please contact us at <a href="mailto:support@internboot.com">support@internboot.com</a>.</p>
          </div>
        </div>
      </div>
    </div>
  </main>"""

process_file('privacy.html', 'Privacy Policy | InternBoot', privacy_content)
process_file('terms.html', 'Terms of Service | InternBoot', terms_content)
