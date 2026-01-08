<?php
// Include configuration
require_once __DIR__ . '/../../config.php';

$is_embed = isset($_GET['embed']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['prompt'])) {

    $dataFile = __DIR__ . '/../chatbot/data.txt';
    $data = file_exists($dataFile) ? file_get_contents($dataFile) : '';

    // Prepare the prompt for Ollama
    $prompt = "You are a helpful assistant for TravelMates Hotel, ready to assist guests with bookings, services, and travel information to ensure a comfortable stay.

INSTRUCTIONS:
1. ALWAYS respond in a friendly, conversational tone
2. For greetings (hello, hi, hey, good morning, etc.), respond warmly and ask how you can help
3. For questions about TravelMates, use ONLY the information provided in the dataset below
4. Keep responses concise (2-3 sentences maximum)
5. If asked about something not in the dataset, politely say you don't have that information only ask about travelMates hotel-related topics
6. Be case-insensitive when matching questions
7. When asked for phone number, email, or location, ALWAYS use 'Our' (e.g., 'Our phone number is...', 'Our email is...', 'Our location is...').

DATASET:
$data

USER MESSAGE: {$_POST['prompt']}

YOUR RESPONSE (keep it short and friendly):";

    $url = "http://127.0.0.1:11434/api/chat";

    $payload = json_encode([
        'model' => 'qwen3:0.6b',
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'stream' => false
    ]);

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json",
            'content' => $payload,
            'ignore_errors' => true
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    $data = json_decode($response, true);
    $reply = $data['message']['content'] ?? '(no reply)';

    echo $reply;
    exit;
}

?>

<?php if (!$is_embed): ?>

    <!-- Floating Action Button (NO Bootstrap data attributes) -->
    <button class="btn btn-warning rounded-circle position-fixed d-flex align-items-center justify-content-center"
        id="chatbotBtn" type="button" style="width:64px;height:64px;bottom:24px;right:24px;z-index:1080;">
        <img src="<?php echo IMAGES_URL; ?>/logo/chatbot.png" alt="Chat"
            style="width:70%;height:70%;object-fit:contain;">
    </button>

    <!-- Chatbot Offcanvas (slides in from right) -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="chatbotOffcanvas" aria-labelledby="chatbotOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="chatbotOffcanvasLabel">TM Customer Assistant</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex p-0 overflow-auto" style="min-width:320px;max-width:420px;">
            <iframe src="<?php echo BASE_URL; ?>/integrations/chatbot/chatbotUI.php?embed=1"
                style="width:100%;height:100%;border:0;min-height:560px;" title="Chatbot"></iframe>
        </div>
    </div>

    <style>
        #chatbotBtn.hidden-permanently {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('chatbotBtn');
            const offcanvasEl = document.getElementById('chatbotOffcanvas');

            if (!btn || !offcanvasEl) {
                console.error('Chatbot elements not found');
                return;
            }
            function initChatbot() {
                if (typeof bootstrap === 'undefined') {
                    setTimeout(initChatbot, 100);
                    return;
                }

                const bsOffcanvas = new bootstrap.Offcanvas(offcanvasEl);

                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    console.log('Button clicked - hiding now');

                    btn.classList.add('hidden-permanently');
                    bsOffcanvas.show();
                });

                offcanvasEl.addEventListener('hidden.bs.offcanvas', function () {
                    console.log('Offcanvas closed - showing button again');
                    btn.classList.remove('hidden-permanently');
                });
            }

            initChatbot();
        });
    </script>
<?php endif; ?>

<?php if (isset($_GET['embed'])): ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Chatbot</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo CSS_URL; ?>/chatbot.css">
    </head>

    <body class="d-flex flex-column vh-100 overflow-hidden"
        style="font-family: 'Poppins', sans-serif; background-color: #f8f9fa;">

        <!-- Chat Messages Container -->
        <div class="container-fluid flex-grow-1 overflow-auto p-3" id="chat">
            <div class="row">
                <div class="col-12">
                    <!-- Welcome Message -->
                    <div class="d-flex gap-3 align-items-start mb-3">
                        <div class="rounded-circle bg-white d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0 shadow-sm"
                            style="width: 36px; height: 36px;">
                            <img src="<?php echo IMAGES_URL; ?>/logo/chatbot.png" alt="Bot"
                                class="w-100 h-100 rounded-circle" style="object-fit: cover;">
                        </div>
                        <div class="px-4 py-2 rounded-4 rounded-bottom-start-1"
                            style="background-color: #fff3cd; color: #856404; font-size: 14px; line-height: 1.5;">
                            Welcome to TravelMates Hotel! 😊<br>
                            How can I help you today?
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Container -->
        <div class="container-fluid bg-white border-top py-3 px-4">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex gap-3 align-items-center">
                        <input type="text" id="userInput" placeholder="Type your message" onkeypress="handleKeyPress(event)"
                            class="form-control border-0 shadow-none"
                            style="font-size: 14px; font-family: 'Poppins', sans-serif; color: #495057;">
                        <button
                            class="btn btn-warning rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 p-0"
                            onclick="send_to_chat()" id="sendBtn"
                            style="width: 36px; height: 36px; transition: transform 0.2s ease;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M22 2L11 13M22 2L15 22L11 13M22 2L2 9L11 13" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Powered By Footer -->
        <div class="container-fluid bg-white text-center py-2">
            <div class="row">
                <div class="col-12">
                    <small class="text-muted" style="font-size: 11px;">
                        Powered by <b>Ollama Model qwen3:0.6b</b>
                    </small>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function handleKeyPress(event) {
                if (event.key === 'Enter') {
                    send_to_chat();
                }
            }

            function send_to_chat() {
                const chat = document.getElementById("chat");
                const input = document.getElementById("userInput");
                const sendBtn = document.getElementById("sendBtn");
                const text = input.value.trim();

                if (!text) return;

                // Add user message
                const userMsgRow = document.createElement('div');
                userMsgRow.className = 'row mb-3';
                userMsgRow.innerHTML = `
                <div class="col-12">
                    <div class="d-flex gap-3 align-items-start flex-row-reverse animate-fadeIn">
                        <div class="px-3 py-2 rounded-4 rounded-bottom-end-1 text-white" 
                             style="background-color: #ffc107; font-size: 14px; line-height: 1.5; max-width: 75%;">
                            ${escapeHtml(text)}
                        </div>
                    </div>
                </div>
            `;
                chat.appendChild(userMsgRow);

                input.value = "";
                sendBtn.disabled = true;
                chat.scrollTop = chat.scrollHeight;

                // Add typing indicator
                const typingRow = document.createElement('div');
                typingRow.className = 'row mb-3';
                typingRow.id = 'typing_' + Date.now();
                typingRow.innerHTML = `
                <div class="col-12">
                    <div class="d-flex gap-3 align-items-start animate-fadeIn">
                        <div class="rounded-circle bg-white d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0 shadow-sm" 
                             style="width: 36px; height: 36px;">
                            <img src="<?php echo IMAGES_URL; ?>/logo/chatbot.png" alt="Bot" class="w-100 h-100 rounded-circle" style="object-fit: cover;">
                        </div>
                        <div class="px-4 py-3 rounded-4 rounded-bottom-start-1" 
                             style="background-color: #e9ecef; font-size: 14px; max-width: 75%;">
                            <div class="d-flex gap-1">
                                <div class="typing-dot"></div>
                                <div class="typing-dot" style="animation-delay: 0.2s;"></div>
                                <div class="typing-dot" style="animation-delay: 0.4s;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
                chat.appendChild(typingRow);
                chat.scrollTop = chat.scrollHeight;

                fetch("<?php echo BASE_URL; ?>/integrations/chatbot/chatbotUI.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "prompt=" + encodeURIComponent(text)
                })
                    .then(res => res.text())
                    .then(reply => {
                        // Remove typing indicator
                        typingRow.remove();

                        // Add bot reply
                        const botMsgRow = document.createElement('div');
                        botMsgRow.className = 'row mb-3';
                        botMsgRow.innerHTML = `
                        <div class="col-12">
                            <div class="d-flex gap-3 align-items-start animate-fadeIn">
                                <div class="rounded-circle bg-white d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0 shadow-sm" 
                                     style="width: 36px; height: 36px;">
                                    <img src="<?php echo IMAGES_URL; ?>/logo/chatbot.png" alt="Bot" class="w-100 h-100 rounded-circle" style="object-fit: cover;">
                                </div>
                                <div class="px-3 py-2 rounded-4 rounded-bottom-start-1" 
                                     style="background-color: #e9ecef; color: #333; font-size: 14px; line-height: 1.5; max-width: 75%;">
                                    ${escapeHtml(reply)}
                                </div>
                            </div>
                        </div>
                    `;
                        chat.appendChild(botMsgRow);
                        chat.scrollTop = chat.scrollHeight;
                        sendBtn.disabled = false;
                    })
                    .catch(() => {
                        typingRow.remove();
                        const errorRow = document.createElement('div');
                        errorRow.className = 'row mb-3';
                        errorRow.innerHTML = `
                        <div class="col-12">
                            <div class="d-flex gap-3 align-items-start animate-fadeIn">
                                <div class="rounded-circle bg-white d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0 shadow-sm" 
                                     style="width: 36px; height: 36px;">
                                    <img src="<?php echo IMAGES_URL; ?>/logo/chatbot.png" alt="Bot" class="w-100 h-100 rounded-circle" style="object-fit: cover;">
                                </div>
                                <div class="px-3 py-2 rounded-4 rounded-bottom-start-1" 
                                     style="background-color: #e9ecef; color: #333; font-size: 14px; line-height: 1.5; max-width: 75%;">
                                    Sorry, I encountered an error. Please try again.
                                </div>
                            </div>
                        </div>
                    `;
                        chat.appendChild(errorRow);
                        chat.scrollTop = chat.scrollHeight;
                        sendBtn.disabled = false;
                    });
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        </script>

    </body>

    </html>
<?php endif; ?>