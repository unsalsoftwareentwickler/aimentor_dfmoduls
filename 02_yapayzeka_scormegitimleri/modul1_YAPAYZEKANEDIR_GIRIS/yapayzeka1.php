<?php
// ===============================================
// AkademiMentor – Denetimli Cevap Üretimi (v1.1)
// - UI/JS dokunulmadı.
// - Harici context: yapayzeka_context.md
// - Self-audit (audit_rules) + server-side doğrulama
// ===============================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// OPENAI API KEY BURAYA KOYULMALI
$apiKey = '';

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Sunucu yapılandırması eksik: OPENAI_API_KEY tanımlı değil.']);
    exit;
}

// ---- Harici context dosyası (konu dışına çıkmamak için) ----
$CONTEXT_PATH = __DIR__ . '/yapayzeka_context.md';
if (!is_file($CONTEXT_PATH)) {
    $TRAINING_CONTEXT = "UYARI: yapayzeka_context.md bulunamadı. Yine de konu AI temel eğitimi.";
} else {
    $TRAINING_CONTEXT = file_get_contents($CONTEXT_PATH);
    if ($TRAINING_CONTEXT === false) {
        $TRAINING_CONTEXT = "UYARI: yapayzeka_context.md okunamadı. Yine de konu AI temel eğitimi.";
    }
}

// --------------------
// Kural Sözleşmesi
// --------------------
$POLICY_VERSION = '2025-10-13';
$RULES = [
    [
        'id' => 'R001',
        'title' => 'Önce self-audit',
        'desc' => 'Model, nihai metinden önce audit_rules fonksiyonunu çağırmalı.',
        'mandatory' => true,
        'server_check' => null
    ],
    [
        'id' => 'R010',
        'title' => '"Evet" teyidi akış kuralı',
        'desc' => 'Eğitime başlamadan önce kullanıcıdan net "evet" teyidi al; aksi halde kibarca yönlendir.',
        'mandatory' => false,
        'server_check' => null
    ],
    [
        'id' => 'R020',
        'title' => 'Dil: Türkçe',
        'desc' => 'Yanıt dili Türkçe olmalı.',
        'mandatory' => true,
        'server_check' => function($text) {
            $hasTrChar = (bool)preg_match('/[çğıöşüÇĞİÖŞÜ]/u', $text);
            $hasCommon = (bool)preg_match('/\b(ve|ama|fakat|çünkü|ancak|eğer)\b/u', $text);
            return $hasTrChar || $hasCommon;
        }
    ],
    [
        'id' => 'R030',
        'title' => 'Kısa ve konu odaklı',
        'desc' => 'Gereksiz uzatma yok; sadece istenen konu.',
        'mandatory' => true,
        'server_check' => null
    ],
    [
        'id' => 'R040',
        'title' => 'Konu dışına çıkma',
        'desc' => 'Yanıt, yapayzeka_context.md içeriğinin konusu olan “Yapay Zekâ Temelleri” bağlamında kalmalı; konu dışı istekleri kibarca yönlendir.',
        'mandatory' => true,
        'server_check' => null
    ],
    [
        'id' => 'R050',
        'title' => 'Gizli veri isteme yok',
        'desc' => 'Kişisel/kurumsal gizli verileri isteme/teşvik yok.',
        'mandatory' => true,
        'server_check' => '/(tc\s*kimlik|iban|banka|adres|telefon|şifre)/iu'
    ],
    [
        'id' => 'R060',
        'title' => 'Maksimum uzunluk',
        'desc' => 'Tek mesaj en fazla 1200 karakter.',
        'mandatory' => false,
        'server_check' => function($text){ return mb_strlen($text, 'UTF-8') <= 1200; }
    ],
	[
		'id' => 'R070',
		'title' => 'Hitabet',
		'desc' => 'Yanıtlarında kullanıcıya "siz" değil "sen" diliyle hitap et.',
		'mandatory' => true,
		'server_check' => function($text){
			return !preg_match('/\bsen(?!t)\b|\bsenin\b|\bsene\b|\bsende\b|\bsenden\b|\bsen(i|e|de|den|sin|siniz)\b/iu', $text);
		}
	],
];

// --------------- Yardımcılar ---------------
function buildSystemPrompt(array $RULES, string $POLICY_VERSION, string $TRAINING_CONTEXT): string {
    $rulesText = array_map(function($r){
        return "{$r['id']}: {$r['title']} — {$r['desc']}";
    }, $RULES);
    $rulesJoined = implode("\n", $rulesText);

    // Not: Burada nihai cevabı HEMEN yazma demiyoruz; önce audit istenecek.
    return <<<SYS
Sen Mentor AI adında bir e-öğrenme asistanısın. Aşağıdaki "Politika Kuralları (v{$POLICY_VERSION})" ve "Eğitim Metni" bütün mesajların için bağlayıcıdır.

[Politika Kuralları]
{$rulesJoined}

[Eğitim Metni – yapayzeka_context.md]
{$TRAINING_CONTEXT}

ÇOK ÖNEMLİ: Nihai kullanıcı cevabını yazmadan ÖNCE “audit_rules” adlı bir öz denetim fonksiyonunu çağır.
- Kuralların her birini değerlendir (pass/fail + kısa not).
- Bir ihlal varsa final_grade="fail".
- Sunucudan onay almadan nihai cevabı yazma.
- Lütfen audit_rules çıktında şu ID'lerin HER BİRİNİ değerlendir: R001, R010, R020, R030, R040, R050, R060, R070
SYS;
}

function openai_chat(array $payload, string $apiKey): array {
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 45
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) throw new RuntimeException("Curl error: " . $err);
    if ($code !== 200) throw new RuntimeException("HTTP $code: " . $res);

    $data = json_decode($res, true);
    if (!$data) throw new RuntimeException("API yanıtı çözümlenemedi.");
    return $data;
}

function server_validate(string $text, array $RULES): array {
    $fails = [];
    foreach ($RULES as $r) {
        if (empty($r['server_check'])) continue;

        // ❗ Sadece ZORUNLU kurallar bloklansın
        $isMandatory = !empty($r['mandatory']);
        if (!$isMandatory) continue;

        $ok = true;
        if (is_string($r['server_check'])) {
            $ok = !preg_match($r['server_check'], $text);
        } elseif (is_callable($r['server_check'])) {
            $ok = (bool)call_user_func($r['server_check'], $text);
        }
        if (!$ok) {
            $fails[] = ['id' => $r['id'], 'reason' => 'Server-side validation failed'];
        }
    }
    return $fails;
}


function audit_tool_schema(): array {
    return [
        [
            'type' => 'function',
            'function' => [
                'name' => 'audit_rules',
                'description' => 'Nihai mesaja geçmeden önce modelin kurallara uyumu için checklist.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'final_grade' => ['type' => 'string', 'enum' => ['pass', 'fail']],
                        'rules' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'string'],
                                    'ok' => ['type' => 'boolean'],
                                    'note' => ['type' => 'string']
                                ],
                                'required' => ['id', 'ok']
                            ]
                        ],
                        'notes' => ['type' => 'string']
                    ],
                    'required' => ['final_grade', 'rules']
                ]
            ]
        ]
    ];
}

function evaluate_audit(array $auditArgs, array $RULES): array {
    $byId = [];
    foreach (($auditArgs['rules'] ?? []) as $row) {
        if (!isset($row['id'])) continue;
        $byId[$row['id']] = $row;
    }
    $failReasons = [];
    foreach ($RULES as $r) {
        if (!empty($r['mandatory'])) {
            if (!isset($byId[$r['id']])) {
                $failReasons[] = "{$r['id']} (eksik denetim)";
                continue;
            }
            if (empty($byId[$r['id']]['ok'])) {
                $failReasons[] = "{$r['id']} (model öz denetimi FAILED)";
            }
        }
    }
    return $failReasons;
}

// --------------------------------
// POST işleyicisi (UI/JS değişmedi)
// --------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    $userMessage = trim($input['message'] ?? '');

    if ($userMessage === '') {
        echo json_encode(['error' => 'Boş mesaj gönderildi.']);
        exit;
    }
	
	$gateConfirmed = (bool)($input['gate_confirmed'] ?? false);
	$phase = (string)($input['phase'] ?? 'unknown');
	$quizMeta = isset($input['quiz']) && is_array($input['quiz']) ? $input['quiz'] : null;
	
	// 'gate' aşamasında ve onay yokken modele gitme
	if (!$gateConfirmed && $phase === 'gate') {
		echo json_encode([
			'success' => true,
			'reply'   => "Sorun değil 😊 Hazır olduğunda \"evet\" yazabilirsin."
		]);
		exit;
	}

    try {
        $systemPrompt = buildSystemPrompt($RULES, $POLICY_VERSION, $TRAINING_CONTEXT);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'system', 'content' => 'İlk adımda yalnızca audit_rules fonksiyonunu çağır. Nihai cevabı YAZMA.'],
            ['role' => 'system', 'content' => 'Konu: yapayzeka_context.md içeriğine sadık kal. Konu dışına çıkarsan kibarca yönlendir.'],
            ['role' => 'system', 'content' =>
        "UI_AKIS_BILGISI: gate_confirmed=" . ($gateConfirmed ? "true" : "false") .
        ", phase={$phase}. 'Evet' kapısı UI tarafından yönetilir. gate_confirmed=false ise kullanıcıyı sadece nazikçe yönlendir; audit'i bloke etme."
    ],
        ];
		
		if ($phase === 'quiz' && $quizMeta) {
			$qNo   = (int)($quizMeta['index'] ?? 0);
			$qText = (string)($quizMeta['question'] ?? '');
			$ans   = (string)($quizMeta['answer'] ?? '');

			$messages[] = [
				'role' => 'system',
				'content' =>
					"QUIZ_MODU: Aşağıdaki kullanıcı cevabını değerlendirin ve eğitim bağlamına sadık kalarak " .
					"en fazla 3 maddelik kısa bir açıklama/geri bildirim yazın. " .
					"Gerekirse doğru kavramı netleştirin, küçük bir örnek verin, güvenlik/veri hassasiyetini hatırlatın. " .
					"Yeni soru SORMAYIN, yalnızca değerlendirme yazın.\n" .
				    "Cümlenin başına tek bir etiket koyun: [PROCEED] (uygun, sonraki soruya geçilebilir) " .
				    "veya [HOLD] (kullanıcıdan aynı soruya tekrar yanıt alınmalı).\n" .
					"Soru #{$qNo}: {$qText}\n" .
					"Kullanıcı Cevabı: {$ans}"
			];
		}

		$messages[] = ['role' => 'user', 'content' => $userMessage]; // user en sonda

        $tools = audit_tool_schema();

        // Aşama 1: Zorunlu audit
        $payload = [
            'model' => 'gpt-4o',
            'messages' => $messages,
            'tools' => $tools,
            'tool_choice' => ['type' => 'function', 'function' => ['name' => 'audit_rules']],
            'temperature' => 0.2
        ];
        
		$auditResp = openai_chat($payload, $apiKey);
		$msg = $auditResp['choices'][0]['message'] ?? null;
		$toolCalls = $msg['tool_calls'] ?? [];

		if (!$toolCalls || ($toolCalls[0]['function']['name'] ?? '') !== 'audit_rules') {
			echo json_encode(['error' => 'Bu konuyu birlikte biraz daha sadeleştirelim 😊']);
			exit;
		}

		// 1) Önce assistant (tool_calls’lı) mesajı ekle
		$messages[] = [
			'role' => 'assistant',
			'content' => $msg['content'] ?? null,
			'tool_calls' => $toolCalls
		];

		// 2) Sonra tool geri dönüşünü ekle
		$auditArgs = json_decode($toolCalls[0]['function']['arguments'] ?? '{}', true) ?: [];
		if (!isset($auditArgs['rules']) || !is_array($auditArgs['rules'])) {
			$auditArgs['rules'] = [];
		}
		$auditArgs['rules'][] = ['id' => 'R001', 'ok' => true, 'note' => 'server: tool was called'];

		$messages[] = [
			'role' => 'tool',
			'tool_call_id' => $toolCalls[0]['id'],
			'name' => 'audit_rules',
			'content' => json_encode(['received' => true], JSON_UNESCAPED_UNICODE)
		];


        $modelAuditFails = evaluate_audit($auditArgs, $RULES);
        $needRevision = ($auditArgs['final_grade'] ?? 'fail') === 'fail' || !empty($modelAuditFails);
		if ($phase === 'quiz') { $needRevision = false; }
		
        // Revizyon döngüsü (max 2)
        $MAX_REVISIONS = 2; $revision = 0;
        while ($needRevision && $revision < $MAX_REVISIONS) {
            $revision++;
            $failText = implode(', ', $modelAuditFails);
            $messages[] = [
                'role' => 'system',
                'content' =>
                    "REVİZYON #{$revision}: Zorunlu kurallar sağlanmadı: {$failText}. " .
                    "Planı düzelt ve tekrar audit_rules çağır. Nihai cevap yazma."
            ];

            $retry = [
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'tools' => $tools,
                'tool_choice' => ['type' => 'function', 'function' => ['name' => 'audit_rules']],
                'temperature' => 0.2
            ];
            
			$auditResp = openai_chat($retry, $apiKey);
			$msg = $auditResp['choices'][0]['message'] ?? null;
			$toolCalls = $msg['tool_calls'] ?? [];
			if (!$toolCalls || ($toolCalls[0]['function']['name'] ?? '') !== 'audit_rules') break;

			// 1) Önce assistant (tool_calls’lı) mesajını ekle
			$messages[] = [
				'role' => 'assistant',
				'content' => $msg['content'] ?? null,
				'tool_calls' => $toolCalls
			];

			// 2) Sonra tool dönüşünü ekle
			$auditArgs = json_decode($toolCalls[0]['function']['arguments'] ?? '{}', true) ?: [];
			if (!isset($auditArgs['rules']) || !is_array($auditArgs['rules'])) {
				$auditArgs['rules'] = [];
			}
			$auditArgs['rules'][] = ['id' => 'R001', 'ok' => true, 'note' => 'server: tool was called'];

			$messages[] = [
				'role' => 'tool',
				'tool_call_id' => $toolCalls[0]['id'],
				'name' => 'audit_rules',
				'content' => json_encode(['received' => true], JSON_UNESCAPED_UNICODE)
			];


            $modelAuditFails = evaluate_audit($auditArgs, $RULES);
            $needRevision = ($auditArgs['final_grade'] ?? 'fail') === 'fail' || !empty($modelAuditFails);
			if ($phase === 'quiz') { $needRevision = false; }
        }

        if ($needRevision) {
            // echo json_encode(['error' => 'Biraz karıştı sanırım 😊 Konuyu kısaca ve net şekilde toparlayalım.']);
            // exit;
			
			$messages[] = [
				'role' => 'system',
				'content' => 'Audit eksik olsa da devam et. Nihai cevabı oluştur. Türkçe, kısa ve konu odaklı ol.'
			];
        }

        // Aşama 2: Nihai cevap
        $messages[] = [
			'role' => 'system',
			'content' => 'Artık nihai cevabı yaz. Kurallara %100 uy. Türkçe, kısa ve konu odaklı ol.' .
						 ($phase === 'quiz' ? ' Sadece değerlendirme yaz; yeni soru sorma.' : '')
		];

        $finalPayload = [
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.3
        ];
        $finalResp = openai_chat($finalPayload, $apiKey);
        $finalText = $finalResp['choices'][0]['message']['content'] ?? '';
		
		$proceed = true;
		if ($phase === 'quiz') {
			if (preg_match('/^\s*\[(PROCEED|HOLD)\]\s*/i', $finalText, $m)) {
				$proceed = strtoupper($m[1]) === 'PROCEED';
				// etiketi metinden sök
				$finalText = preg_replace('/^\s*\[(PROCEED|HOLD)\]\s*/i', '', $finalText, 1);
			}
		}

        // Sunucu doğrulaması (regex/closure)
        $serverFails = server_validate($finalText, $RULES);
        if (!empty($serverFails)) {
			// Kullanıcıya fallback
			echo json_encode([
				'success' => true,
				'reply' => "Bu bölümde Yapay Zekâ Temellerine odaklanalım 😊 ",
				'hold_question' => ($phase === 'quiz') ? true : false
			]);
			exit;
		}


        echo json_encode([
		  'success' => true,
		  'reply' => $finalText,
		  'hold_question' => ($phase === 'quiz') ? !$proceed : false  // HOLD = aynı soruda kal
		]);
    } catch (Throwable $e) {
        http_response_code(200);
    echo json_encode([
        'success' => true,
        'reply' => "Şu anda isteğini işlerken küçük bir aksaklık yaşandı 😊 Lütfen tekrar dene.",
        'hold_question' => false
    ]);
    }
    exit;
}
?>

<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>AkademiMentor – Chat</title>
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
    .title-group{
      display:flex; align-items:center; gap:12px; min-width:0;
    }
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
    .message.user{background:#f6f9ff;}
    .message .message-sender{font-size:0.75em; color:#6b7280; margin-bottom:4px;}
    .message .message-bubble{white-space:pre-wrap; word-wrap:break-word;}

    .typing-indicator{display:none; margin:8px 0;}
    .typing-indicator.show{display:block;}
    .typing-bubble{display:flex; align-items:center; gap:8px; padding:8px 10px;
      border:1px solid #edf0f4; border-radius:12px; background:#fff;}
    .typing-dots{display:flex; gap:4px;}
    .typing-dot{width:6px; height:6px; border-radius:50%; background:#9aa4b2; animation:blink 1s infinite;}
    .typing-dot:nth-child(2){animation-delay:.2s}
    .typing-dot:nth-child(3){animation-delay:.4s}
    @keyframes blink{0%,80%,100%{opacity:.2}40%{opacity:1}}

    .chat-input{
      border-top:1px solid var(--chat-border); background:#fff;
      display:grid; grid-template-columns: minmax(0, 1fr) 112px;
      gap:8px; padding:10px;
    }
    .chat-input input, .chat-input textarea{
      box-sizing: border-box; min-width: 0; width:100%; height:48px;
      resize:none; padding:10px 12px; border:1px solid #d7dde3;
      border-radius:12px; font:inherit; outline:none; background:#fff;
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
          <div id="chatbox"></div>

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
          <button class="send-btn" id="sendBtn" type="button" onclick="sendMessage()">Gönder</button>
        </div>
      </section>
    </main>
  </div>

<script>
let gateConfirmed = false;

const chatbox = document.getElementById("chatbox");
const input = document.getElementById("userInput");
const button = document.getElementById("sendBtn");
const typingIndicator = document.getElementById("typingIndicator");

let state = 0;
let questions = [
  "Soru 1: Günlük hayatında kullandığın yapay zekâ uygulamalarına üç örnek verebilir misin?",
  "Soru 2: Makine öğrenmesi (ML) ile derin öğrenme (DL) arasındaki temel fark nedir?",
  "Soru 3: Yapay zekâ araçlarını kullanırken hangi verileri asla paylaşmamalısın?"
];

function addMessage(from, text) {
  const wrap = document.createElement('div');
  wrap.className = `message ${from === 'Sen' ? 'user' : 'ai'}`;
  wrap.innerHTML = `
    <div class="message-sender">${from}</div>
    <div class="message-bubble">${text}</div>
  `;
  chatbox.appendChild(wrap);
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
    button.disabled = true;
    button.textContent = "Gönderiliyor...";
    input.disabled = true;
  } else {
    button.disabled = false;
    button.textContent = "Gönder";
    input.disabled = false;
    input.focus();
  }
}

// Giriş mesajı
addMessage("AkademiMentor",
`Evet merhaba, ben eğitim AI asistanı Akademi Mentor AI. Şimdi sana eğitim öncesi birkaç sorum olacak.

Hazırsan "evet" yaz, ben de sırayla 2-3 soru sorayım. Ardından dilediğin kadar soru sorabilir veya eğitime devam edebilirsin.`);

input.addEventListener("keypress", function(e) {
  if (e.key === "Enter") {
    e.preventDefault();
    sendMessage();
  }
});

function getPhase() {
  if (!gateConfirmed) return "gate";
  if (state > 0 && state <= questions.length) return "quiz";
  if (state > questions.length) return "free";
  return "gate";
}

async function sendMessage() {
  const userText = input.value.trim();
  if (!userText) return;

  setButtonState(true);
  addMessage("Sen", userText);
  input.value = "";

  // 1) "evet" kapısı (UI)
  if (state === 0 && userText.toLowerCase() === "evet") {
    gateConfirmed = true;
    state = 1;
    setTimeout(() => {
      addMessage("AkademiMentor", questions[0]);
      setButtonState(false);
    }, 600);
    return;
  }

  // 2) QUIZ AŞAMASI: kullanıcı cevabını backend'e gönder, açıklamayı göster, sonra sıradaki soruya geç
	if (state > 0 && state <= questions.length) {
	  const qIndex = state - 1;
	  const questionText = questions[qIndex];

	  showTypingIndicator();

	  let data;

	  try {
		const resp = await fetch(window.location.href, {
		  method: 'POST',
		  headers: { 'Content-Type': 'application/json' },
		  body: JSON.stringify({
			message: userText,
			gate_confirmed: true,
			phase: "quiz",
			quiz: { index: qIndex + 1, question: questionText, answer: userText }
		  })
		});

		data = await resp.json();
		hideTypingIndicator();

		if (data.success && data.reply) {
		  addMessage("AkademiMentor", data.reply);
		} else {
		  addMessage("AkademiMentor", "" + (data.error || "bilinmeyen hata"));
		}
	  } catch (e) {
		console.error(e);
		hideTypingIndicator();
		addMessage("AkademiMentor", "Bağlantı hatası oluştu. Lütfen tekrar deneyiniz.");
	  } finally {
		const reqSucceeded = !!(data && data.success === true);
		const shouldHold   = !!(data && data.hold_question === true);
		const shouldAdvance = reqSucceeded && !shouldHold;

		if (shouldAdvance) {
			  state++;
			  setTimeout(() => {
				if (state <= questions.length) {
				  addMessage("AkademiMentor", questions[state - 1]);
				} else {
				  addMessage("AkademiMentor", "Şimdi sıra sende! Yapay zekâ eğitimi hakkında dilediğin kadar soru sorabilirsin.");
				}
				setButtonState(false);
			  }, 400);
			} else {
			  // Aynı soruda kal: soruyu tekrar göster + küçük ipucu
			  addMessage("AkademiMentor", "Aynı soruya geri dönelim 👇");
			  addMessage("AkademiMentor", questionText);
			  // İsteğe bağlı kısa yönlendirme:
			  // addMessage("AkademiMentor", "Kısaca 2-3 örnek yazabilir misiniz?");
			  setButtonState(false);
			}
	  }
	  return;
	}


  // 3) Serbest sohbet (backend)
  showTypingIndicator();
  try {
    const response = await fetch(window.location.href, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message: userText,
        gate_confirmed: gateConfirmed,
        phase: getPhase()
      })
    });
    const data = await response.json();
    hideTypingIndicator();

    if (data.success && data.reply) {
      addMessage("AkademiMentor", data.reply);
    } else {
      addMessage("AkademiMentor", "" + (data.error || "bilinmeyen hata"));
    }
  } catch (err) {
    console.error(err);
    hideTypingIndicator();
    addMessage("AkademiMentor", "Bağlantı hatası oluştu. Lütfen tekrar deneyiniz.");
  } finally {
    setButtonState(false);
  }
}

window.addEventListener('load', () => input.focus());
</script>


	
	
</body>
</html>

