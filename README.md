# chat app no backend

A fully functional, lightweight **real-time chat system** built with **PHP**, **JSON files**, and a clean **HTML/CSS/JS** interface.  
Supports user registration, login, avatars, messaging, and live message fetching—**all without a backend server**.

---

## 🚀 Features

### 🔐 Authentication
- User registration:
  - Username
  - Password (hashed with `password_hash`)
  - Avatar (base64)
- Secure login with token-based authentication
- Token regeneration on each login
- Username validation & duplication check

### 💬 Messaging
- Send text messages
- Send images (base64 encoded)
- Messages stored in `messages.json`
- Auto-trim: keeps last 50 messages when exceeding 100

### 👤 User Management
- Users stored in `users.json`
- Each user contains:
  - username
  - password hash
  - avatar
  - unique token
  - created_at timestamp

### 📁 Data Protection
- Script auto-creates on first run:
```
/chat_data/
    users.json
    messages.json
    .htaccess
```
`.htaccess` content:
```
Order Deny,Allow
Deny from all
```
Blocks external access to JSON files.

### 🎨 Front-End UI
- Responsive design
- Dark theme
- Styled chat bubbles
- Fixed header & input on mobile
- Image preview before sending
- Settings panel with custom CSS support

---

## 📡 API Endpoints

### **POST** Requests (JSON body required)

#### 1️⃣ Register
`action: "register"`

**Request:**
```json
{
  "action": "register",
  "username": "example",
  "password": "12345",
  "avatar": "data:image/png;base64,..."
}
```

**Response:**
```json
{
  "success": true,
  "token": "your_token_here",
  "username": "example"
}
```

---

#### 2️⃣ Login
`action: "login"`

**Request:**
```json
{
  "action": "login",
  "username": "example",
  "password": "12345"
}
```

**Response:**
```json
{
  "success": true,
  "token": "new_token",
  "username": "example",
  "avatar": "..."
}
```

---

#### 3️⃣ Send Message
`action: "send_message"`

**Request:**
```json
{
  "action": "send_message",
  "token": "user_token",
  "message": "Hello!",
  "image": ""
}
```

**Response:**
```json
{
  "success": true,
  "message": { ... }
}
```

---

## 🔍 GET Endpoints

#### 1️⃣ Get Messages
`?action=get_messages`  
Returns all messages.

#### 2️⃣ Verify Token
`?action=verify_token&token=XXXX`  
Checks if user is logged in.

---

## 🛠 Installation

1. Upload all files to your server/hosting.
2. Ensure PHP is enabled.
3. Make sure the script can write to the directory.
4. Open the page and start chatting!

---

## 📄 File Structure
```
project/
│── index.php
│── chat_data/
│    ├── users.json
│    ├── messages.json
│    └── .htaccess
```

---

## 🤝 Contributing
Feel free to extend:
- Add WebSocket support  
- Add admin panel  
- Add message deletion  
- Theme system  

---

## 🐛 Notes
- Not intended for large-scale production
- JSON storage simple, not optimized for heavy loads  
- Best for small communities or personal use  

---

## 🧑‍💻 Author
Made by: [**coder.gg**](https://il1ll.github.io/).

---

## 📬 Contact & Socials
- **Discord:** [@coder.gg](https://discord.com/users/1099039269391171765)
- **Telegram:** [@codergg](https://t.me/codergg)
- **TikTok:** [@coder.gg](https://tiktok.com/@coder.gg)
