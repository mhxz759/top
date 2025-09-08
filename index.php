<?php
session_start();

// Lista de usuários válidos (usuário => [senha, validade, limite_verificacoes])
$usuarios = [
    "admin" => ["1234", "2030-06-01", 1000]
];

$arquivo_verificacoes = 'verificacoes_usadas.json';

function carregarVerificacoes() {
    global $arquivo_verificacoes;
    if (file_exists($arquivo_verificacoes)) {
        return json_decode(file_get_contents($arquivo_verificacoes), true) ?: [];
    }
    return [];
}

function salvarVerificacoes($verificacoes) {
    global $arquivo_verificacoes;
    file_put_contents($arquivo_verificacoes, json_encode($verificacoes));
}

function verificarLimite($usuario) {
    global $usuarios;
    $verificacoes = carregarVerificacoes();
    $usado = isset($verificacoes[$usuario]) ? $verificacoes[$usuario] : 0;
    $limite = $usuarios[$usuario][2];
    return $limite - $usado;
}

function usarVerificacao($usuario) {
    $verificacoes = carregarVerificacoes();
    if (!isset($verificacoes[$usuario])) {
        $verificacoes[$usuario] = 0;
    }
    $verificacoes[$usuario]++;
    salvarVerificacoes($verificacoes);
}

function validarFormatoCartao($numero) {
    $numero = preg_replace('/\D/', '', $numero);
    if (strlen($numero) < 13 || strlen($numero) > 19) return false;
    $soma = 0; $alternar = false;
    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $digito = intval($numero[$i]);
        if ($alternar) {
            $digito *= 2;
            if ($digito > 9) $digito -= 9;
        }
        $soma += $digito;
        $alternar = !$alternar;
    }
    return ($soma % 10) == 0;
}

function detectarBandeira($numero) {
    $numero = preg_replace('/\D/', '', $numero);
    if (preg_match('/^4/', $numero)) return 'Visa';
    elseif (preg_match('/^5[1-5]/', $numero)) return 'Mastercard';
    elseif (preg_match('/^3[47]/', $numero)) return 'Amex';
    elseif (preg_match('/^6/', $numero)) return 'Discover';
    return 'Unknown';
}

function verificarCartao($cartao, $gate) {
    sleep(2);
    $dados = explode('|', $cartao);
    if (count($dados) < 4) {
        return ['status' => 'INVALID','message' => 'Formato inválido'];
    }
    $numero = trim($dados[0]);
    $mes = trim($dados[1]);
    $ano = trim($dados[2]);
    $cvc = trim($dados[3]);

    if (!validarFormatoCartao($numero)) {
        return ['status' => 'INVALID','message' => 'Número inválido'];
    }

    $isLive = rand(1, 100) <= 20;
    if ($gate === 'stripe') {
        if ($isLive) {
            return ['status' => 'LIVE','message' => 'Stripe: Payment authorized'];
        } else {
            return ['status' => 'DEAD','message' => 'Stripe: Card declined'];
        }
    } else {
        if ($isLive) {
            return ['status' => 'LIVE','message' => 'PayPal: Transaction approved'];
        } else {
            return ['status' => 'DEAD','message' => 'PayPal: Transaction failed'];
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["verificar_cartao"]) && isset($_SESSION["logado"])) {
    $cartao = $_POST["cartao"];
    $gate = $_POST["gate"] ?? 'stripe';
    $usuario = $_SESSION["usuario"];
    if (verificarLimite($usuario) <= 0) {
        echo json_encode(["status" => "error","message" => "Limite esgotado"]);
        exit;
    }
    usarVerificacao($usuario);
    $resultado = verificarCartao($cartao, $gate);
    echo json_encode(["status" => "success","card_status" => $resultado['status'],"message" => $resultado['message'],"limite_restante" => verificarLimite($usuario)]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["usuario"]) && isset($_POST["senha"])) {
    $usuario = $_POST["usuario"];
    $senha = $_POST["senha"];
    if (isset($usuarios[$usuario]) && $usuarios[$usuario][0] === $senha) {
        $_SESSION["logado"] = true;
        $_SESSION["usuario"] = $usuario;
        $_SESSION["validade"] = $usuarios[$usuario][1];
    } else {
        $erro = "Login inválido";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true) {
?>
<!DOCTYPE html>
<html>
<head><title>HeadersCenter V2.0 - Login</title></head>
<body>
<form method="POST">
    <input type="text" name="usuario" placeholder="Usuário" required>
    <input type="password" name="senha" placeholder="Senha" required>
    <button type="submit">Entrar</button>
</form>
</body>
</html>
<?php exit; } ?>
<!DOCTYPE html>
<html>
<head>
    <title>HeadersCenter V2.0</title>
    <style>
        .card-result.live {border-left: 4px solid #2196f3; background: rgba(33,150,243,0.1);}
        .card-result.rejected {border-left: 4px solid #f44336; background: rgba(244,67,54,0.1);}
    </style>
</head>
<body>
    <h1>HeadersCenter V2.0</h1>
    <select id="gateSelect">
        <option value="stripe">Stripe</option>
        <option value="paypal">PayPal</option>
    </select>
    <textarea id="cardInput"></textarea>
    <button id="startBtn">Start</button>
    <div id="results"></div>

    <script>
    document.getElementById('startBtn').addEventListener('click', ()=>{
        const cards = document.getElementById('cardInput').value.trim().split('\n');
        const gate = document.getElementById('gateSelect').value;
        cards.forEach(card=>{
            const formData = new FormData();
            formData.append('verificar_cartao','true');
            formData.append('cartao',card);
            formData.append('gate',gate);
            fetch('index.php',{method:'POST',body:formData})
              .then(r=>r.json()).then(data=>{
                const div = document.createElement('div');
                div.className = 'card-result ' + (data.card_status==='LIVE'?'live':'rejected');
                div.innerText = card+' | '+data.message;
                document.getElementById('results').prepend(div);
            });
        });
    });
    </script>
</body>
</html>