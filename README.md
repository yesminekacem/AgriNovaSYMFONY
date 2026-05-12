# 🌱 AgriNova Web — Symfony Integration Module

> **Part of:** `Esprit-PIDEV-3A2-2026-AgriNova`
> **Framework:** Symfony 6 (PHP)
> **Desktop Counterpart:** JavaFX 22

Developed as part of the **PIDEV – 3rd Year Engineering Program** at **Esprit School of Engineering** (Academic Year 2025–2026).

The AgriNova Web module is the browser-based extension of the AgriNova desktop application, built using Symfony 6 to provide AI-powered services, real-time collaboration, and cross-platform accessibility for farm management.

---

# 🏗️ Architecture Overview

```text
AgriNova Platform
├── 🖥️ Desktop App (JavaFX 22)
└── 🌐 Web App (Symfony 6 / PHP)
      └── Shared MySQL 8 Database
```

Both platforms share the same MySQL database schema, ensuring synchronized data across desktop and web applications.

---

# 🚀 Main Features

| Module | Features |
|--------|----------|
| 💬 Forum | Real-time comments & reactions, voice-to-text, translation, bad word filter |
| 🌾 Crop | AI chatbot, disease diagnosis from image, AI task generation, PDF reports |
| 📦 Inventory & Rentals | CRUD, QR codes, email notifications, AI assistant, maps, weather |
| 🛒 Marketplace | Product listings, cart, PayPal checkout, PDF invoices, order tickets |
| 👤 User | Authentication, roles, admin panel |

---

# 💬 Forum Module

## Real-Time Reactions & Comments

- Live reactions using Mercure Hub (Server-Sent Events)
- Reaction types:
  - 👍 Like
  - ❤️ Love
  - 😂 Haha
  - 😮 Wow
  - 😢 Sad
  - 😡 Angry
- Real-time comment streaming
- Dynamic most-reacted posts feed

## Bad Word Filter

- Server-side profanity filtering
- Configurable blocked-word dictionary
- Offensive content can be rejected or auto-censored

## Voice-to-Text

- Browser-native Web Speech API integration
- Speech converted directly into post/comment textareas

## Translation

- One-click translation of posts and comments
- Powered by LibreTranslate or DeepL API

---

# 🌾 Crop Module

## AI Crop Chatbot

The crop dashboard includes an AI chatbot powered by Groq API (LLaMA 3.3 70B).

### Features

- Multi-turn conversations
- Real-time streamed responses
- Markdown-rendered answers
- Agricultural advice:
  - Soil recommendations
  - Irrigation suggestions
  - Pest treatment
  - Planting schedules

---

## AI Plant Disease Diagnosis

Users can upload plant or leaf images for disease detection.

### Workflow

1. User uploads image
2. Image sent to YOLOv8 Python API
3. API returns:
   - Disease name
   - Confidence score
   - Bounding boxes
4. Groq AI generates treatment recommendations
5. Result stored in crop history

### Features

- Drag & drop upload
- Annotated disease preview
- Offline API fallback message

---

## AI Task Generation

A "Generate Tasks" button automatically creates farming tasks using AI.

### Input Data

- Crop type
- Planting date
- Growth stage
- Farm region
- Crop area

### Generated Tasks

Examples:
- Irrigation schedule
- Fertilization
- Pest inspection
- Soil monitoring

### Additional Features

- Estimated costs
- Due dates
- Editable before saving
- PDF export using DomPDF

---

# 📦 Inventory & Rentals Module

## Inventory CRUD

- Add equipment
- Edit equipment
- Delete equipment
- Search & filter
- Availability tracking

## Rental Management

- Rental scheduling
- Auto price calculation
- Status tracking:
  - Active
  - Pending
  - Completed
  - Overdue
  - Cancelled

---

## QR Code Integration

QR codes are generated for:

- Equipment
- Rental tickets
- Rental references

Generated using:
- `endroid/qr-code`

---

## Email Notifications

Email services implemented with Symfony Mailer.

### Notifications

- New rental confirmation
- Due-date reminders
- Overdue alerts
- Equipment availability notifications

---

## Inventory AI Assistant

AI assistant answers inventory-related questions such as:

- "Which equipment is available next week?"
- "Suggest a rental price for a tractor."

Powered by Groq API.

---

## Interactive Maps

Leaflet.js integration provides:

- GPS coordinate selection
- Rental location visualization
- Equipment clustering

---

## Weather Widget

Integrated OpenWeatherMap widget displays:

- Temperature
- Humidity
- Wind speed
- Rain forecast
- 5-day forecast

---

# 🛒 Marketplace Module

## Product Listings

- Product images
- Price
- Quantity
- Categories
- Availability status

## Shopping Cart

- Quantity management
- Live subtotal calculation
- Checkout flow

---

## PayPal Integration

Integrated PayPal Sandbox payment system.

### Features

- OAuth2 flow
- Order validation
- Payment confirmation
- Order status tracking

---

## PDF Invoice Generator

Invoices generated using DomPDF.

### Invoice Content

- Buyer information
- Seller information
- Product list
- Unit prices
- Total price
- Taxes
- PayPal transaction ID

### Route

```text
GET /marketplace/order/{id}/invoice
```

---

## Order Confirmation Ticket

After checkout:

- Ticket generated automatically
- Includes QR code
- Printable HTML/PDF version
- Sent by email

### Ticket Includes

- Order reference
- Product list
- Total amount
- Delivery estimate
- QR code

### Route

```text
GET /marketplace/order/{id}/ticket
```

---

# 👤 User Module

## Authentication Features

- Registration
- Login
- Remember Me
- Forgot password
- OTP verification

## Admin Features

- User management
- Ban/unban users
- Role management

### Roles

- ROLE_USER
- ROLE_ADMIN

---

# 🛠️ Tech Stack

## Backend

| Layer | Technology |
|-------|------------|
| Framework | Symfony 6 |
| ORM | Doctrine ORM |
| Database | MySQL 8 |
| Real-Time | Mercure Hub |
| AI Chatbot | Groq API |
| Disease Detection | YOLOv8 Python API |
| PDF Export | DomPDF |
| QR Codes | endroid/qr-code |
| Mail Service | Symfony Mailer |
| Payment | PayPal API |
| Maps | Leaflet.js |
| Weather | OpenWeatherMap |

---

## Frontend

| Layer | Technology |
|-------|------------|
| Templating | Twig |
| CSS | Tailwind CSS / Bootstrap 5 |
| JavaScript | Vanilla JS + Symfony UX |
| Maps | Leaflet.js |
| Charts | Chart.js |
| Voice Input | Web Speech API |

---

# 📂 Project Structure

```text
agrinova-web/
├── src/
│   ├── Controller/
│   ├── Entity/
│   ├── Repository/
│   ├── Service/
│   ├── EventSubscriber/
│   └── Twig/
├── templates/
├── public/
├── config/
├── migrations/
├── .env
└── composer.json
```

---

# ⚙️ Installation

## Requirements

- PHP 8.1+
- Composer
- Symfony CLI
- MySQL 8
- Node.js 18+
- Mercure Hub

---

## Setup

```bash
# Clone repository
git clone https://github.com/yourusername/Esprit-PIDEV-3A2-2026-AgriNova.git

# Enter project
cd agrinova-web

# Install dependencies
composer install

# Install frontend packages
npm install && npm run dev

# Configure environment
cp .env .env.local

# Run migrations
php bin/console doctrine:migrations:migrate

# Start Symfony server
symfony server:start
```

---

# 🔒 Security

| Concern | Implementation |
|---------|----------------|
| Password Hashing | Symfony PasswordHasher |
| CSRF Protection | Symfony CSRF tokens |
| SQL Injection | Doctrine ORM prepared queries |
| XSS Protection | Twig auto-escaping |
| Authentication | Symfony Security |
| OTP Expiration | 15-minute expiry |
| Mercure Security | JWT topic authorization |

---

# 👥 Contributors

| Name | Module |
|------|--------|
| Team Member 1 | User Module |
| Team Member 2 | Crop AI |
| Team Member 3 | Forum |
| Team Member 4 | Marketplace |
| Team Member 5 | Inventory |

---

# 🎓 Academic Context

- Institution: Esprit School of Engineering
- Program: PIDEV – 3rd Year Engineering
- Class: 3A2
- Academic Year: 2025–2026

---

# 🏷️ GitHub Topics

```text
symfony
php
mysql
mercure
groq
paypal
leaflet
openweathermap
farm-management
ai-integration
```

---

# 📄 License

This project is developed for educational purposes at Esprit School of Engineering.
