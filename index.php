<?php
date_default_timezone_set('Asia/Riyadh');

$data_dir = 'chat_data/';
$users_file = $data_dir . 'users.json';
$messages_file = $data_dir . 'messages.json';
$htaccess_file = $data_dir . '.htaccess';

if (!file_exists($data_dir)) {
    mkdir($data_dir, 0755, true);
    $htaccess_content = "Order Deny,Allow\nDeny from all\n";
    file_put_contents($htaccess_file, $htaccess_content);
}

function getUsers() {
    global $users_file;
    if (file_exists($users_file)) {
        $users = json_decode(file_get_contents($users_file), true);
        return is_array($users) ? $users : [];
    }
    return [];
}

function saveUsers($users) {
    global $users_file;
    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function verifyToken($token) {
    $users = getUsers();
    foreach ($users as $user) {
        if (isset($user['token']) && $user['token'] === $token) {
            return $user;
        }
    }
    return false;
}

function getMessages() {
    global $messages_file;
    if (file_exists($messages_file)) {
        $messages = json_decode(file_get_contents($messages_file), true);
        return is_array($messages) ? $messages : [];
    }
    return [];
}

function saveMessages($messages) {
    global $messages_file;
    file_put_contents($messages_file, json_encode($messages, JSON_PRETTY_PRINT));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    header("Access-Control-Allow-Origin: *");
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (isset($data['action'])) {
        if ($data['action'] === 'register') {
            $users = getUsers();
            $username = trim($data['username']);
            
            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Username cannot be empty']);
                exit;
            }
            
            foreach ($users as $user) {
                if ($user['username'] === $username) {
                    echo json_encode(['success' => false, 'message' => 'Username already exists']);
                    exit;
                }
            }
            
            $new_user = [
                'username' => $username,
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'avatar' => $data['avatar'] ?? '',
                'token' => generateToken(),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $users[] = $new_user;
            saveUsers($users);
            
            echo json_encode(['success' => true, 'token' => $new_user['token'], 'username' => $username]);
            exit;
            
        } elseif ($data['action'] === 'login') {
            $users = getUsers();
            $username = trim($data['username']);
            $password = $data['password'];
            
            if (empty($username) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Username and password cannot be empty']);
                exit;
            }
            
            foreach ($users as $user) {
                if ($user['username'] === $username && password_verify($password, $user['password'])) {
                    $new_token = generateToken();
                    $user_avatar = $user['avatar'] ?? '';
                    
                    foreach ($users as &$u) {
                        if ($u['username'] === $username) {
                            $u['token'] = $new_token;
                            break;
                        }
                    }
                    
                    saveUsers($users);
                    echo json_encode(['success' => true, 'token' => $new_token, 'username' => $username, 'avatar' => $user_avatar]);
                    exit;
                }
            }
            
            echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
            exit;
            
        } elseif ($data['action'] === 'send_message') {
            $token = $data['token'];
            $user = verifyToken($token);
            
            if ($user) {
                $messages = getMessages();
                
                if (count($messages) >= 100) {
                    $messages = array_slice($messages, -50);
                }
                
                $message_content = trim($data['message'] ?? '');
                $image_content = $data['image'] ?? '';
                
                if (empty($message_content) && empty($image_content)) {
                    echo json_encode(['success' => false, 'message' => 'Message or image is required']);
                    exit;
                }
                
                $message_data = [
                    'id' => uniqid(),
                    'username' => $user['username'],
                    'avatar' => $user['avatar'],
                    'message' => $message_content,
                    'image' => $image_content,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                
                $messages[] = $message_data;
                saveMessages($messages);
                
                echo json_encode(['success' => true, 'message' => $message_data]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    header("Access-Control-Allow-Origin: *");
    
    if ($_GET['action'] === 'get_messages') {
        $messages = getMessages();
        echo json_encode($messages);
        exit;
        
    } elseif ($_GET['action'] === 'verify_token') {
        $token = $_GET['token'] ?? '';
        $user = verifyToken($token);
        
        if ($user) {
            echo json_encode(['success' => true, 'username' => $user['username'], 'avatar' => $user['avatar'] ?? '']);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Application</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --dark-bg: #1e1e2f;
            --dark-card: #27293d;
            --dark-text: #e6e6e6;
            --dark-secondary-text: #a0a0a0;
            --primary-color: #5d50c6;
            --primary-light: #7b68ee;
            --success-color: #4CAF50;
            --danger-color: #dc3545;
            --border-color: #3f4159;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--dark-bg); 
            color: var(--dark-text);
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        
        .container { 
            width: 100%; 
            max-width: 1000px; 
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .auth-container { 
            background: var(--dark-card); 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
            overflow: hidden;
            width: 90%;
            max-width: 400px;
        }

        .chat-container { 
            height: 100%;
            width: 100%;
            display: flex; 
            flex-direction: column; 
            background-color: var(--dark-card);
            border-radius: 0;
            box-shadow: none;
            position: relative;
        }

        @media (min-width: 768px) {
            .container {
                margin: 20px;
                height: 90vh;
                max-width: 1200px;
            }
            .chat-container {
                height: 90vh;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                max-height: 90vh; 
            }
        }

        .header { 
            background: var(--primary-color); 
            color: white; 
            padding: 20px; 
            text-align: center; 
            border-top-left-radius: 20px; 
            border-top-right-radius: 20px;
        }
        
        @media (max-width: 767px) {
             .header {
                border-radius: 0;
             }
        }

        .auth-tabs { 
            display: flex; 
            background: var(--dark-bg); 
        }

        .auth-tab { 
            flex: 1; 
            padding: 15px; 
            text-align: center; 
            cursor: pointer; 
            border-bottom: 3px solid transparent; 
            transition: all 0.3s;
            color: var(--dark-secondary-text); 
        }

        .auth-tab.active { 
            border-bottom-color: var(--primary-light); 
            background: var(--dark-card); 
            font-weight: bold;
            color: var(--dark-text);
        }

        .auth-form { padding: 30px; }
        
        .form-group { margin-bottom: 20px; }
        
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: var(--dark-secondary-text); 
        }

        .form-group input { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid var(--border-color); 
            border-radius: 15px; 
            font-size: 16px; 
            transition: border-color 0.3s, background-color 0.3s;
            background-color: var(--dark-bg);
            color: var(--dark-text);
        }

        .form-group input:focus { 
            outline: none; 
            border-color: var(--primary-light); 
        }

        .avatar-preview { 
            width: 80px; 
            height: 80px; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 4px solid var(--primary-color); 
            margin: 10px auto; 
            display: block; 
        }

        .file-label { 
            display: block; 
            background: var(--primary-color); 
            color: white; 
            padding: 10px 15px; 
            border-radius: 15px; 
            text-align: center; 
            cursor: pointer; 
            margin-top: 10px; 
            transition: background 0.3s; 
        }

        .file-label:hover { 
            background: var(--primary-light); 
        }

        .btn { 
            width: 100%; 
            padding: 15px; 
            background: var(--primary-color); 
            color: white; 
            border: none; 
            border-radius: 15px; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: transform 0.2s, background-color 0.3s; 
        }

        .btn:hover { 
            transform: translateY(-2px);
            background: var(--primary-light); 
        }

        .chat-header { 
            background: var(--primary-color); 
            color: white; 
            padding: 15px 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-shrink: 0;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            position: relative;
            z-index: 10;
        }

        @media (max-width: 767px) {
            .chat-header {
                border-radius: 0;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                width: 100%;
                z-index: 1000;
            }
        }

        .user-info { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }

        .user-avatar { 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            background: rgba(255,255,255,0.2); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            overflow: hidden; 
        }

        .user-avatar img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }

        .menu-btn { 
            background: rgba(255,255,255,0.2); 
            color: white; 
            border: none; 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 18px; 
            transition: background 0.3s; 
        }

        .menu-btn:hover { 
            background: rgba(255,255,255,0.3); 
        }

        .logout-btn { 
            background: var(--danger-color); 
            color: white; 
            border: none; 
            padding: 8px 15px; 
            border-radius: 20px; 
            cursor: pointer; 
            transition: background 0.3s; 
        }

        .logout-btn:hover { 
            background: #c93041; 
        }

        .chat-messages { 
            flex: 1; 
            overflow-y: auto; 
            padding: 20px; 
            background: var(--dark-bg);
            scrollbar-color: var(--primary-color) var(--dark-bg);
            scrollbar-width: thin;
            position: relative;
        }

        @media (max-width: 767px) {
            .chat-messages {
                padding-top: 80px;
                padding-bottom: 80px;
            }
        }

        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: var(--dark-bg);
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background-color: var(--primary-color);
            border-radius: 10px;
            border: 2px solid var(--dark-bg);
        }

        .message { 
            margin-bottom: 15px; 
            padding: 12px 15px; 
            border-radius: 15px; 
            max-width: 80%; 
            position: relative; 
            animation: fadeIn 0.3s ease-in; 
            word-wrap: break-word;
        }

        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        .message.user-message { 
            background: var(--primary-color); 
            color: white; 
            margin-left: auto; 
            border-bottom-right-radius: 5px; 
        }

        .message.other-message { 
            background: var(--dark-card); 
            border: 1px solid var(--border-color); 
            border-bottom-left-radius: 5px;
            color: var(--dark-text); 
        }

        .message-header { 
            display: flex; 
            align-items: flex-start; 
            gap: 8px; 
            margin-bottom: 5px; 
        }

        .message.other-message .message-username {
            color: var(--primary-light);
        }
        .message.user-message .message-username {
            color: var(--dark-text);
        }

        .message-avatar { 
            width: 30px; 
            height: 30px; 
            border-radius: 50%; 
            background: var(--dark-bg); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: var(--dark-secondary-text); 
            font-size: 14px; 
            overflow: hidden; 
            flex-shrink: 0; 
        }

        .message-avatar img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }

        .message-username { 
            font-weight: 600; 
            font-size: 14px; 
        }

        .message img { 
            max-width: 100%; 
            border-radius: 8px; 
            margin-top: 0;
            display: block;
            height: auto;
        }

        .message-time { 
            font-size: 10px; 
            opacity: 0.7; 
            margin-top: 5px;
            text-align: right; 
            display: block;
        }

        .chat-input-container { 
            padding: 10px 15px; 
            background: var(--dark-card); 
            border-top: 1px solid var(--border-color); 
            display: flex; 
            gap: 10px; 
            align-items: center;
            flex-shrink: 0;
            position: relative;
            z-index: 10;
        }

        @media (max-width: 767px) {
            .chat-input-container {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                padding: 8px 12px;
            }
        }

        .message-input { 
            flex: 1; 
            padding: 10px 15px; 
            border: 2px solid var(--border-color); 
            border-radius: 20px; 
            font-size: 16px; 
            resize: none;
            height: 40px;
            overflow: hidden;
            min-height: 40px;
            max-height: 40px;
            background-color: var(--dark-bg);
            color: var(--dark-text);
            transition: border-color 0.3s;
            line-height: 1.4;
        }
        
        @media (max-width: 767px) {
            .message-input {
                padding: 8px 12px;
            }
        }

        .message-input:focus { 
            outline: none; 
            border-color: var(--primary-light); 
        }

        .send-btn, .image-btn { 
            width: 40px; 
            height: 40px; 
            border: none; 
            border-radius: 50%; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 16px; 
            transition: all 0.3s; 
            flex-shrink: 0;
        }
        
        @media (max-width: 767px) {
            .send-btn, .image-btn {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }

        .send-btn { 
            background: var(--primary-color); 
            color: white; 
        }

        .image-btn { 
            background: var(--success-color); 
            color: white; 
        }

        .send-btn:hover { 
            background: var(--primary-light); 
        }
        .image-btn:hover {
            background: #45a049;
        }
        
        .image-preview-container { 
            display: flex; 
            padding: 10px 15px; 
            background: var(--dark-card); 
            border-top: 1px solid var(--border-color); 
            align-items: center; 
            gap: 10px; 
            flex-shrink: 0;
            display: none;
            position: relative;
            z-index: 10;
        }

        @media (max-width: 767px) {
            .image-preview-container {
                position: fixed;
                bottom: 60px;
                left: 0;
                right: 0;
                width: 100%;
            }
        }

        .image-preview { 
            width: 50px; 
            height: 50px; 
            border-radius: 8px; 
            object-fit: cover; 
            border: 2px solid var(--primary-color); 
        }

        .remove-image-btn { 
            background: var(--danger-color); 
            color: white; 
            border: none; 
            width: 30px; 
            height: 30px; 
            border-radius: 50%; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 14px; 
        }

        .hidden { display: none !important; }

        .settings-menu { 
            position: fixed; 
            top: 60px; 
            left: 20px; 
            background: var(--dark-card); 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.4); 
            padding: 20px; 
            width: 300px; 
            z-index: 1000; 
            display: none; 
            border: 1px solid var(--border-color);
        }

        .settings-menu.active { display: block; }

        .settings-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 15px; 
            padding-bottom: 10px; 
            border-bottom: 1px solid var(--border-color); 
        }

        .settings-title { 
            font-weight: 600; 
            color: var(--dark-text); 
        }

        .close-settings { 
            background: none; 
            border: none; 
            font-size: 18px; 
            cursor: pointer; 
            color: var(--dark-secondary-text); 
            transition: color 0.3s;
        }

        .close-settings:hover {
            color: var(--danger-color);
        }

        .settings-group { margin-bottom: 20px; }

        .settings-label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: var(--dark-secondary-text); 
        }

        .settings-select, .settings-input { 
            width: 100%; 
            padding: 10px; 
            border: 2px solid var(--border-color); 
            border-radius: 10px; 
            font-size: 14px; 
            margin-bottom: 10px;
            background-color: var(--dark-bg);
            color: var(--dark-text);
            transition: border-color 0.3s;
        }

        .settings-select:focus, .settings-input:focus { 
            outline: none; 
            border-color: var(--primary-light); 
        }

        .settings-checkbox { 
            margin-right: 8px; 
        }

        .settings-option { 
            display: flex; 
            align-items: center; 
            margin-bottom: 8px; 
            color: var(--dark-text);
        }

        .apply-css-btn { 
            background: var(--primary-color); 
            color: white; 
            border: none; 
            padding: 8px 15px; 
            border-radius: 10px; 
            cursor: pointer; 
            font-size: 14px; 
            transition: background 0.3s;
        }

        .apply-css-btn:hover { 
            background: var(--primary-light); 
        }

        @media (max-width: 767px) {
            body { 
                align-items: flex-start; 
                justify-content: flex-start;
            }
            .container { 
                height: 100vh; 
                margin: 0;
                width: 100%;
            }
            .auth-container {
                border-radius: 0;
                width: 100%;
                max-width: none;
                height: 100vh;
                display: flex;
                flex-direction: column;
            }
            .auth-form {
                flex-grow: 1;
                overflow-y: auto;
            }
            .chat-container { 
                height: 100%;
                border-radius: 0;
                width: 100%;
            }
            .message { 
                max-width: 90%; 
                padding: 10px 12px;
            }
            .settings-menu { 
                width: calc(100% - 40px); 
                left: 20px; 
                top: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="authContainer" class="auth-container">
            <div class="header">
                <h1>Welcome to Chat</h1>
            </div>
            <div class="auth-tabs">
                <div class="auth-tab active" onclick="showTab('login')">Login</div>
                <div class="auth-tab" onclick="showTab('register')">Register</div>
            </div>
            
            <div id="loginForm" class="auth-form">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" id="loginUsername" placeholder="Enter your username">
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" id="loginPassword" placeholder="Enter your password">
                </div>
                <button class="btn" onclick="login()">Login</button>
            </div>
            
            <div id="registerForm" class="auth-form hidden">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" id="registerUsername" placeholder="Choose a username">
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" id="registerPassword" placeholder="Choose a password">
                </div>
                <div class="form-group">
                    <label>Profile Picture:</label>
                    <input type="file" id="avatarInput" class="hidden" accept="image/*">
                    <label for="avatarInput" class="file-label">Choose Image</label>
                    <img id="avatarPreview" class="avatar-preview hidden">
                </div>
                <button class="btn" onclick="register()">Create Account</button>
            </div>
        </div>
        
        <div id="chatContainer" class="chat-container hidden">
            <div class="chat-header">
                <div class="user-info">
                    <button class="menu-btn" onclick="toggleSettings()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="user-avatar" id="userAvatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <span id="userWelcome">Welcome!</span>
                </div>
                <button class="logout-btn" onclick="logout()">Logout</button>
            </div>
            
            <div class="chat-messages" id="chatMessages"></div>
            
            <div class="image-preview-container" id="imagePreviewContainer">
                <img id="imagePreview" class="image-preview">
                <button class="remove-image-btn" onclick="removeSelectedImage()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="chat-input-container">
                <input type="file" id="imageInput" class="hidden" accept="image/*">
                <textarea id="messageInput" class="message-input" placeholder="Type your message here..."></textarea>
                <button class="image-btn" id="imageButton" onclick="document.getElementById('imageInput').click()">
                    <i class="fas fa-image"></i>
                </button>
                <button class="send-btn" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="settings-menu" id="settingsMenu">
        <div class="settings-header">
            <div class="settings-title">Settings</div>
            <button class="close-settings" onclick="toggleSettings()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="settings-group">
            <label class="settings-label">Time Format:</label>
            <select class="settings-select" id="timeFormat">
                <option value="time">Time Only</option>
                <option value="date">Date Only</option>
                <option value="both">Time & Date</option>
            </select>
        </div>
        
        <div class="settings-group">
            <label class="settings-label">Avatar Display:</label>
            <div class="settings-option">
                <input type="checkbox" class="settings-checkbox" id="showAvatars" checked>
                <label for="showAvatars">Show Avatars</label>
            </div>
        </div>
        
        <div class="settings-group">
            <label class="settings-label">Custom CSS:</label>
            <textarea class="settings-input" id="customCSS" placeholder="Enter your custom CSS here..." rows="6"></textarea>
            <button class="apply-css-btn" onclick="applyCustomCSS()">Apply CSS</button>
            <button class="apply-css-btn" onclick="resetCustomCSS()" style="background: var(--danger-color); margin-left: 5px;">Reset CSS</button>
        </div>
    </div>

    <script>
        let currentUser = null;
        let currentToken = localStorage.getItem('chat_token');
        let selectedImage = null;
        let isUserScrolling = false;
        let currentMessages = new Map();

        const settings = {
            timeFormat: localStorage.getItem('timeFormat') || 'both',
            showAvatars: localStorage.getItem('showAvatars') !== 'false',
            customCSS: localStorage.getItem('customCSS') || ''
        };

        if (currentToken) {
            verifyToken(currentToken);
        } else {
            showAuth();
        }

        function loadSettings() {
            document.getElementById('timeFormat').value = settings.timeFormat;
            document.getElementById('showAvatars').checked = settings.showAvatars;
            document.getElementById('customCSS').value = settings.customCSS;
            applyCustomCSS();
            applySettings();
        }

        function saveSettings() {
            settings.timeFormat = document.getElementById('timeFormat').value;
            settings.showAvatars = document.getElementById('showAvatars').checked;
            settings.customCSS = document.getElementById('customCSS').value;
            
            localStorage.setItem('timeFormat', settings.timeFormat);
            localStorage.setItem('showAvatars', settings.showAvatars);
            localStorage.setItem('customCSS', settings.customCSS);
            
            applySettings();
        }

        function applySettings() {
            const avatars = document.querySelectorAll('.message-avatar, .user-avatar');
            avatars.forEach(avatar => {
                avatar.style.display = settings.showAvatars ? 'flex' : 'none';
            });
            renderMessages();
        }

        function applyCustomCSS() {
            let customCSS = document.getElementById('customCSS').value;
            let styleElement = document.getElementById('customStyles');
            
            if (!styleElement) {
                styleElement = document.createElement('style');
                styleElement.id = 'customStyles';
                document.head.appendChild(styleElement);
            }
            
            styleElement.textContent = customCSS;
            saveSettings();
        }

        function resetCustomCSS() {
            document.getElementById('customCSS').value = '';
            applyCustomCSS();
        }

        function toggleSettings() {
            const menu = document.getElementById('settingsMenu');
            menu.classList.toggle('active');
            if (menu.classList.contains('active')) {
                loadSettings();
            }
        }

        document.addEventListener('click', function(event) {
            const menu = document.getElementById('settingsMenu');
            const menuBtn = document.querySelector('.menu-btn');
            if (menu && menuBtn && !menu.contains(event.target) && !menuBtn.contains(event.target) && menu.classList.contains('active')) {
                menu.classList.remove('active');
                saveSettings();
            }
        });

        function showTab(tab) {
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            document.getElementById('loginForm').classList.toggle('hidden', tab !== 'login');
            document.getElementById('registerForm').classList.toggle('hidden', tab !== 'register');
            const activeTab = document.querySelector(`.auth-tab[onclick="showTab('${tab}')"]`);
            if (activeTab) {
                activeTab.classList.add('active');
            }
        }

        function showAuth() {
            document.getElementById('authContainer').classList.remove('hidden');
            document.getElementById('chatContainer').classList.add('hidden');
        }

        function showChat() {
            document.getElementById('authContainer').classList.add('hidden');
            document.getElementById('chatContainer').classList.remove('hidden');
            document.getElementById('userWelcome').textContent = `Welcome ${currentUser.username}`;
            
            const userAvatar = document.getElementById('userAvatar');
            userAvatar.innerHTML = '';
            if (currentUser.avatar) {
                const img = document.createElement('img');
                img.src = currentUser.avatar;
                img.alt = currentUser.username;
                userAvatar.appendChild(img);
            } else {
                userAvatar.innerHTML = '<i class="fas fa-user"></i>';
            }
            
            loadSettings();
            loadInitialMessages();
            setInterval(checkForNewMessages, 1000);
        }

        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('avatarPreview').src = event.target.result;
                    document.getElementById('avatarPreview').classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('imageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    selectedImage = event.target.result;
                    document.getElementById('imagePreview').src = selectedImage;
                    document.getElementById('imagePreviewContainer').style.display = 'flex';
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        const chatMessages = document.getElementById('chatMessages');
        chatMessages.addEventListener('scroll', function() {
            const scrollTop = chatMessages.scrollTop;
            const scrollHeight = chatMessages.scrollHeight;
            const clientHeight = chatMessages.clientHeight;
            isUserScrolling = scrollTop + clientHeight < scrollHeight - 10;
        });

        function removeSelectedImage() {
            selectedImage = null;
            document.getElementById('imagePreviewContainer').style.display = 'none';
            document.getElementById('imageInput').value = '';
        }

        function register() {
            const username = document.getElementById('registerUsername').value.trim();
            const password = document.getElementById('registerPassword').value.trim();
            const avatar = document.getElementById('avatarPreview').src.includes('data:image') ? document.getElementById('avatarPreview').src : '';

            if (!username || !password) {
                alert('Please fill all fields');
                return;
            }

            const data = {
                action: 'register',
                username: username,
                password: password,
                avatar: avatar
            };

            fetch('index.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    localStorage.setItem('chat_token', result.token);
                    currentUser = {username: result.username, avatar: avatar, token: result.token};
                    showChat();
                } else {
                    alert(result.message);
                }
            })
            .catch(error => {
                alert('Network error. Please try again.');
            });
        }

        function login() {
            const username = document.getElementById('loginUsername').value.trim();
            const password = document.getElementById('loginPassword').value.trim();

            if (!username || !password) {
                alert('Please fill all fields');
                return;
            }

            const data = {
                action: 'login',
                username: username,
                password: password
            };

            fetch('index.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    localStorage.setItem('chat_token', result.token);
                    verifyToken(result.token); 
                } else {
                    alert(result.message);
                }
            })
            .catch(error => {
                alert('Network error. Please try again.');
            });
        }

        function verifyToken(token) {
            fetch(`index.php?action=verify_token&token=${token}`)
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    currentUser = {username: result.username, avatar: result.avatar, token: token};
                    showChat();
                } else {
                    localStorage.removeItem('chat_token');
                    showAuth();
                }
            })
            .catch(error => {
                console.log('Token verification failed');
                showAuth();
            });
        }

        function logout() {
            localStorage.removeItem('chat_token');
            currentUser = null;
            selectedImage = null;
            currentMessages.clear();
            showAuth();
            removeSelectedImage();
            showTab('login');
        }

        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();

            if ((message || selectedImage) && currentUser) {
                const tempId = 'temp_' + Date.now();
                addTempMessage(tempId, message, selectedImage);

                const data = {
                    action: 'send_message',
                    token: currentUser.token,
                    message: message,
                    image: selectedImage
                };

                fetch('index.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(result => {
                    if (result.success) {
                        removeTempMessage(tempId);
                        addMessageToChat(result.message);
                        messageInput.value = '';
                        removeSelectedImage();
                    } else {
                        removeTempMessage(tempId);
                        alert(result.message);
                    }
                })
                .catch(error => {
                    removeTempMessage(tempId);
                    alert('Failed to send message. Please try again.');
                });
            }
        }

        function addTempMessage(tempId, message, image) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message user-message';
            messageDiv.id = tempId;
            messageDiv.style.opacity = '0.7';

            let avatarContent = '';
            if (currentUser.avatar && settings.showAvatars) {
                avatarContent = `<img src="${currentUser.avatar}" alt="${currentUser.username}">`;
            } else if (settings.showAvatars) {
                avatarContent = '<i class="fas fa-user"></i>';
            }

            let content = '';
            if (settings.showAvatars) {
                content = `
                    <div class="message-header">
                        <div class="message-avatar">
                            ${avatarContent}
                        </div>
                        <span class="message-username">${currentUser.username}</span>
                    </div>
                `;
            } else {
                content = `<div class="message-username">${currentUser.username}</div>`;
            }

            if (message) {
                content += `<div>${message}</div>`;
            }

            if (image) {
                content += `<img src="${image}" alt="Image">`;
            }

            content += `<div class="message-time">Sending...</div>`;
            messageDiv.innerHTML = content;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function removeTempMessage(tempId) {
            const tempMessage = document.getElementById(tempId);
            if (tempMessage) {
                tempMessage.remove();
            }
        }

        function loadInitialMessages() {
            if (!currentUser) return;

            fetch(`index.php?action=get_messages&t=${Date.now()}`)
            .then(r => r.json())
            .then(messages => {
                currentMessages.clear();
                if (Array.isArray(messages)) {
                    messages.forEach(msg => currentMessages.set(msg.id, msg));
                    renderMessages();
                }
            })
            .catch(error => {
                console.log('Failed to load initial messages.');
            });
        }

        function checkForNewMessages() {
            if (!currentUser) return;

            fetch(`index.php?action=get_messages&t=${Date.now()}`)
            .then(r => r.json())
            .then(messages => {
                if (Array.isArray(messages)) {
                    let hasNewMessages = false;
                    
                    messages.forEach(msg => {
                        if (!currentMessages.has(msg.id)) {
                            hasNewMessages = true;
                            addMessageToChat(msg);
                        }
                    });

                    if (hasNewMessages) {
                        messages.forEach(msg => currentMessages.set(msg.id, msg));
                    }
                }
            })
            .catch(error => {
                console.log('Failed to check for new messages.');
            });
        }

        function addMessageToChat(message) {
            if (currentMessages.has(message.id)) return;
            
            currentMessages.set(message.id, message);
            
            const chatMessages = document.getElementById('chatMessages');
            const isAtBottom = chatMessages.scrollHeight - chatMessages.clientHeight - chatMessages.scrollTop < 100; 
            
            const messageElement = createMessageElement(message);
            chatMessages.appendChild(messageElement);
            
            if (!isUserScrolling || isAtBottom) {
                setTimeout(() => {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }, 100);
            }
        }

        function renderMessages() {
            const chatMessages = document.getElementById('chatMessages');
            const currentScroll = chatMessages.scrollTop;
            const isAtBottom = chatMessages.scrollHeight - chatMessages.clientHeight - chatMessages.scrollTop < 100;
            
            chatMessages.innerHTML = '';
            
            currentMessages.forEach(message => {
                const messageElement = createMessageElement(message);
                chatMessages.appendChild(messageElement);
            });
            
            if (!isUserScrolling || isAtBottom) {
                setTimeout(() => {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }, 100);
            } else {
                chatMessages.scrollTop = currentScroll;
            }
        }

        function createMessageElement(message) {
            const messageDiv = document.createElement('div');
            const isCurrentUser = currentUser && message.username === currentUser.username;
            messageDiv.className = `message ${isCurrentUser ? 'user-message' : 'other-message'}`;
            messageDiv.setAttribute('data-message-id', message.id);

            let avatarContent = '';
            if (message.avatar && settings.showAvatars) {
                avatarContent = `<img src="${message.avatar}" alt="${message.username}">`;
            } else if (settings.showAvatars) {
                avatarContent = '<i class="fas fa-user"></i>';
            }

            let content = '';
            if (settings.showAvatars) {
                content = `
                    <div class="message-header">
                        <div class="message-avatar">
                            ${avatarContent}
                        </div>
                        <span class="message-username">${message.username}</span>
                    </div>
                `;
            } else {
                content = `<div class="message-username">${message.username}</div>`;
            }

            if (message.message) {
                content += `<div>${message.message}</div>`;
            }

            if (message.image) {
                content += `<img src="${message.image}" alt="Image">`;
            }

            content += `<div class="message-time">${formatTime(message.timestamp)}</div>`;

            messageDiv.innerHTML = content;
            return messageDiv;
        }

        function formatTime(timestamp) {
            const date = new Date(timestamp);
            
            const timeOnly = date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });

            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const dateOnly = `${day}/${month}/${year}`;

            if (settings.timeFormat === 'time') {
                return timeOnly;
            } else if (settings.timeFormat === 'date') {
                return dateOnly;
            } else {
                return `${dateOnly} ${timeOnly}`;
            }
        }

    </script>
</body>
</html>
