<?php
// omnichannel.php - 3 Şıklı Quiz + 6 Açık Uçlu Soru + threshold tabanlı konu denetimi (konu dışıysa aynı soruyu tekrar et + detaylı log)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ---- API Anahtarı ----
$apiKey = getenv('OPENAI_API_KEY') ?: 'sk-0X2KrChH7_6645bWJEWlpsHEwV5FnzG2TFjR3ZbALAT3BlbkFJmi60t6s9vk4llqrOcviBWPdHb-A_jKDtMvctuQmh0A';

// ---- Yardımcı fonksiyonlar ----
function read_training_context() {
    $path = __DIR__ . '/yapayzeka_context.md';
    if (!is_file($path)) return "Yapay Zekâ Temelleri eğitimi hakkında genel bilgiler.";
    $txt = file_get_contents($path);
    return trim($txt) ?: "Yapay Zekâ Temelleri eğitimi hakkında genel bilgiler.";
}

function log_message($msg) {
    $logPath = __DIR__ . '/logs/yapayzeka3_logs.txt';
    $time = date('[Y-m-d H:i:s] ');
    file_put_contents($logPath, $time . $msg . "\n", FILE_APPEND);
}

function get_embedding($apiKey, $text) {
    $ch = curl_init('https://api.openai.com/v1/embeddings');
    $data = ['model' => 'text-embedding-3-small', 'input' => $text];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 20
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) throw new RuntimeException("Embedding hatası: $err");
    $data = json_decode($res, true);
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

function ensure_context_embedding($apiKey, $context) {
    $cache = __DIR__ . '/omnichannel_context_embedding.json';
    if (is_file($cache)) {
        $data = json_decode(file_get_contents($cache), true);
        if (!empty($data)) return $data;
    }
    $emb = get_embedding($apiKey, $context);
    file_put_contents($cache, json_encode($emb));
    return $emb;
}

// ---- Ana iş mantığı ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $input = json_decode(file_get_contents('php://input'), true);
    $userMessage = trim($input['message'] ?? '');

    if ($userMessage === '') {
        echo json_encode(['error' => 'Boş mesaj gönderildi.']); exit;
    }

    // Context ve embedding
    $contextText = read_training_context();
    $contextEmb = ensure_context_embedding($apiKey, $contextText);
    $msgEmb = get_embedding($apiKey, $userMessage);

    // Benzerlik hesapla
    $similarity = cosine_similarity($contextEmb, $msgEmb);
    $msgLength = mb_strlen($userMessage);
    if ($msgLength < 150) $threshold = 0.30;
    elseif ($msgLength < 300) $threshold = 0.40;
    else $threshold = 0.60;

    $level = ($similarity >= 0.8) ? 'YÜKSEK' :
              (($similarity >= 0.6) ? 'ORTA' :
              (($similarity >= 0.4) ? 'DÜŞÜK' : 'ÇOK DÜŞÜK'));

    // 📜 Log: Benzerlik analizi
    log_message("Benzerlik: " . round($similarity, 4) . " ($level) | Threshold: $threshold | Mesaj: $userMessage");

    // Konu dışıysa
    if ($similarity < $threshold) {
        log_message("Konu dışı — soru tekrar edilecek.");
        echo json_encode([
            'reply' => 'Sanırım konu dışına çıktık 😊 Bu bölümde yalnızca Yapay Zekâ Temelleri üzerine konuşuyoruz. Aynı soruyu tekrar dene lütfen.',
            'repeat' => true,
            'similarity' => round($similarity, 4),
            'threshold' => $threshold
        ]);
        exit;
    }

    // Konu içi: kısa geri bildirim iste
    $systemPrompt = <<<SYS
Sen Mentor AI'sın. Kullanıcı bir eğitim değerlendirme sorusuna az önce cevabını yazdı.
Görev: Cevabı yargılamadan, doğruluk kontrolü yapmadan 1-2 cümlelik kısa bir geri bildirim ver.
Yeni soru sorma; en fazla 2 cümle yaz; emoji kullanma; Türkçe yaz.
SYS;

    $postData = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ],
        'temperature' => 0.6,
        'max_tokens' => 70,
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
        CURLOPT_TIMEOUT => 25
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        log_message("❌ Curl hatası: $err");
        echo json_encode(['reply' => 'Teşekkürler, yanıtını aldım. Devam edelim.']); exit;
    }

    $data = json_decode($response, true);
    $reply = $data['choices'][0]['message']['content'] ?? 'Teşekkürler, yanıtını aldım. Devam edelim.';

    log_message("✅ AI yanıt verdi. Cevap uzunluğu: " . mb_strlen($reply) . " karakter. Soru tamamlandı, bir sonraki soruya geçiliyor.");
    echo json_encode([
        'reply' => $reply,
        'repeat' => false,
        'similarity' => round($similarity, 4),
        'threshold' => $threshold
    ]);
    exit;
}
?>



<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Yapay Zekâ Eğitimi Değerlendirmesi - AkademiMentor</title>
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

    /* Şıklar ve açıklama tasarımı (bozulmaması için korunmuştur) */
    .options-container { display:flex; flex-direction:column; gap:8px; margin-top:12px; }
    .option-button {
      padding: 12px 16px;
      border: 2px solid #e6e8eb;
      background: #fff;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 0.9em;
      text-align: left;
    }
    .option-button:hover { border-color:#155eef; background:#f6f9ff; }
    .option-button.correct { border-color:#10b981; background:#d1fae5; color:#065f46; }
    .option-button.incorrect { border-color:#ef4444; background:#fee2e2; color:#991b1b; }

    .explanation-box {
      background:#f0f9ff;
      border:1px solid #0ea5e9;
      border-radius:12px;
      padding:16px;
      margin:16px 0;
    }
  </style>

</head>
<body>
  <div class="layout">
    <main class="chat-area">
      <section class="chat-panel">
        <header class="chat-header">
          <div class="title-group">
            <div class="icon-slot"><img src="mentor-robot.png" alt="Logo" /></div>
            <div class="title">Yapay Zekâ Eğitimi Değerlendirmesi - AkademiMentor</div>
          </div>
        </header>

        <div class="chat-messages" id="messages"><div id="chatbox"></div></div>

        <div class="chat-input">
          <input type="text" id="userInput" placeholder="Cevabını yaz ve Enter’a bas..." />
          <button class="send-btn" id="sendButton" type="button" onclick="sendMessage()">Gönder</button>
        </div>
      </section>
    </main>
  </div>

  <script>
    const chatbox = document.getElementById("chatbox");
    const input = document.getElementById("userInput");

    // --- 1) ŞIKLI QUIZ (3 soru) ------------------------------------------------
    const mcq = [
      {
        q: "Yapay zekâ (AI) ile Makine Öğrenmesi (ML) arasındaki temel fark nedir?",
        options: [
          "AI insan benzeri zekâyı taklit eden geniş yaklaşım; ML veriden öğrenen bir alt alandır",
          "AI yalnızca kural tabanlıdır; ML sadece görüntü işleme yapar"
        ],
        correct: 0,
        exp: "AI geniş bir şemsiyedir; ML bu şemsiyenin içinde veriden örüntü öğrenen yöntemler bütünüdür."
      },
      {
        q: "Üretken yapay zekâ çıktılarıyla çalışırken hangi adım kritiktir?",
        options: [
          "Çıktıları doğrulamak ve gerektiğinde düzenlemek",
          "Her çıktıyı otomatik olarak paylaşmak"
        ],
        correct: 0,
        exp: "GenAI bazen hatalı/uydurma bilgi üretebilir; bu yüzden doğrulama ve düzenleme esastır."
      },
      {
        q: "Bir AI Agent’ı en doğru şekilde hangi ifade tanımlar?",
        options: [
          "Sadece metni özetleyen basit bir araç",
          "Hedefe yönelik olarak algılayıp karar veren ve eyleme geçen sistem"
        ],
        correct: 1,
        exp: "Agent, çok adımlı görevleri planlayıp uygulayabilir (ör. özet→görev→takvim)."
      }
    ];
    let mcqIndex = 0; // 0..2

    // --- 2) AÇIK UÇLU 6 SORU ---------------------------------------------------
    const openQuestions = [
      "Yapay zekâyı günlük hayatında fark etmeden kullandığın üç alana örnek verebilir misin?",
      "Bir satış raporunu incelerken, AI araçları sana nasıl yardımcı olabilir?",
      "Makine öğrenmesi ile kural tabanlı yapay zekâ arasındaki fark nedir, kısaca anlatabilir misin?",
      "AI Agent’ların sıradan AI araçlarından farkı nedir, günlük hayattan bir örnek verebilir misin?",
      "Derin öğrenme hangi alanlarda devrim yarattı, hatırlıyor musun?",
      "Yapay zekâya dair en yaygın önyargılardan biri nedir, bunun doğrusu neydi?"
    ];
    const closingMessages = [
      "Bu sorulara rahatça cevap verebiliyorsan, yapay zekâ uygulamalarını günlük hayatında güvenle ve verimli şekilde kullanmaya hazırsın.",
      "Cevaplarda zorlandığın olduysa, tekrar dönüp ilgili bölümü dinleyebilirsin.",
      "Unutma, yapay zekâ uygulamaları senin rakibin değil; senin daha üretken, daha yaratıcı, daha hızlı olmanı sağlayacak güçlü bir yardımcı.",
      "Onu doğru şekilde yönlendirirsen, seninle beraber çalışır. Ve en önemlisi: AI araçları asla senin yerine karar vermez; karar verici hâlâ sensin."
    ];

    // Aşamalar: 'mcq' -> 'open' -> 'closing' -> 'completed'
    let phase = 'mcq';
    let openIndex = 0;
    let awaitingAnswer = false;

    // ---- UI helpers ----
    function addMessage(from, text, isUser=false) {
      const div = document.createElement('div');
      div.className = `message ${isUser ? 'user' : 'ai'}`;
      div.innerHTML = `<div class="message-sender">${isUser ? 'Sen' : 'AkademiMentor'}</div>
                       <div class="message-bubble">${text}</div>`;
      chatbox.appendChild(div);
      chatbox.parentElement.scrollTop = chatbox.parentElement.scrollHeight;
    }
    function addMCQ(questionObj, idx) {
	  const wrap = document.createElement('div');
	  wrap.className = 'message ai';
	  let html = `<div class="message-sender">AkademiMentor</div>
				  <div class="message-bubble">${questionObj.q}
				  <div class="options-container" data-mcq="${idx}">`;
	  questionObj.options.forEach((opt, i) => {
		html += `<div class="option-button" onclick="selectMCQ(${idx},${i})">${String.fromCharCode(65+i)}) ${opt}</div>`;
	  });
	  html += `</div></div>`;
	  wrap.innerHTML = html;
	  chatbox.appendChild(wrap);
	  chatbox.parentElement.scrollTop = chatbox.parentElement.scrollHeight;
	}
    function explain(text) {
      const div = document.createElement('div');
      div.className = 'message ai';
      div.innerHTML = `<div class="message-sender">AkademiMentor</div>
                       <div class="message-bubble"><div class="explanation-box">${text}</div></div>`;
      chatbox.appendChild(div);
      chatbox.parentElement.scrollTop = chatbox.parentElement.scrollHeight;
    }

    // ---- MCQ flow ----
    window.selectMCQ = function(qIdx, chosen) {
      const container = document.querySelector(`[data-mcq="${qIdx}"]`);
      if (!container) return;
      const buttons = container.querySelectorAll('.option-button');
      buttons.forEach(b => b.style.pointerEvents = 'none');

      const correct = mcq[qIdx].correct;
      buttons[chosen].classList.add(chosen === correct ? 'correct' : 'incorrect');
      if (chosen !== correct) buttons[correct].classList.add('correct');

      setTimeout(() => {
        explain(mcq[qIdx].exp);
        mcqIndex++;
        if (mcqIndex < mcq.length) {
          setTimeout(() => addMCQ(mcq[mcqIndex], mcqIndex), 700);
        } else {
          // MCQ bitti -> OPEN başlasın
          phase = 'open';
          setTimeout(askOpenQuestion, 900);
        }
      }, 700);
    };

    function askOpenQuestion() {
      if (openIndex < openQuestions.length) {
        addMessage("AkademiMentor", `Soru ${openIndex + 1}: ${openQuestions[openIndex]}`);
        awaitingAnswer = true;
      } else {
        showClosing();
      }
    }

    function showClosing() {
	  phase = 'closing';
	  let i = 0;
	  const step = () => {
		if (i >= closingMessages.length) {
		  phase = 'completed';
		  addMessage("AkademiMentor", "Tebrikler, eğitimi tamamladın!");
		  return;
		}
		addMessage("AkademiMentor", "• " + closingMessages[i]);
		i++; setTimeout(step, 900);
	  };
	  step();
	}


    // ---- Send flow ----
    async function sendMessage() {
      const userText = input.value.trim();
      if (!userText) return;
      addMessage("Sen", userText, true);
      input.value = "";

      if (phase === 'open' && awaitingAnswer) {
        awaitingAnswer = false;
        try {
          const r = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: userText })
          });
          const data = await r.json();
          addMessage("AkademiMentor", data.reply || "Teşekkürler, yanıtını aldım. Devam edelim.");
			
			if (data.repeat) {
			  // Konu dışıysa aynı soruyu tekrar et
			  setTimeout(() => {
				addMessage("AkademiMentor", `Tekrar deneyelim 😊 ${openQuestions[openIndex]}`);
				awaitingAnswer = true;
			  }, 800);
			} else {
			  // Konu içiyse sonraki soruya geç
			  openIndex++;
			  setTimeout(askOpenQuestion, 700);
			}
			return;
			
        } catch(e) {
          addMessage("AkademiMentor", "Teşekkürler, yanıtını aldım. Devam edelim.");
        }
        openIndex++;
        setTimeout(askOpenQuestion, 700);
        return;
      }

      // MCQ aşamasında yazarsa: tebrik verme, sadece yönlendir
      if (phase === 'mcq') {
        addMessage("AkademiMentor", "Lütfen yukarıdaki soruyu seçeneklerden birini tıklayarak yanıtla.");
        return;
      }

      // Kapanış akarken (phase === 'closing'): araya mesaj girmesin -> hiç yanıt verme
      if (phase === 'closing') {
        return;
      }

      // Kapanış bitti (phase === 'completed'): HER zaman tebrik ver
      if (phase === 'completed') {
        addMessage("AkademiMentor", "Tebrikler, eğitimi tamamladın!");
        return;
      }
    }

    // Enter ile gönder
    input.addEventListener("keypress", function(e){ if (e.key === "Enter"){ e.preventDefault(); sendMessage(); } });

    // ---- INIT ----
    (function init(){
      const intro = "Şimdi geldi kapanışa. Sen bu eğitim boyunca yapay zekâ uygulamalarının ne olduğunu, nasıl çalıştığını, ofis hayatında sana nasıl yardımcı olabileceğini gördün. Ama gerçekten öğrendin mi? Gel bunu birlikte test edelim.";
      addMessage("AkademiMentor", intro);
      setTimeout(() => addMCQ(mcq[0], 0), 900);
    })();
  </script>
</body>
</html>
