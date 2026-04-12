<script setup>
// Page: PrivacyPolicyPage displays the GDPR-aligned privacy policy for Visual Shield.
import PageTemplate from '@/components/templates/PageTemplate.vue'
</script>

<template>
  <PageTemplate title="Privacy Policy" maxWidth="max-w-3xl">

    <p class="text-xs text-muted -mt-4 mb-8">Last updated: April 2026 &middot; Version 1.0</p>

    <p class="text-body text-sm leading-relaxed mb-10">
      This policy applies to deployed instances of Visual Shield that are accessible to users.
      If you are running Visual Shield locally for personal development or testing, this policy
      does not apply to you.
    </p>

    <div class="space-y-10">

      <!-- 1. Who we are -->
      <section>
        <h2 class="text-xl font-semibold text-heading mb-3">1. Who we are</h2>
        <p class="text-body text-sm leading-relaxed">
          Visual Shield is a web application that analyses uploaded video files for visual
          accessibility risks such as flashing, luminance changes, and motion intensity. It is not a
          medical service and does not provide medical advice or diagnosis.
        </p>
      </section>

      <!-- 2. What data we collect -->
      <section>
        <h2 class="text-xl font-semibold text-heading mb-3">2. What data we collect</h2>

        <h3 class="text-base font-semibold text-heading mb-2">2.1 Account data</h3>
        <p class="text-body text-sm leading-relaxed mb-3">When you register an account, we collect:</p>
        <div class="overflow-x-auto rounded-xl border border-line mb-4">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-line text-left text-muted">
                <th class="px-4 py-2.5 font-medium text-xs">Data</th>
                <th class="px-4 py-2.5 font-medium text-xs">Purpose</th>
              </tr>
            </thead>
            <tbody class="text-body">
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Username</td>
                <td class="px-4 py-2.5">To identify your account</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Password (hashed with Argon2id)</td>
                <td class="px-4 py-2.5">To authenticate you</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Display name (optional)</td>
                <td class="px-4 py-2.5">To personalise your experience</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Account role</td>
                <td class="px-4 py-2.5">To control access to features</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Account creation timestamp</td>
                <td class="px-4 py-2.5">For administrative records</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-body text-sm leading-relaxed">
          We do not collect your real name, email address, phone number, or any payment information
          unless you voluntarily provide them.
        </p>

        <h3 class="text-base font-semibold text-heading mt-5 mb-2">2.2 Video files</h3>
        <p class="text-body text-sm leading-relaxed mb-3">When you upload a video for analysis, we store:</p>
        <div class="overflow-x-auto rounded-xl border border-line mb-3">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-line text-left text-muted">
                <th class="px-4 py-2.5 font-medium text-xs">Data</th>
                <th class="px-4 py-2.5 font-medium text-xs">Purpose</th>
              </tr>
            </thead>
            <tbody class="text-body">
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">The uploaded video file</td>
                <td class="px-4 py-2.5">To perform accessibility analysis</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Original filename</td>
                <td class="px-4 py-2.5">To display the file in your dashboard</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">File size and duration</td>
                <td class="px-4 py-2.5">To display metadata in reports</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Sampling rate selected</td>
                <td class="px-4 py-2.5">To record the analysis parameters</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Upload timestamp</td>
                <td class="px-4 py-2.5">To order and display your video history</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-body text-sm leading-relaxed">
          Videos are stored on the server outside the public web root. They are not shared with any
          third party and are not used for any purpose other than generating your analysis report.
        </p>

        <h3 class="text-base font-semibold text-heading mt-5 mb-2">2.3 Analysis results</h3>
        <p class="text-body text-sm leading-relaxed">
          After a video is processed, we store per-second flash frequency, motion intensity, and
          luminance datapoints; flagged segment records with severity levels and time ranges; and an
          overall risk summary. This data is linked to your account and to the uploaded video.
        </p>

        <h3 class="text-base font-semibold text-heading mt-5 mb-2">2.4 Session data</h3>
        <p class="text-body text-sm leading-relaxed">
          We use JWT (JSON Web Tokens) for authentication. The token is stored in your browser's
          <code class="bg-surface-alt px-1 rounded text-xs font-mono">localStorage</code>.
          No session cookies are set by this application.
        </p>
      </section>

      <!-- 3. How we use your data -->
      <section>
        <h2 class="text-xl font-semibold text-heading mb-3">3. How we use your data</h2>
        <p class="text-body text-sm leading-relaxed mb-3">
          We process your data on the following lawful bases under Article 6 of the UK/EU GDPR:
        </p>
        <div class="overflow-x-auto rounded-xl border border-line mb-3">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-line text-left text-muted">
                <th class="px-4 py-2.5 font-medium text-xs">Processing activity</th>
                <th class="px-4 py-2.5 font-medium text-xs">Lawful basis</th>
              </tr>
            </thead>
            <tbody class="text-body">
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Storing your account credentials</td>
                <td class="px-4 py-2.5"><strong class="text-heading">Contract</strong> — necessary to provide the service you registered for</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Storing and analysing uploaded videos</td>
                <td class="px-4 py-2.5"><strong class="text-heading">Contract</strong> — necessary to deliver the analysis you requested</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Storing analysis results</td>
                <td class="px-4 py-2.5"><strong class="text-heading">Contract</strong> — necessary to present reports and enable exports</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Admin management of user roles</td>
                <td class="px-4 py-2.5"><strong class="text-heading">Legitimate interests</strong> — necessary to operate and secure the application</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-body text-sm leading-relaxed">
          We do not use your data for advertising, profiling, automated decision-making, or any
          purpose beyond operating this application.
        </p>
      </section>

      <!-- 4. Data retention -->
      <section>
        <h2 class="text-xl font-semibold text-heading mb-3">4. Data retention</h2>
        <div class="overflow-x-auto rounded-xl border border-line mb-3">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-line text-left text-muted">
                <th class="px-4 py-2.5 font-medium text-xs">Data type</th>
                <th class="px-4 py-2.5 font-medium text-xs">Retention period</th>
              </tr>
            </thead>
            <tbody class="text-body">
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Account data</td>
                <td class="px-4 py-2.5">Until you delete your account or request erasure</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Uploaded video files</td>
                <td class="px-4 py-2.5">Until you delete the video or request erasure</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">Analysis results and datapoints</td>
                <td class="px-4 py-2.5">Until the associated video is deleted or you request erasure</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5">JWT tokens</td>
                <td class="px-4 py-2.5">Expire per the configured token lifetime; stored only in your browser</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-body text-sm leading-relaxed">
          Inactive accounts and their associated data may be removed after a period of inactivity at
          the administrator's discretion, with reasonable notice where possible.
        </p>
      </section>

      <!-- 5. Who we share data with -->
      <section>
        <h2 class="text-xl font-semibold text-heading mb-3">5. Who we share your data with</h2>
        <p class="text-body text-sm leading-relaxed mb-3">
          We do not sell, rent, or share your personal data with any third party for marketing or
          commercial purposes.
        </p>
        <p class="text-body text-sm leading-relaxed mb-2">Your data may be accessible to:</p>
        <ul class="list-disc list-inside text-body text-sm leading-relaxed space-y-2 pl-2">
          <li>
            <strong class="text-heading">Server infrastructure providers</strong> — if this
            application is hosted on a third-party server, that provider may have access to the
            underlying storage. Ensure your host offers an appropriate data processing agreement (DPA).
          </li>
          <li>
            <strong class="text-heading">Administrators of this instance</strong> — users with the
            <code class="bg-surface-alt px-1 rounded text-xs font-mono">admin</code> role can view
            a list of registered usernames and account statuses. They cannot see passwords.
          </li>
        </ul>
        <p class="text-body text-sm leading-relaxed mt-3">
          No personal data is sent to external APIs or services by this application.
        </p>
      </section>

      <!-- 6. Cookies and local storage -->
      <section>
        <h2 class="text-xl font-semibold text-heading mb-3">6. Cookies and local storage</h2>
        <p class="text-body text-sm leading-relaxed mb-2">
          This application does not set any cookies.
        </p>
        <p class="text-body text-sm leading-relaxed">
          It uses browser
          <code class="bg-surface-alt px-1 rounded text-xs font-mono">localStorage</code>
          to store your JWT authentication token. This is necessary to keep you logged in between
          page loads. You can clear it at any time by logging out or clearing your browser's local
          storage.
        </p>
      </section>

      <!-- 7. Data security -->
      <section>
        <h2 class="text-xl font-semibold text-heading mb-3">7. Data security</h2>
        <p class="text-body text-sm leading-relaxed mb-3">
          The following technical measures are in place to protect your data:
        </p>
        <ul class="list-disc list-inside text-body text-sm leading-relaxed space-y-2 pl-2">
          <li>Passwords are hashed using <strong class="text-heading">Argon2id</strong>, a memory-hard algorithm recommended for password storage</li>
          <li>All database queries use <strong class="text-heading">PDO prepared statements</strong> to prevent SQL injection</li>
          <li>Authentication uses <strong class="text-heading">JWT bearer tokens</strong> with configurable expiry</li>
          <li>Uploaded video files are stored <strong class="text-heading">outside the public web root</strong> and served through an authenticated API endpoint</li>
          <li>File uploads are validated for type and size before being accepted</li>
        </ul>
        <p class="text-body text-sm leading-relaxed mt-3">
          Despite these measures, no system is completely secure. If you become aware of a security
          vulnerability in this application, please contact us immediately.
        </p>
      </section>

      <!-- 8. Your rights -->
      <section>
        <h2 class="text-xl font-semibold text-heading mb-3">8. Your rights under GDPR</h2>
        <p class="text-body text-sm leading-relaxed mb-3">
          If you are located in the UK or European Economic Area (EEA), you have the following
          rights regarding your personal data:
        </p>
        <div class="overflow-x-auto rounded-xl border border-line mb-4">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-line text-left text-muted">
                <th class="px-4 py-2.5 font-medium text-xs">Right</th>
                <th class="px-4 py-2.5 font-medium text-xs">What it means</th>
              </tr>
            </thead>
            <tbody class="text-body">
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5 font-medium text-heading whitespace-nowrap">Right of access</td>
                <td class="px-4 py-2.5">You can request a copy of the personal data we hold about you</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5 font-medium text-heading whitespace-nowrap">Right to rectification</td>
                <td class="px-4 py-2.5">You can update your display name from your profile page at any time</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5 font-medium text-heading whitespace-nowrap">Right to erasure</td>
                <td class="px-4 py-2.5">You can delete your videos from the dashboard. To delete your account and all associated data, contact us</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5 font-medium text-heading whitespace-nowrap">Right to data portability</td>
                <td class="px-4 py-2.5">Analysis reports can be exported as JSON or CSV from the report page</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5 font-medium text-heading whitespace-nowrap">Right to object</td>
                <td class="px-4 py-2.5">You can object to processing based on legitimate interests by contacting us</td>
              </tr>
              <tr class="border-b border-line last:border-0">
                <td class="px-4 py-2.5 font-medium text-heading whitespace-nowrap">Right to restrict processing</td>
                <td class="px-4 py-2.5">You can request that we restrict processing of your data while a dispute is resolved</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- 9. Children -->
      <section>
        <h2 class="text-xl font-semibold text-heading mb-3">9. Children's data</h2>
        <p class="text-body text-sm leading-relaxed">
          This application is not intended for use by children under the age of 16. We do not
          knowingly collect personal data from children. If you believe a child has created an
          account, please contact us so we can remove it.
        </p>
      </section>

      <!-- 10. Changes -->
      <section>
        <h2 class="text-xl font-semibold text-heading mb-3">10. Changes to this policy</h2>
        <p class="text-body text-sm leading-relaxed">
          We may update this policy from time to time. When we do, we will update the
          <strong class="text-heading">Last updated</strong> date at the top of this page.
          Continued use of the application after an update constitutes acceptance of the revised
          policy.
        </p>
      </section>

    </div>

  </PageTemplate>
</template>
