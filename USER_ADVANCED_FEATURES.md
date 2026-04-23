# User Advanced Features (Brief)

This summary covers only these user-module features:
- Face ID login
- Email verification
- Export users to PDF (admin users view)

## 1) Face ID Login

**What it does**
- Lets users log in using camera-based face matching.
- If no safe match is found, login is denied.

**Main files**
- `src/Controller/SecurityController.php`
  - `detectFace()` for matching + authentication flow
- `src/Service/FaceApiClient.php`
  - Sends face comparison requests to provider API
- `templates/Front/login.html.twig`
  - Face ID entry UI in login page
- `public/Front/js/face.js`
  - Camera capture and face-detect request logic
- `config/services.yaml`
  - Face API options wiring

**Logic / API / library used**
- **Face++ API** (`/facepp/v3/compare`)
- **Symfony HttpClient** (API calls)
- **Symfony Security** (programmatic login/token)

---

## 2) Email Verification

**What it does**
- Sends a 6-digit code after signup.
- Verifies code and marks user as verified.

**Main files**
- `src/Controller/RegistrationController.php`
  - Account creation + initial code sending
- `src/Controller/VerificationController.php`
  - Verify code + resend code
- `templates/Front/verify_email_code.html.twig`
  - Verification form UI

**Logic / API / library used**
- **Symfony Mailer** (send verification emails)
- **MailerSend transport** (configured DSN)
- **Symfony Cache (`cache.app`)** for temporary code storage (TTL)
- **HMAC hash** for code validation integrity

---

## 3) Export Users to PDF (Admin Users View)

**What it does**
- Exports users list to PDF from admin users page.
- Applies active search/filter values to exported result.

**Main files**
- `src/Controller/AdminController.php`
  - `exportPdf()` route and generation logic
- `templates/Front/admin_users.html.twig`
  - Export button in users admin view
- `templates/Front/admin_users_pdf.html.twig`
  - PDF HTML layout template
- `public/Front/js/admin-users.js`
  - Builds export URL with current filters
- `config/packages/knp_snappy.yaml`
  - KnpSnappy PDF binary config

**Logic / API / library used**
- **KnpSnappyBundle** (`knplabs/knp-snappy-bundle`)
- **wkhtmltopdf** binary for HTML -> PDF rendering

