<?php
session_start();

// Lista de usuários válidos (usuário => [senha, validade, limite_verificacoes])
$usuarios = [
    "gay" => ["gaygostade4", "2030-06-01", 10],
    "Brunin" => ["Brunin4055", "2030-06-01", 100000],
    "Dono9070" => ["Dono9070", "2030-05-21", 100000],
    "ksla" => ["12211y7", "2025-05-27", 100000],
    "qkhaUah191u2" => ["kAKHKS101", "2025-05-25", 10],
    "Df" => ["dfteste", "2030-06-01", 5],
    "thurr" => ["aalL00303289", "2030-06-01", 1000000000],
    "Deadfor" => ["Deadfor", "2030-06-01", 10],
    "Thzin" => ["Teo", "2030-06-01", 1000000000],
    "titiorn" => ["123mudar", "2030-06-01", 100000000],
    "Joseph" => ["Joseph6789", "2025-06-15", 100000000000],
    "lokzdev" => ["lokzdev6789", "2025-06-16", 1],
    "Henrique" => ["Henrique", "2030-06-01", 10000],
    "ChypherBoSs" => ["Hell020286", "2030-06-01", 10000000000000],
    "Zn_71" => ["Zn", "2025-07-03", 1000000000000],
    "Rd" => ["Rd", "2025-07-29", 10000000000000000],
    "12" => ["1111%%%¨%$¨$¨%$", "2030-06-01", 100],
    "13" => ["1111%%%¨%$¨$¨%$", "2030-06-01", 100],
    "14" => ["1111%%%¨%$¨$¨%$", "2030-06-01", 100],
    "15" => ["1111%%%¨%$¨$¨%$", "2030-06-01", 100],
    "16" => ["1111%%%¨%$¨$¨%$", "2030-06-01", 100],
    "17" => ["1111%%%¨%$¨$¨%$", "2030-06-01", 100],
    "18" => ["1111%%%¨%$¨$¨%$", "2030-06-01", 100],
    "19" => ["1111%%%¨%$¨$¨%$", "2030-06-01", 100],
    "20" => ["1111%%%¨%$¨$¨%$", "2030-06-01", 100]
];

// Arquivo para armazenar verificações usadas
$arquivo_verificacoes = 'verificacoes_usadas.json';

// Função para carregar verificações usadas
function carregarVerificacoes() {
    global $arquivo_verificacoes;
    if (file_exists($arquivo_verificacoes)) {
        return json_decode(file_get_contents($arquivo_verificacoes), true) ?: [];
    }
    return [];
}

// Função para salvar verificações usadas
function salvarVerificacoes($verificacoes) {
    global $arquivo_verificacoes;
    file_put_contents($arquivo_verificacoes, json_encode($verificacoes));
}

// Função para verificar limite do usuário
function verificarLimite($usuario) {
    global $usuarios;
    $verificacoes = carregarVerificacoes();
    $usado = isset($verificacoes[$usuario]) ? $verificacoes[$usuario] : 0;
    $limite = $usuarios[$usuario][2];
    return $limite - $usado;
}

// Função para usar uma verificação
function usarVerificacao($usuario) {
    $verificacoes = carregarVerificacoes();
    if (!isset($verificacoes[$usuario])) {
        $verificacoes[$usuario] = 0;
    }
    $verificacoes[$usuario]++;
    salvarVerificacoes($verificacoes);
}

// Função para validar formato do cartão com algoritmo de Luhn
function validarFormatoCartao($numero) {
    // Remove espaços e caracteres não numéricos
    $numero = preg_replace('/\D/', '', $numero);
    
    // Verifica se tem pelo menos 13 dígitos
    if (strlen($numero) < 13 || strlen($numero) > 19) {
        return false;
    }
    
    // Algoritmo de Luhn para validação básica
    $soma = 0;
    $alternar = false;
    
    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $digito = intval($numero[$i]);
        
        if ($alternar) {
            $digito *= 2;
            if ($digito > 9) {
                $digito -= 9;
            }
        }
        
        $soma += $digito;
        $alternar = !$alternar;
    }
    
    return ($soma % 10) == 0;
}

// Função para detectar bandeira do cartão
function detectarBandeira($numero) {
    $numero = preg_replace('/\D/', '', $numero);
    
    if (preg_match('/^4/', $numero)) {
        return 'Visa';
    } elseif (preg_match('/^5[1-5]/', $numero) || preg_match('/^2[2-7]/', $numero)) {
        return 'Mastercard';
    } elseif (preg_match('/^3[47]/', $numero)) {
        return 'American Express';
    } elseif (preg_match('/^6(?:011|4|5)/', $numero)) {
        return 'Discover';
    } elseif (preg_match('/^35/', $numero)) {
        return 'JCB';
    } else {
        return 'Unknown';
    }
}

// Função para verificar cartão
function verificarCartao($cartao, $gate) {
    // Simula delay de 20 segundos
    sleep(20);
    
    // Parse dos dados do cartão
    $dados = explode('|', $cartao);
    if (count($dados) < 4) {
        return [
            'status' => 'INVALID',
            'message' => 'Formato inválido. Use: NUMERO|MM|YY|CVC',
            'details' => []
        ];
    }
    
    $numero = trim($dados[0]);
    $mes = trim($dados[1]);
    $ano = trim($dados[2]);
    $cvc = trim($dados[3]);
    
    // Validar formato básico com algoritmo de Luhn
    if (!validarFormatoCartao($numero)) {
        return [
            'status' => 'INVALID',
            'message' => 'Número de cartão inválido (falhou no algoritmo de Luhn)',
            'details' => [
                'card' => $numero,
                'brand' => detectarBandeira($numero)
            ]
        ];
    }
    
    // 20% chance de LIVE, 80% chance de DEAD
    if (rand(1, 100) <= 20) {
        $live_message = '';
        if ($gate == 'Stripe') {
            $live_message = 'LIVE - Gate Stripe - Cartão Válido';
        } elseif ($gate == 'PayPal') {
            $live_message = 'LIVE - Gate PayPal - Saldo Disponível';
        }
        
        return [
            'status' => 'LIVE',
            'message' => $live_message,
            'details' => [
                'card' => $numero,
                'exp' => $mes . '/' . $ano,
                'cvc' => $cvc,
                'brand' => detectarBandeira($numero),
                'type' => 'CREDIT',
                'bank' => 'Unknown Bank',
                'country' => 'Unknown'
            ]
        ];
    } else {
        $dead_message = '';
        if ($gate == 'Stripe') {
            $dead_message = 'DEAD - Gate Stripe - Recusado por insuficiência de fundos';
        } elseif ($gate == 'PayPal') {
            $dead_message = 'DEAD - Gate PayPal - Recusado. Verifique os dados do cartão.';
        }
        
        return [
            'status' => 'DEAD',
            'message' => $dead_message,
            'details' => [
                'card' => $numero,
                'exp' => $mes . '/' . $ano,
                'cvc' => $cvc,
                'brand' => detectarBandeira($numero),
                'type' => 'CREDIT',
                'bank' => 'Unknown Bank',
                'country' => 'Unknown'
            ]
        ];
    }
}

// Processar verificação de cartão via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["verificar_cartao"]) && isset($_SESSION["logado"])) {
    $cartao = $_POST["cartao"];
    $gate = $_POST["gate"]; // Captura o gate selecionado
    $usuario = $_SESSION["usuario"];
    
    // Verificar se ainda tem limite
    $limite_restante = verificarLimite($usuario);
    if ($limite_restante <= 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Limite de verificações esgotado!"
        ]);
        exit;
    }
    
    // Usar uma verificação
    usarVerificacao($usuario);
    
    // Verificar o cartão
    $resultado = verificarCartao($cartao, $gate);
    
    // Retornar resultado
    echo json_encode([
        "status" => "success",
        "card_status" => $resultado['status'],
        "message" => $resultado['message'],
        "details" => $resultado['details'],
        "limite_restante" => verificarLimite($usuario)
    ]);
    exit;
}

// Verifica se está tentando logar
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["usuario"]) && isset($_POST["senha"])) {
    $usuario = $_POST["usuario"];
    $senha = $_POST["senha"];
    
    if (isset($usuarios[$usuario]) && $usuarios[$usuario][0] === $senha) {
        $validade = $usuarios[$usuario][1];
        $hoje = date("Y-m-d");
        
        if ($validade >= $hoje) {
            $limite_restante = verificarLimite($usuario);
            if ($limite_restante > 0) {
                $_SESSION["logado"] = true;
                $_SESSION["usuario"] = $usuario;
                $_SESSION["validade"] = $validade;
                $_SESSION["limite_restante"] = $limite_restante;
            } else {
                $erro = "Seu limite de verificações esgotou! Contate @Snow4055 no Telegram para comprar mais acessos.";
            }
        } else {
            $erro = "Sua conta expirou. Fale com @Snow4055 para renovar.";
        }
    } else {
        $erro = "Usuário ou senha inválidos.";
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Se não estiver logado, mostra a tela de login
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true) {
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HeadersCenter V2.0 - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1a1a;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #2c2c2c;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-container h2 {
            margin-bottom: 30px;
            color: #fff;
            font-weight: 600;
        }
        .form-control {
            background-color: #3b3b3b;
            border: 1px solid #555;
            color: #fff;
            margin-bottom: 20px;
        }
        .form-control:focus {
            background-color: #3b3b3b;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            color: #fff;
        }
        .btn-primary {
            width: 100%;
            padding: 10px;
            font-size: 1.1rem;
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0b5ed7;
        }
        .alert-danger {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>HeadersCenter V2.0</h2>
        <form action="" method="post">
            <?php if (isset($erro)) { ?>
                <div class="alert alert-danger"><?php echo $erro; ?></div>
            <?php } ?>
            <div class="mb-3">
                <input type="text" class="form-control" name="usuario" placeholder="Usuário" required>
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="senha" placeholder="Senha" required>
            </div>
            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
    </div>
</body>
</html>
<?php
exit;
}

// Se o usuário estiver logado, mostra o conteúdo do checker
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HeadersCenter V2.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1a1a;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            margin-top: 50px;
        }
        .card {
            background-color: #2c2c2c;
            color: #fff;
            border-color: #3b3b3b;
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #3b3b3b;
            border-bottom-color: #555;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .form-control {
            background-color: #1a1a1a;
            border: 1px solid #555;
            color: #fff;
            margin-bottom: 10px;
        }
        .form-control:focus {
            background-color: #1a1a1a;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            color: #fff;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 10px 20px;
            font-size: 1rem;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0b5ed7;
        }
        .results-container {
            margin-top: 20px;
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #3b3b3b;
            border-radius: 8px;
            padding: 10px;
        }
        .results-container h5 {
            margin-bottom: 15px;
            font-weight: 600;
        }
        .result-item {
            border-bottom: 1px solid #3b3b3b;
            padding: 10px 0;
        }
        .result-item:last-child {
            border-bottom: none;
        }
        .result-status {
            font-weight: bold;
        }
        .live {
            color: #fff;
            background-color: #007bff; /* Azul para LIVE */
        }
        .dead {
            color: #fff;
            background-color: #dc3545;
        }
        .invalid {
            color: #fff;
            background-color: #ffc107;
        }
        .loading-bar {
            width: 100%;
            height: 5px;
            background-color: #0d6efd;
            animation: loading 2s infinite linear;
            margin-top: 10px;
            display: none;
        }
        @keyframes loading {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }
        .list-group-item {
            background-color: #2c2c2c;
            border-color: #3b3b3b;
            color: #fff;
        }
        .list-group-item .text-muted {
            color: #bbb !important;
        }
        .badge {
            font-size: 0.9em;
            padding: 0.5em 0.8em;
            margin-left: 10px;
        }
        .btn-logout {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-logout:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .copy-btn {
            cursor: pointer;
            color: #fff;
            margin-left: 10px;
            font-size: 0.9em;
            transition: color 0.3s;
        }
        .copy-btn:hover {
            color: #0d6efd;
        }
        .copy-all-btn {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .copy-all-btn:hover {
            background-color: #5a6268;
            border-color: #5a6268;
        }
        .copy-all-btn.copied {
            background-color: #28a745;
            border-color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col text-end">
                <a href="?logout" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                HeadersCenter V2.0
                <span class="badge bg-secondary"><?php echo $_SESSION['usuario']; ?></span>
            </div>
            <div class="card-body">
                <p>Verificações restantes: <span id="limite-restante"><?php echo verificarLimite($_SESSION['usuario']); ?></span></p>
                <form id="checker-form">
                    <div class="mb-3">
                        <label for="gate-select" class="form-label">Selecione o Gate:</label>
                        <select class="form-select form-control" id="gate-select" name="gate" required>
                            <option value="Stripe">Stripe</option>
                            <option value="PayPal">PayPal</option>
                        </select>
                    </div>
                    <textarea class="form-control" rows="5" placeholder="Cole suas CCs aqui (ex: NUMERO|MM|YY|CVC)" name="cartao" required></textarea>
                    <button type="submit" class="btn btn-primary mt-3">
                        <i class="fas fa-play"></i> Iniciar Verificação
                    </button>
                    <div class="loading-bar"></div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="live-tab" data-bs-toggle="tab" data-bs-target="#live-pane" type="button" role="tab" aria-controls="live-pane" aria-selected="true">
                            Lives (<span id="live-count">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="dead-tab" data-bs-toggle="tab" data-bs-target="#dead-pane" type="button" role="tab" aria-controls="dead-pane" aria-selected="false">
                            Deads (<span id="dead-count">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="invalid-tab" data-bs-toggle="tab" data-bs-target="#invalid-pane" type="button" role="tab" aria-controls="invalid-pane" aria-selected="false">
                            Inválidas (<span id="invalid-count">0</span>)
                        </button>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="myTabContent">
                    <div class="tab-pane fade show active" id="live-pane" role="tabpanel" aria-labelledby="live-tab" tabindex="0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Resultados Lives</h5>
                            <button id="copy-all-btn" class="btn btn-secondary copy-all-btn" disabled>
                                <i class="fas fa-copy"></i> Copiar Todas as Lives
                            </button>
                        </div>
                        <div id="live-results" class="results-container">
                            <p class="text-center text-muted">Nenhum cartão verificado ainda.</p>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="dead-pane" role="tabpanel" aria-labelledby="dead-tab" tabindex="0">
                        <h5>Resultados Deads</h5>
                        <div id="dead-results" class="results-container">
                            <p class="text-center text-muted">Nenhum cartão verificado ainda.</p>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="invalid-pane" role="tabpanel" aria-labelledby="invalid-tab" tabindex="0">
                        <h5>Resultados Inválidas</h5>
                        <div id="invalid-results" class="results-container">
                            <p class="text-center text-muted">Nenhum cartão verificado ainda.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const form = $('#checker-form');
        const liveResultsDiv = $('#live-results');
        const deadResultsDiv = $('#dead-results');
        const invalidResultsDiv = $('#invalid-results');
        const liveCountSpan = $('#live-count');
        const deadCountSpan = $('#dead-count');
        const invalidCountSpan = $('#invalid-count');
        const limiteRestanteSpan = $('#limite-restante');
        const loadingBar = $('.loading-bar');
        const copyAllBtn = $('#copy-all-btn');

        let liveCount = 0;
        let deadCount = 0;
        let invalidCount = 0;
        let liveCards = [];

        function addResult(status, message, details) {
            const resultItem = $('<div>').addClass('result-item');
            
            let statusBadge = $('<span>').addClass('badge rounded-pill');
            let messageText = '';
            let cardDetails = '';
            
            if (status === 'LIVE') {
                statusBadge.addClass('live').text('LIVE');
                messageText = message;
                cardDetails = `<p class="mb-0">
                    <span class="text-muted">Card:</span> ${details.card} <br>
                    <span class="text-muted">Exp:</span> ${details.exp} <br>
                    <span class="text-muted">CVC:</span> ${details.cvc} <br>
                    <span class="text-muted">Bandeira:</span> ${details.brand}
                </p>`;
                
                // Adiciona ao array de lives
                liveCards.push(details.card + '|' + details.exp + '|' + details.cvc);
                updateCopyAllButton();
                
                liveResultsDiv.prepend(resultItem.html(`
                    <div class="d-flex justify-content-between align-items-start">
                        ${statusBadge[0].outerHTML}
                        <span class="result-message">${messageText}</span>
                        <a href="javascript:void(0)" class="copy-btn" onclick="copyResult(this)"><i class="fas fa-copy"></i></a>
                    </div>
                    ${cardDetails}
                `));
                liveCount++;
                liveCountSpan.text(liveCount);
                if (liveResultsDiv.find('.text-muted').length > 0) {
                     liveResultsDiv.find('.text-muted').remove();
                }

            } else if (status === 'DEAD') {
                statusBadge.addClass('dead').text('DEAD');
                messageText = message;
                cardDetails = `<p class="mb-0">
                    <span class="text-muted">Card:</span> ${details.card} <br>
                    <span class="text-muted">Exp:</span> ${details.exp} <br>
                    <span class="text-muted">CVC:</span> ${details.cvc} <br>
                    <span class="text-muted">Bandeira:</span> ${details.brand}
                </p>`;
                
                deadResultsDiv.prepend(resultItem.html(`
                    <div class="d-flex justify-content-between align-items-start">
                        ${statusBadge[0].outerHTML}
                        <span class="result-message">${messageText}</span>
                        <a href="javascript:void(0)" class="copy-btn" onclick="copyResult(this)"><i class="fas fa-copy"></i></a>
                    </div>
                    ${cardDetails}
                `));
                deadCount++;
                deadCountSpan.text(deadCount);
                if (deadResultsDiv.find('.text-muted').length > 0) {
                     deadResultsDiv.find('.text-muted').remove();
                }

            } else { // INVALID
                statusBadge.addClass('invalid').text('INVALID');
                messageText = message;
                cardDetails = `<p class="mb-0">
                    <span class="text-muted">Card:</span> ${details.card || 'N/A'} <br>
                    <span class="text-muted">Bandeira:</span> ${details.brand || 'N/A'}
                </p>`;
                
                invalidResultsDiv.prepend(resultItem.html(`
                    <div class="d-flex justify-content-between align-items-start">
                        ${statusBadge[0].outerHTML}
                        <span class="result-message">${messageText}</span>
                        <a href="javascript:void(0)" class="copy-btn" onclick="copyResult(this)"><i class="fas fa-copy"></i></a>
                    </div>
                    ${cardDetails}
                `));
                invalidCount++;
                invalidCountSpan.text(invalidCount);
                if (invalidResultsDiv.find('.text-muted').length > 0) {
                     invalidResultsDiv.find('.text-muted').remove();
                }
            }
        }

        form.on('submit', function(e) {
            e.preventDefault();
            
            const cardData = $(this).find('textarea').val().split('\n').filter(cc => cc.trim() !== '');
            const selectedGate = $('#gate-select').val(); // Captura o gate selecionado

            if (cardData.length === 0) {
                alert('Por favor, insira pelo menos um cartão.');
                return;
            }

            loadingBar.show();
            
            // Simulação de loop, para cada cartão
            cardData.forEach((cartao, index) => {
                setTimeout(() => {
                    $.ajax({
                        url: '',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            verificar_cartao: true,
                            cartao: cartao,
                            gate: selectedGate // Envia o gate selecionado
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                addResult(response.card_status, response.message, response.details);
                                limiteRestanteSpan.text(response.limite_restante);
                            } else {
                                // Trata o erro (ex: limite esgotado)
                                alert(response.message);
                                loadingBar.hide();
                                form.find('textarea').prop('disabled', false);
                            }
                        },
                        error: function() {
                            alert('Erro na requisição. Tente novamente.');
                            loadingBar.hide();
                            form.find('textarea').prop('disabled', false);
                        },
                        complete: function() {
                            if (index === cardData.length - 1) {
                                loadingBar.hide();
                                form.find('textarea').prop('disabled', false);
                            }
                        }
                    });
                }, index * 2000); // 2 segundos de delay por cartão para simular o tempo de resposta
            });
            
            form.find('textarea').prop('disabled', true);
        });

        function copyResult(element) {
            const parent = $(element).closest('.result-item');
            const card = parent.find('.result-message').text().split(' - ')[2].trim();
            
            navigator.clipboard.writeText(card).then(function() {
                // Show success feedback
                const originalHtml = $(element).html();
                $(element).html('<i class="fas fa-check"></i>');
                setTimeout(function() {
                    $(element).html(originalHtml);
                }, 1500);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                alert('Falha ao copiar o cartão');
            });
        }

        function updateCopyAllButton() {
            if (liveCards.length > 0) {
                copyAllBtn.disabled = false;
                copyAllBtn.innerHTML = `<i class="fas fa-copy"></i> Copiar Todas as Lives (${liveCards.length})`;
            } else {
                copyAllBtn.disabled = true;
                copyAllBtn.innerHTML = `<i class="fas fa-copy"></i> Copiar Todas as Lives`;
            }
        }

        // Initialize button state on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCopyAllButton();
        });

        function copyAllLiveCards() {
            if (liveCards.length === 0) {
                alert('No live cards to copy!');
                return;
            }
            
            const allLiveText = liveCards.join('\n');
            
            navigator.clipboard.writeText(allLiveText).then(function() {
                // Show success feedback
                copyAllBtn.innerHTML = `<i class="fas fa-check"></i> Copiado ${liveCards.length} Lives!`;
                copyAllBtn.classList.add('copied');
                
                setTimeout(function() {
                    copyAllBtn.innerHTML = `<i class="fas fa-copy"></i> Copiar Todas as Lives (${liveCards.length})`;
                    copyAllBtn.classList.remove('copied');
                }, 3000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy live cards');
            });
        }

        function clearEmptyState() {
            if (liveResultsDiv.html().includes('Nenhum cartão verificado ainda.')) {
                liveResultsDiv.html('');
            }
            if (deadResultsDiv.html().includes('Nenhum cartão verificado ainda.')) {
                deadResultsDiv.html('');
            }
            if (invalidResultsDiv.html().includes('Nenhum cartão verificado ainda.')) {
                invalidResultsDiv.html('');
            }
        }
    </script>
</body>
</html>
