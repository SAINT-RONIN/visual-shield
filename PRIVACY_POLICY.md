# Privacy Policy

**Application:** Visual Shield  
**Last updated:** April 2026  
**Version:** 1.0

> **Note:** This policy applies to instances of Visual Shield that are deployed and made accessible to users. If you are running Visual Shield locally for personal development or testing, this policy does not apply to you.

---

## 1. Who we are

Visual Shield is a web application that analyses uploaded video files for visual accessibility risks such as flashing, luminance changes, and motion intensity. It is not a medical service and does not provide medical advice or diagnosis.

**Data Controller:**  
[Your name or organisation name]  
[Your address]  
[Your country]  
[Your contact email]

If you have any questions about this privacy policy or how your data is handled, contact us at the email address above.

---

## 2. What data we collect

### 2.1 Account data

When you register an account, we collect:

| Data | Purpose |
|------|---------|
| Username | To identify your account |
| Password (hashed with Argon2id) | To authenticate you |
| Display name (optional) | To personalise your experience |
| Account role (`member` or `admin`) | To control access to features |
| Account creation timestamp | For administrative records |

We do not collect your real name, email address, phone number, or any payment information unless you voluntarily provide them.

### 2.2 Video files

When you upload a video for analysis, we store:

| Data | Purpose |
|------|---------|
| The uploaded video file | To perform accessibility analysis |
| Original filename | To display the file in your dashboard |
| File size and duration | To display metadata in reports |
| Sampling rate you selected | To record the analysis parameters |
| Upload timestamp | To order and display your video history |

Videos are stored on the server outside the public web root. They are not shared with any third party and are not used for any purpose other than generating your analysis report.

### 2.3 Analysis results

After a video is processed, we store:

- Per-second flash frequency, motion intensity, and luminance datapoints
- Flagged segment records with severity levels, start and end times, and metric values
- An overall risk summary for the video

This data is linked to your account and to the uploaded video file.

### 2.4 Session data

We use JWT (JSON Web Tokens) for authentication. The token is stored in your browser's `localStorage`. No session cookies are set by this application.

---

## 3. How we use your data

We process your data on the following lawful bases under Article 6 of the UK/EU General Data Protection Regulation (GDPR):

| Processing activity | Lawful basis |
|--------------------|-------------|
| Storing your account credentials | **Contract** — necessary to provide you with the service you registered for |
| Storing and analysing uploaded videos | **Contract** — necessary to deliver the analysis you requested |
| Storing analysis results | **Contract** — necessary to present reports and enable exports |
| Admin management of user roles | **Legitimate interests** — necessary to operate and secure the application |

We do not use your data for advertising, profiling, automated decision-making, or any purpose beyond operating this application.

---

## 4. Data retention

| Data type | Retention period |
|-----------|-----------------|
| Account data | Retained until you delete your account or request erasure |
| Uploaded video files | Retained until you delete the video or request erasure |
| Analysis results and datapoints | Retained until the associated video is deleted or you request erasure |
| JWT tokens | Expire according to the configured token lifetime; stored only in your browser |

Inactive accounts and their associated data may be deleted after a period of inactivity at the administrator's discretion, with reasonable notice where possible.

---

## 5. Who we share your data with

We do not sell, rent, or share your personal data with any third party for marketing or commercial purposes.

Your data may be accessible to:

- **Server infrastructure providers** — if this application is hosted on a third-party server or cloud platform, that provider may have access to the underlying storage. Ensure your hosting provider offers appropriate data processing agreements (DPAs).
- **Administrators of this instance** — users with the `admin` role can view a list of all registered usernames and account statuses. They cannot see passwords.

No personal data is sent to external APIs or services by this application.

---

## 6. Cookies and local storage

This application does not set any cookies.

It uses browser `localStorage` to store your JWT authentication token. This is necessary to keep you logged in between page loads. You can clear it at any time by logging out or clearing your browser's local storage.

---

## 7. Data security

The following technical measures are in place to protect your data:

- Passwords are hashed using **Argon2id**, a memory-hard algorithm recommended for password storage
- All database queries use **PDO prepared statements** to prevent SQL injection
- Authentication uses **JWT bearer tokens** with configurable expiry
- Uploaded video files are stored **outside the public web root** and served through an authenticated API endpoint
- File uploads are validated for type and size before being accepted

Despite these measures, no system is completely secure. If you become aware of a security vulnerability in this application, please contact us immediately.

---

## 8. Your rights under GDPR

If you are located in the UK or European Economic Area (EEA), you have the following rights regarding your personal data:

| Right | What it means |
|-------|--------------|
| **Right of access** | You can request a copy of the personal data we hold about you |
| **Right to rectification** | You can update your display name from your profile page at any time |
| **Right to erasure** | You can delete your videos from the dashboard. To delete your account and all associated data, contact us |
| **Right to data portability** | Analysis reports can be exported as JSON or CSV from the report page |
| **Right to object** | You can object to processing based on legitimate interests by contacting us |
| **Right to restrict processing** | You can request that we restrict processing of your data while a dispute is resolved |

To exercise any of these rights, contact us at the email address in Section 1. We will respond within 30 days.

If you are not satisfied with how we handle your request, you have the right to lodge a complaint with your national data protection authority:

- **UK:** Information Commissioner's Office (ICO) — [ico.org.uk](https://ico.org.uk)
- **EU:** Your local supervisory authority — [edpb.europa.eu/about-edpb/board/members](https://edpb.europa.eu/about-edpb/board/members)

---

## 9. Children's data

This application is not intended for use by children under the age of 16. We do not knowingly collect personal data from children. If you believe a child has created an account, please contact us so we can remove it.

---

## 10. Changes to this policy

We may update this privacy policy from time to time. When we do, we will update the **Last updated** date at the top of this document. We recommend reviewing it periodically.

Continued use of the application after a policy update constitutes acceptance of the revised terms.

---

## 11. Contact

For any privacy-related questions, data subject requests, or security disclosures:

**Email:** [your-contact-email@example.com]  
**Address:** [Your organisation address]

---

*Visual Shield does not provide medical advice. The analysis is automated and based on technical thresholds for accessibility awareness only.*
