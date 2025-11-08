<?php
// تحميل نظام الترجمة
require_once 'includes/translator.php';

require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$user_id = $_SESSION['user_id'];
$auction_id = isset($_GET['id']) ? clean($_GET['id']) : null;

if (!$auction_id) {
    redirect('dashboard.php');
}

// جلب معلومات الشات
$stmt = $conn->prepare("
    SELECT 
        sc.*,
        ch.id as chat_id,
        a.id as auction_id,
        v.brand, v.model, v.year,
        seller.username as seller_name,
        seller.id as seller_id,
        buyer.username as buyer_name,
        buyer.id as buyer_id
    FROM sale_chats ch
    JOIN sales_confirmations sc ON ch.sale_confirmation_id = sc.id
    JOIN auctions a ON ch.auction_id = a.id
    JOIN vehicles v ON a.vehicle_id = v.id
    JOIN users seller ON ch.seller_id = seller.id
    JOIN users buyer ON ch.buyer_id = buyer.id
    WHERE ch.auction_id = ? AND (ch.seller_id = ? OR ch.buyer_id = ?)
");
$stmt->execute([$auction_id, $user_id, $user_id]);
$chat = $stmt->fetch();

if (!$chat) {
    redirect('dashboard.php');
}

$is_seller = ($chat['seller_id'] == $user_id);
$other_user = $is_seller ? $chat['buyer_name'] : $chat['seller_name'];
$chat_id = $chat['chat_id'];

// معالجة إرسال رسالة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message = clean($_POST['message']);
    $voice_path = null;
    $message_type = 'text';
    
    // معالجة الرسالة الصوتية
    if (isset($_FILES['voice_file']) && $_FILES['voice_file']['size'] > 0) {
        $upload_dir = 'uploads/voice/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file = $_FILES['voice_file'];
        $ext = 'webm';
        $filename = 'voice_' . $user_id . '_' . time() . '.' . $ext;
        $destination = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $voice_path = $destination;
            $message_type = 'voice';
            $message = '[رسالة صوتية]';
        }
    }
    
    if (!empty($message)) {
        $stmt = $conn->prepare("
            INSERT INTO sale_messages (chat_id, sender_id, message, voice_message, message_type) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$chat_id, $user_id, $message, $voice_path, $message_type]);
        
        $receiver_id = $is_seller ? $chat['buyer_id'] : $chat['seller_id'];
        $notify_msg = $message_type == 'voice' ? '🎤 رسالة صوتية' : substr($message, 0, 50);
        sendNotification($receiver_id, 'رسالة جديدة', $notify_msg, 'message', $auction_id);
        
        header("Location: sale-chat.php?id=$auction_id#bottom");
        exit;
    }
}

// جلب الرسائل
$stmt = $conn->prepare("
    SELECT m.*, u.username 
    FROM sale_messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.chat_id = ?
    ORDER BY m.created_at ASC
");
$stmt->execute([$chat_id]);
$messages = $stmt->fetchAll();

// تحديث كمقروء
$stmt = $conn->prepare("UPDATE sale_messages SET is_read = TRUE WHERE chat_id = ? AND sender_id != ?");
$stmt->execute([$chat_id, $user_id]);
?>
<!DOCTYPE html>
<html lang="<?php echo currentLang(); ?>" dir="<?php echo textDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>محادثة - <?php echo htmlspecialchars($chat['brand'] . ' ' . $chat['model']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f8fafc;
            direction: rtl;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(37, 99, 235, 0.2);
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-info h2 {
            font-size: 1.3rem;
            margin-bottom: 5px;
        }
        .chat-info p {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .back-btn {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: 0.3s;
        }
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 1000px;
            margin: 0 auto;
            width: 100%;
            background: white;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
            background: #f8fafc;
        }
        .message {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            animation: slideIn 0.3s;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message.sent {
            flex-direction: row-reverse;
        }
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
        }
        .message.sent .message-avatar {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        }
        .message-content {
            max-width: 70%;
        }
        .message-bubble {
            background: white;
            padding: 12px 18px;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            word-wrap: break-word;
        }
        .message.sent .message-bubble {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
        }
        .message-time {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 5px;
            padding: 0 5px;
        }
        .voice-message {
            background: #f1f5f9;
            padding: 12px 18px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .message.sent .voice-message {
            background: rgba(255,255,255,0.2);
        }
        .voice-message audio {
            max-width: 250px;
        }
        .voice-icon {
            width: 35px;
            height: 35px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .message-form {
            background: white;
            padding: 20px 30px;
            border-top: 2px solid #f1f5f9;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .voice-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #10b981;
            color: white;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .voice-btn:hover {
            background: #059669;
            transform: scale(1.1);
        }
        .voice-btn.recording {
            background: #ef4444;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            50% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
        }
        .message-input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 25px;
            font-size: 1rem;
            transition: 0.3s;
        }
        .message-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .send-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .send-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }
    </style>

    <!-- Responsive & Mobile Menu CSS -->
    <link rel="stylesheet" href="assets/css/responsive.css">

</head>
<body>
    <div class="header">
        <div class="container">
                                <div style="position: fixed; top: 20px; left: 20px; z-index: 1000;">
        <?php include 'includes/lang-switcher.php'; ?>
    </div>
    <script src="js/auto-translate.js"></script>

            <div class="chat-header">
                <div class="chat-info">
                    <h2>
                        <i class="fas fa-comments"></i>
                        محادثة: <?php echo htmlspecialchars($chat['brand'] . ' ' . $chat['model'] . ' ' . $chat['year']); ?>
                    </h2>
                    <p>
                        <i class="fas fa-user"></i>
                        <?php echo $is_seller ? 'المشتري' : 'البائع'; ?>: <?php echo htmlspecialchars($other_user); ?>
                    </p>
                </div>
                <a href="my-chats.php" class="back-btn">
                    <i class="fas fa-arrow-right"></i>
                    رجوع
                </a>
            </div>
        </div>
    </div>

    <div class="chat-container">
        <div class="messages-area" id="messagesArea">
            <?php foreach ($messages as $msg): ?>
                <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                    <div class="message-avatar"><?php echo strtoupper(substr($msg['username'], 0, 1)); ?></div>
                    <div class="message-content">
                        <?php if ($msg['message_type'] == 'voice' && $msg['voice_message']): ?>
                            <div class="message-bubble">
                                <div class="voice-message">
                                    <div class="voice-icon">
                                        <i class="fas fa-microphone"></i>
                                    </div>
                                    <audio controls>
                                        <source src="<?php echo htmlspecialchars($msg['voice_message']); ?>" type="audio/webm">
                                        متصفحك لا يدعم تشغيل الصوت
                                    </audio>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="message-bubble"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                        <?php endif; ?>
                        <div class="message-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div id="bottom"></div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="message-form" id="messageForm">
            <button type="button" class="voice-btn" id="voiceBtn">
                <i class="fas fa-microphone"></i>
            </button>
            <input type="text" name="message" class="message-input" id="messageInput" placeholder="اكتب رسالتك..." autocomplete="off">
            <input type="file" name="voice_file" id="voiceFile" style="display: none;" accept="audio/*">
            <button type="submit" name="send_message" class="send-btn" id="sendBtn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>

<script>
const messagesArea = document.getElementById('messagesArea');
let lastMessageId = <?php echo !empty($messages) ? end($messages)['id'] : 0; ?>;

messagesArea.scrollTop = messagesArea.scrollHeight;

// Voice Recording Variables
let mediaRecorder = null;
let audioChunks = [];
let isRecording = false;

const voiceBtn = document.getElementById('voiceBtn');
const messageInput = document.getElementById('messageInput');
const messageForm = document.getElementById('messageForm');

// Voice Recording Handler
voiceBtn.addEventListener('click', async () => {
    if (!isRecording) {
        // Start Recording
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ 
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    sampleRate: 44100
                } 
            });
            
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];
            
            mediaRecorder.addEventListener('dataavailable', event => {
                audioChunks.push(event.data);
            });
            
            mediaRecorder.addEventListener('stop', () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                
                // Upload via FormData
                const formData = new FormData();
                formData.append('voice_file', audioBlob, 'voice.webm');
                formData.append('message', '[رسالة صوتية]');
                formData.append('send_message', '1');
                
                fetch('sale-chat.php?id=<?php echo $auction_id; ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        window.location.href = 'sale-chat.php?id=<?php echo $auction_id; ?>#bottom';
                    } else {
                        alert('فشل الإرسال');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في الإرسال');
                });
                
                // Stop all tracks
                stream.getTracks().forEach(track => track.stop());
            });
            
            mediaRecorder.start();
            isRecording = true;
            voiceBtn.classList.add('recording');
            voiceBtn.innerHTML = '<i class="fas fa-stop"></i>';
            messageInput.placeholder = '🔴 جاري التسجيل...';
            messageInput.disabled = true;
            
            console.log('Recording started');
            
        } catch (err) {
            console.error('Microphone error:', err);
            alert('يرجى السماح باستخدام المايكروفون من إعدادات المتصفح');
        }
    } else {
        // Stop Recording
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
            isRecording = false;
            voiceBtn.classList.remove('recording');
            voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
            messageInput.placeholder = 'اكتب رسالتك...';
            messageInput.disabled = false;
            
            console.log('Recording stopped');
        }
    }
});

// Prevent form submit while recording
messageForm.addEventListener('submit', (e) => {
    if (isRecording) {
        e.preventDefault();
        alert('يرجى إيقاف التسجيل أولاً');
        return false;
    }
});

// AJAX Auto Refresh
async function fetchNew() {
    try {
        const res = await fetch(`ajax/get-new-messages.php?chat_id=<?php echo $chat_id; ?>&last_id=${lastMessageId}`);
        const data = await res.json();
        
        if (data.messages && data.messages.length > 0) {
            data.messages.forEach(msg => {
                const div = document.createElement('div');
                div.className = 'message ' + (msg.is_mine ? 'sent' : 'received');
                
                let content = '';
                if (msg.message_type === 'voice' && msg.voice_message) {
                    content = `
                        <div class="message-bubble">
                            <div class="voice-message">
                                <div class="voice-icon"><i class="fas fa-microphone"></i></div>
                                <audio controls>
                                    <source src="${msg.voice_message}" type="audio/webm">
                                    <source src="${msg.voice_message}" type="audio/mpeg">
                                </audio>
                            </div>
                        </div>
                    `;
                } else {
                    content = `<div class="message-bubble">${msg.message.replace(/\n/g, '<br>')}</div>`;
                }
                
                div.innerHTML = `
                    <div class="message-avatar">${msg.username.charAt(0).toUpperCase()}</div>
                    <div class="message-content">
                        ${content}
                        <div class="message-time">${msg.time}</div>
                    </div>
                `;
                messagesArea.insertBefore(div, document.getElementById('bottom'));
                lastMessageId = msg.id;
            });
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }
    } catch (error) {
        console.error('Fetch error:', error);
    }
}

setInterval(fetchNew, 3000);
</script>
    <!-- Mobile Menu & Responsive JavaScript -->
    <script src="assets/js/mobile-menu.js"></script>

</body>
</html>