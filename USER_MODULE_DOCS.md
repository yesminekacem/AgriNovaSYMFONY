User module — implementation notes and developer guide

This document describes the tools, patterns and files used to implement the "User module" in this Symfony project: registration, login, session handling, security, profile (including profile image), admin user management and the Twig templates used for the front/back UI.

High-level plan
- Overview of components and how they interact
- Files and directories to look at
- Authentication & Security (login flow, role mapping, session)
- Registration & email validation (server-side validator & DQL checks)
- Profile: image upload, preview, and fallback letter-avatar
- Admin users: CRUD operations, promote/demote/block, exports
- Twig template structure and how CSS/JS are organized
- Local run instructions and common troubleshooting

1) Architecture & main concepts
- Entity (User): Doctrine entity that stores users (email, hashed password, role, fullName, profileImage, email_verified, banned). We use attribute mapping (PHP 8 attributes) and Symfony Validator attributes (e.g. #[Assert\Email]).
- Repository: `App\Repository\UserRepository` contains custom queries and can be used to fetch users for admin table, searches, filters and DQL checks.
- Controllers:
  - `RegistrationController` — handles GET/POST of /register, input validation, password hashing, persistence and verification token generation.
  - Security controller (login/logout) — uses Symfony security system and authenticators.
  - Profile and Admin controllers — provide profile view/edit and admin actions (users list, edit, promote/demote, block/unblock, delete).
- Services: standard Symfony services (validator, url_generator, entity_manager, password_hasher) are injected into controllers.
- Templates: Twig templates are split by `templates/Front/` and `templates/Back/` plus base layout files `base.html.twig` and `baseBack.html.twig`.

2) Important files and places to look
- src/Entity/User.php — Doctrine entity and Validator attributes (email, fullName length if added)
- src/Controller/RegistrationController.php — registration logic and validation
- src/Controller/*AdminController.php* — admin users actions (list, edit, promote/demote, block)
- templates/Front/register.html.twig — registration form
- templates/Front/profile.html.twig — user profile (view & edit)
- templates/Back/admin_users.html.twig — admin user list
- config/packages/security.yaml — authentication, firewall and access controls
- public/ — static assets and uploads (e.g. public/uploads/profile_images and public/cropsimages)
- assets/ — JS and CSS source (we move inline CSS/JS to external files under public/css and public/js in this project)

3) Authentication & session
- Symfony security system (security.yaml) manages login/logout and session creation.
- The `User` entity implements UserInterface and PasswordAuthenticatedUserInterface. `getUserIdentifier()` returns email.
- Roles are stored in a legacy `role` string column; `getRoles()` maps that to proper ROLE_USER / ROLE_ADMIN values.
- After login, redirect logic can check roles and send users to either the admin users table (ROLE_ADMIN) or profile page (ROLE_USER). This is handled in the success handler (or in the controller that receives the login redirect).
- Logout is handled by a route configured in `security.yaml` and should be wired into the UI (a red logout button as requested).

4) Registration flow and validation
- We use two layers of validation:
  - Symfony Validator constraints defined on the `User` entity (e.g. `#[Assert\Email]`, `#[Assert\Length(min: 3)]` etc.). These are invoked in `RegistrationController` via `ValidatorInterface::validatePropertyValue()` for single property checks or `validate()` for the whole object.
  - Application checks like duplicate-email (repository->findOneBy) and additional DQL checks (optional). The DQL check is currently present but redundant — repository lookup is sufficient and preferred.
- Password handling: we check password presence and strength (regex rules) server-side before hashing. Passwords are hashed with Symfony's password hasher (bcrypt/argon2 depending on your PHP environment and config).
- Email verification: the current implementation generates a stateless HMAC token using `APP_SECRET` and the hashed password — no extra DB column needed. The verification link is flashed for local dev.

5) DQL usage for validation (examples)
- Count existing emails (example shown in the code):
  $query = $em->createQuery('SELECT COUNT(u.id) FROM App\\Entity\\User u WHERE u.email = :email')->setParameter('email', $email);
  $emailCount = (int) $query->getSingleScalarResult();
- You can also use the repository: $userRepository->findOneBy(['email' => $email]) — this is simpler and preferred for uniqueness checks.
- DQL is useful when you need DB-driven rules (e.g. getting system-wide settings stored in DB) but for basic field validation, prefer Validator constraints.

6) Profile image upload and preview
- Profile images are stored in `public/uploads/profile_images` (or a similar public folder). The `User::profileImage` stores the filename/path.
- On upload, Symfony uses the normal `UploadedFile` handling. The runtime requires `fileinfo` extension enabled (the error you saw: "Unable to guess the MIME type as no guessers are available") — enable `php_fileinfo` in your PHP ini.
- For image preview fallback: if user has no profile image, render a simple avatar with the user's first letter (HTML/CSS fallback). This is implemented in Twig with a conditional—if `user.profileImage` exists, show <img>, else render a letter circle.

7) Admin user list features
- Table UI: improved with external CSS and JS. Live search / filters / sorting can be implemented client-side with a small JS plugin or server-side with query params and repository queries.
- Export to PDF: use a library (e.g. Dompdf) to render an HTML view and output PDF. Keep it behind ROLE_ADMIN guard.
- Actions: promote/demote toggle role string in DB and clear caches if needed; block toggles `banned` boolean.

8) Twig templates and assets
- Templates live in `templates/Front/` and `templates/Back/` and extend `base.html.twig` or `baseBack.html.twig`.
- All CSS and JS should be externalized in `public/css/` and `public/js/`. Twig includes them via `<link rel="stylesheet" href="{{ asset('css/app.css') }}">` and `<script src="{{ asset('js/app.js') }}"></script>`.
- Keep layout blocks consistent, e.g. `block title`, `block stylesheets`, `block body`, `block javascripts` so children override only what they need.

9) Running the app locally
- Ensure PHP has required extensions: pdo_mysql, fileinfo, curl (optional for Symfony HttpClient). Enable them in your php.ini if missing.
- Set environment variables (copy `.env` or create a `.env.local`). Example snippet using your MySQL DB (no password):

```dotenv
# .env.local (example)
APP_ENV=dev
APP_SECRET=change_me_to_a_long_random_value
DATABASE_URL="mysql://root:@127.0.0.1:3306/agrinova?serverVersion=8.0&charset=utf8mb4"
```

- Install dependencies (if needed):

```bash
composer install
```

- Start server:

```bash
# with Symfony CLI (recommended if installed)
symfony server:start

# or with PHP built-in server
php -S 127.0.0.1:8000 -t public
```

- Open http://127.0.0.1:8000

10) Common issues & fixes
- "An exception occurred in the driver: could not find driver" — means `pdo_mysql` is not enabled for the PHP binary your server/IDE is using. Check `php -m` and `phpinfo()` from the running PHP binary. Ensure PhpStorm is configured to use the same PHP CLI/php-fpm as your web server.
- fileinfo error when uploading images — enable `php_fileinfo` extension.
- Twig route errors (Unable to generate a URL for named route) — means the route name used in Twig (e.g. `app_admin_users`) does not exist. Verify the controller defining that route and that it uses the expected name.
- Controller not callable (expected method "index" etc.) — routes may be referencing a controller action that doesn't exist; either change the route or add the method in the controller.

11) Next steps and suggestions
- Remove redundant DQL email-count check and rely on repository + validator.
- Add `#[Assert\Length(min: 3, max: 100)]` to `fullName` on the `User` entity and adjust registration to validate it (you can use `validatePropertyValue` as in the email case).
- Add client-side form validation and a password strength meter (JS) that mirrors the server regex.
- Add tests (unit/functional) for registration and login flows (phpunit integration tests are already present in the project structure).
- Improve admin table with server-side filtering (via query params) and client-side live search.

If you want, I can:
- Update `templates/Front/register.html.twig` to display the `formErrors` object clearly and add confirm-password input with a client-side password-strength indicator.
- Remove the DQL duplicate-email check and add `#[Assert\Length]` to `fullName`.
- Create a simple `public/css/user-module.css` and `public/js/user-module.js` stub and wire them into the bases.

---

If you want me to proceed with any of the suggested next steps, tell me which one and I'll implement it. If you'd like this document placed elsewhere (docs/ or templates/), I can move it.
