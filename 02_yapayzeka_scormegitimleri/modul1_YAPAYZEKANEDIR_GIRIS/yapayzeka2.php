<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

$apiKey = 'sk-0X2KrChH7_6645bWJEWlpsHEwV5FnzG2TFjR3ZbALAT3BlbkFJmi60t6s9vk4llqrOcviBWPdHb-A_jKDtMvctuQmh0A';

// --------------------
// Kural Seti
// --------------------
$RULES = [
    ['id' => 'R020', 'title' => 'Dil: Türkçe', 'desc' => 'Yanıt dili Türkçe olmalı.', 'mandatory' => true,
        'check' => fn($t) => preg_match('/[çğıöşüÇĞİÖŞÜ]/u', $t)],
    ['id' => 'R040', 'title' => 'Konu dışına çıkma', 'desc' => 'Yapay Zekâ Temelleri dışına çıkma.', 'mandatory' => true],
    ['id' => 'R050', 'title' => 'Gizli veri yok', 'desc' => 'Kişisel veya kurumsal gizli bilgi isteme.', 'mandatory' => true,
        'check' => '/(tc\s*kimlik|iban|banka|adres|telefon|şifre)/iu'],
    ['id' => 'R070', 'title' => 'Hitap biçimi', 'desc' => 'Use informal Turkish second-person singular ("sen" form). Avoid formal/plural "siz" form.', 'mandatory' => true,
        'check' => function($t) {
            $trimmed = trim($t);
            $length = mb_strlen($trimmed);
            if (preg_match('/\bsen(?!t)\b|\bsenin\b|\bsene\b|\bsende\b|\bsenden\b|\bsensin\b/iu', $trimmed)) return true;
            if ($length < 120 || preg_match('/yapay zek|makine öğren|tanımı|nedir|örnek/i', $trimmed)) return true;
            return false;
        }],
];

// --------------------
// Yardımcı Fonksiyonlar
// --------------------
function log_to_file($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);
    $logFile = $logDir . '/similarity_log_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

function server_validate($text, $RULES) {
    $fails = [];
    foreach ($RULES as $r) {
        if (!empty($r['mandatory']) && isset($r['check'])) {
            if (is_string($r['check']) && preg_match($r['check'], $text))
                $fails[] = $r['id'];
            elseif (is_callable($r['check']) && !$r['check']($text))
                $fails[] = $r['id'];
        }
    }
    return $fails;
}

function read_training_context() {
    $contextPath = __DIR__ . '/yapayzeka_context.md';
    if (!is_file($contextPath)) {
        return "UYARI: yapayzeka_context.md bulunamadı. Yine de konu AI temel eğitimi.";
    }
    $ctx = file_get_contents($contextPath);
    if ($ctx === false || trim($ctx) === '') {
        return "UYARI: yapayzeka_context.md okunamadı. Yine de konu AI temel eğitimi.";
    }
    return $ctx;
}

// --------------------
// Embedding tabanlı context kontrolü
// --------------------
function get_embedding($apiKey, $text) {
    $post = ['model' => 'text-embedding-3-small', 'input' => $text];
    $ch = curl_init('https://api.openai.com/v1/embeddings');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($post),
        CURLOPT_TIMEOUT => 30
    ]);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    return $data['data'][0]['embedding'] ?? [];
}

function cosine_similarity($a, $b) {
    $dot = 0.0; $normA = 0.0; $normB = 0.0;
    for ($i = 0; $i < count($a); $i++) {
        $dot += $a[$i] * $b[$i];
        $normA += $a[$i] ** 2;
        $normB += $b[$i] ** 2;
    }
    return $dot / (sqrt($normA) * sqrt($normB));
}

function ensure_context_embedding($apiKey, $contextText) {
    $cacheFile = __DIR__ . '/context_embedding.json';
    if (is_file($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if (!empty($data)) return $data;
    }
    $embedding = get_embedding($apiKey, $contextText);
    file_put_contents($cacheFile, json_encode($embedding));
    return $embedding;
}

// --------------------
// OpenAI chat çağrısı
// --------------------
function openai_chat($apiKey, $conversation) {
    $postData = [
        'model' => 'gpt-4o',
        'messages' => $conversation
    ];
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_TIMEOUT => 30
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) throw new RuntimeException('Curl hatası: ' . $err);
    if ($httpCode !== 200) throw new RuntimeException("HTTP $httpCode: $response");
    $responseData = json_decode($response, true);
    return $responseData['choices'][0]['message']['content'] ?? '';
}

// --------------------
// Oturum
// --------------------
session_start();
if (!isset($_SESSION['conversation'])) {
    $_SESSION['conversation'] = [];
}

// --------------------
// POST İşleme
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $input = json_decode(file_get_contents('php://input'), true);
    $userMessage = trim($input['message'] ?? '');
    if ($userMessage === '') {
        echo json_encode(['error' => 'Boş mesaj gönderildi.']);
        exit;
    }

    $TRAINING_CONTEXT = read_training_context();
    $contextEmbedding = ensure_context_embedding($apiKey, $TRAINING_CONTEXT);
    $messageEmbedding = get_embedding($apiKey, $userMessage);

    // Cosine similarity ölçümü
    $similarity = cosine_similarity($contextEmbedding, $messageEmbedding);

    // Dinamik eşik ayarlama
    $msgLength = mb_strlen($userMessage);
    if ($msgLength < 150) {
		$threshold = 0.30;
	} elseif ($msgLength < 300) {
		$threshold = 0.60;
	} else {
		$threshold = 0.60;
	}


    // LOG: Benzerlik oranını dosyaya yaz
    log_to_file("Benzerlik: " . round($similarity, 4) . " | Threshold: {$threshold} | Mesaj: {$userMessage}");

    if ($similarity < $threshold) {
        log_to_file("Konu dışı sayıldı.");
        echo json_encode([
            'error' => 'Sanırım konu dışına çıktık 😊 Bu bölümde yalnızca "Yapay Zekâ Temelleri" üzerine konuşabiliriz.'
        ]);
        exit;
    }

    $_SESSION['conversation'][] = ['role' => 'user', 'content' => $userMessage];

    $conversationForAI = $_SESSION['conversation'];
    array_unshift($conversationForAI, [
        'role' => 'system',
        'content' => "Sen Mentor AI adında bir e-öğrenme asistanısın. 
'Yapay Zekâ Temelleri' eğitim modülündesin. 
Kullanıcıya sadece bu bağlamla ilgili konularda yanıt ver. 
Eğer konu dışı bir şey sorarsa, kesinlikle cevap verme ve kibarca uyar:
'Sanırım konu dışına çıktık 😊 Bu bölümde yalnızca Yapay Zekâ Temellerine odaklanalım.'

===== BAĞLAM =====
" . $TRAINING_CONTEXT
    ]);

    try {
        $aiReply = openai_chat($apiKey, $conversationForAI);
        $fails = server_validate($aiReply, $RULES);
        if (!empty($fails)) {
            log_to_file("⚠️ AI cevabı kurallara takıldı.");
            echo json_encode(['error' => 'Bu bölümde Yapay Zekâ Temellerine odaklanalım 😊']);
            exit;
        }
        $_SESSION['conversation'][] = ['role' => 'assistant', 'content' => $aiReply];
        $finalMessage = !isset($_SESSION['showedNowYourTurn'])
            ? ($_SESSION['showedNowYourTurn'] = true) || "Şimdi sıra sende! Bu eğitim hakkında dilediğin kadar soru sorabilir veya sonraki ekrana geçebilirsin."
            : "Eğer daha sorun varsa alabilirim ya da bir sonraki ekrana geçebilirsin 😊";

        log_to_file("✅ AI yanıt verdi. Cevap uzunluğu: " . mb_strlen($aiReply) . " karakter.");
        echo json_encode([
            'success' => true,
            'reply' => $aiReply,
            'finalMessage' => $finalMessage
        ]);
    } catch (Throwable $e) {
        log_to_file("❌ Hata: " . $e->getMessage());
        echo json_encode(['error' => 'Bir hata oluştu: ' . $e->getMessage()]);
    }
    exit;
}
?>



<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Yapay Zeka Eğitimi - AkademiMentor</title>
  <style>
    :root{
      --chat-bg:#fff;
      --chat-border:#e6e8eb;
      --chat-radius:16px;
      --chat-shadow:0 8px 24px rgba(0,0,0,.08);
      --icon-size: 100px;
      --header-h: calc(var(--icon-size) + 24px);
      --input-h:72px;
      --header-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    }

    body{
      margin:0; min-height:100svh;
      background:linear-gradient(180deg,#f4f6f8 0%,#eef1f4 100%);
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
      color:#101828;
	  font-size: 210%;
    }

    .layout{
      display:grid;
      grid-template-columns: 1fr;
      grid-template-rows: 100svh;
      width:100vw; height:100svh; overflow:hidden;
    }

    .chat-area{ padding:16px; display:grid; height:100%; }

    .chat-panel{
      border:1px solid var(--chat-border); background:var(--chat-bg);
      border-radius:var(--chat-radius); box-shadow:var(--chat-shadow);
      display:grid; grid-template-rows:var(--header-h) 1fr var(--input-h);
      overflow:hidden; height:calc(100svh - 32px);
    }

    .chat-header{
      display:flex; align-items:center; justify-content:space-between;
      padding:0 16px; background: var(--header-gradient);
      color:#fff; font-weight:600;
    }
    .title-group{ display:flex; align-items:center; gap:12px; min-width:0; }
    .icon-slot{
	  width: var(--icon-size);
	  height: var(--icon-size);
	  border-radius: 12px;
	  background: rgba(255,255,255,.18);
	  display:flex; align-items:center; justify-content:center;
	  overflow:hidden; flex:0 0 var(--icon-size);
	}

	.icon-slot img, .icon-slot svg{
	  width: 100%;
	  height: 100%;
	  object-fit: contain;
	}

    .chat-messages{ overflow:auto; padding:16px; background:#fcfdff; }

    .message{max-width:70ch; padding:10px 12px; margin:8px 0;
      border-radius:12px; line-height:1.45; border:1px solid #edf0f4; background:#fff;}
    .message.user{ background:#f6f9ff; }
    .message .message-sender{ font-size:0.75em; color:#6b7280; margin-bottom:4px; }
    .message .message-bubble{ white-space:pre-wrap; word-wrap:break-word; }

    .typing-indicator{ display:none; margin:8px 0; }
    .typing-indicator.show{ display:block; }
    .typing-bubble{ display:flex; align-items:center; gap:8px; padding:8px 10px;
      border:1px solid #edf0f4; border-radius:12px; background:#fff; }
    .typing-dots{ display:flex; gap:4px; }
    .typing-dot{ width:6px; height:6px; border-radius:50%; background:#9aa4b2; animation:blink 1s infinite; }
    .typing-dot:nth-child(2){ animation-delay:.2s }
    .typing-dot:nth-child(3){ animation-delay:.4s }
    @keyframes blink{ 0%,80%,100%{opacity:.2} 40%{opacity:1} }

    .chat-input{
      border-top:1px solid var(--chat-border); background:#fff;
      display:grid; grid-template-columns:minmax(0,1fr) 112px;
      gap:8px; padding:10px;
    }
    .chat-input input,.chat-input textarea{
      box-sizing:border-box; min-width:0; width:100%; height:48px; resize:none;
      padding:10px 12px; border:1px solid #d7dde3; border-radius:12px;
      font:inherit; outline:none; background:#fff;
    }
    .send-btn{
      width:112px; height:48px; padding:0 16px;
      border:1px solid #155eef; background:#155eef; color:#fff;
      border-radius:12px; cursor:pointer; font-weight:600;
    }
  </style>
</head>
<body>
  <div class="layout">
    <main class="chat-area">
      <section class="chat-panel">
        <header class="chat-header">
          <div class="title-group">
            <div class="icon-slot">
              <img src="mentor-robot.png" alt="Logo">
            </div>
            <div class="title">Yapay Zeka Eğitimi - AkademiMentor</div>
          </div>
          <div class="right-actions"></div>
        </header>

        <div class="chat-messages" id="messages">
          <div id="chatbox">
            <div class="message ai">
              <div class="message-sender">AkademiMentor</div>
              <div class="message-bubble">Sence Yapay Zekâ ile Makine Öğrenmesi arasındaki en temel fark nedir, kısaca yazabilir misin?</div>
            </div>
          </div>

          <div class="typing-indicator" id="typingIndicator">
            <div class="typing-bubble">
              <div class="message-sender">AkademiMentor yazıyor...</div>
              <div class="typing-dots">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="chat-input">
          <input type="text" id="userInput" placeholder="Cevabınızı yazın veya soru sorun..." />
          <button class="send-btn" id="sendButton" type="button" onclick="sendMessage()">Gönder</button>
        </div>
      </section>
    </main>
  </div>

  <script>
    const chatbox = document.getElementById("chatbox");
    const input = document.getElementById("userInput");
    const sendButton = document.getElementById("sendButton");
    const typingIndicator = document.getElementById("typingIndicator");

    input.addEventListener("keypress", function(e) {
      if (e.key === "Enter") { e.preventDefault(); sendMessage(); }
    });

    function addMessage(from, text) {
      const messageDiv = document.createElement('div');
      messageDiv.className = `message ${from === 'Sen' ? 'user' : 'ai'}`;
      messageDiv.innerHTML = `
        <div class="message-sender">${from}</div>
        <div class="message-bubble">${text}</div>
      `;
      chatbox.appendChild(messageDiv);
      chatbox.parentElement.scrollTop = chatbox.parentElement.scrollHeight;
    }

    function showTypingIndicator() {
      typingIndicator.classList.add('show');
      chatbox.parentElement.scrollTop = chatbox.parentElement.scrollHeight;
    }
    function hideTypingIndicator() {
      typingIndicator.classList.remove('show');
    }

    function setButtonState(loading) {
      if (loading) {
        sendButton.disabled = true;
        sendButton.textContent = "Gönderiliyor...";
        input.disabled = true;
      } else {
        sendButton.disabled = false;
        sendButton.textContent = "Gönder";
        input.disabled = false;
        input.focus();
      }
    }

    async function sendMessage() {
      const userText = input.value.trim();
      if (!userText) return;

      setButtonState(true);
      addMessage("Sen", userText);
      input.value = "";

      showTypingIndicator();

      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ message: userText })
        });

        if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }

        const data = await response.json();
        hideTypingIndicator();

        if (data.success && data.reply) {
          addMessage("AkademiMentor", data.reply.trim());
          if (data.finalMessage) {
            setTimeout(() => addMessage("AkademiMentor", data.finalMessage), 1000);
          }
        } else {
          addMessage("AkademiMentor", "" + (data.error || "Bilinmeyen hata"));
        }
      } catch (error) {
        console.error('Fetch error:', error);
        hideTypingIndicator();
        addMessage("AkademiMentor", "Bağlantı hatası oluştu. Lütfen tekrar deneyin.");
      } finally {
        setButtonState(false);
      }
    }

    window.addEventListener('load', () => input.focus());
  </script>
</body>
</html>