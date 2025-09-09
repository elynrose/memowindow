# 🎵 MemoWindow

Transform precious voice recordings of your loved ones into beautiful waveform art. Create lasting visual memories that you can frame, share, and treasure forever.

## ✨ Features

### 💕 Voice Memory Creation
- Upload voice recordings of loved ones
- Generate beautiful waveform visualizations
- Create complete memory frames with titles and QR codes
- High-resolution output (3600×2400) perfect for printing

### 🔐 Secure Authentication
- Google Sign-in integration
- Email/password authentication
- User-specific memory collections
- Firebase Auth integration

### 🎨 Professional Design
- Complete canvas compositions with title, waveform, and QR code
- Print-ready resolution for professional framing
- Responsive design for all devices
- Beautiful modal interfaces

### 🛒 E-commerce System
- Stripe payment processing
- Multiple print size options (12"×16", 18"×24", framed prints)
- Order tracking and management
- Printful integration for fulfillment

### 📊 Memory Management
- Personal memory collections
- Pagination with "Load More" functionality
- Delete memories with confirmation
- Image viewing modals

## 🚀 Tech Stack

### Frontend
- **HTML5 Canvas** - Waveform visualization
- **Firebase Auth** - User authentication
- **Firebase Storage** - File hosting
- **ES6 Modules** - Modern JavaScript
- **Webpack** - Module bundling

### Backend
- **PHP** - Server-side logic
- **MySQL** - Database storage
- **Stripe API** - Payment processing
- **Printful API** - Print fulfillment
- **Firebase Storage** - File management

## 🔧 Installation

### Prerequisites
- PHP 7.4+
- MySQL/MariaDB
- Node.js & npm
- Composer
- Firebase project

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone [repository-url]
   cd memowindow
   ```

2. **Install dependencies**
   ```bash
   npm install
   composer install
   ```

3. **Configure Firebase**
   - Update `src/firebase-config.js` with your Firebase project settings
   - Enable Authentication (Google + Email/Password)
   - Enable Storage with appropriate rules

4. **Configure APIs**
   - Update `config.php` with your API keys:
     - Stripe publishable and secret keys
     - Printful API key and store ID
   - Set up database connection details

5. **Build frontend**
   ```bash
   npm run build
   ```

6. **Database setup**
   - Create MySQL database
   - Tables will be created automatically on first use

## 🔑 Configuration

### Firebase Storage Rules
```javascript
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    match /{allPaths=**} {
      allow read: if true;
      allow write, delete: if request.auth != null;
    }
  }
}
```

### Required API Keys
- **Firebase** - Project configuration
- **Stripe** - Payment processing (test/live keys)
- **Printful** - Print fulfillment

## 📱 Usage

1. **Sign in** with Google or email/password
2. **Upload voice recording** of loved one
3. **Add meaningful title** (e.g., "Mom's Laughter")
4. **Create Memory** - Generates complete composition
5. **Order prints** - Choose size and complete payment
6. **Track orders** - Monitor print status

## 🖼️ Print Specifications

- **High Resolution**: 3600×2400 pixels (150 DPI)
- **Professional Quality**: Suitable for large frames
- **Complete Composition**: Title, waveform, and QR code included
- **Multiple Sizes**: 12"×16", 18"×24", canvas options

## 🔒 Security Features

- **User authentication** required for all operations
- **Private memory collections** - users only see their own
- **Secure file storage** in Firebase Storage
- **PCI-compliant payments** via Stripe
- **Input validation** throughout application

## 📊 Database Schema

### wave_assets
- User memories with Firebase Storage URLs
- Audio files, waveform images, metadata

### orders
- Stripe payment tracking
- Printful order integration
- Customer information

## 🎯 Perfect For

- **Memorial frames** with QR codes to hear voices
- **Family gifts** with recordings of loved ones
- **Memory preservation** for future generations
- **Sharing precious moments** easily and beautifully

## 💕 Created with Love

MemoWindow helps preserve the most precious gift - the voice of those we love. Every memory created is a lasting tribute that families can see, hear, and treasure forever.

---

**Built with modern web technologies and designed for meaningful human connections.** 🎵💕
