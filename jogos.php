<?php

header("Content-Type: application/json; charset=utf-8");

require "conexao.php";

$metodo = $_SERVER["REQUEST_METHOD"]; 

// POST = criar jogos
if ($metodo === "POST") {
    $json = file_get_contents("php://input");
    $dados = json_decode($json, true);

    // Se receber apenas um jogo isolado, transforma em lista
    if (is_array($dados) && isset($dados["titulo"])) {
        $dados = [$dados];
    }

    if (is_array($dados) && !empty($dados)) {
        // Corrigido: 7 interrogações para bater com as 7 colunas
        $linhas = array_fill(0, count($dados), "(?, ?, ?, ?, ?, ?, ?)");
        $sql = "INSERT INTO jogos (titulo, plataforma, genero, desenvolvedora, ano_lancamento, preco, estoque) VALUES " . implode(", ", $linhas);
        
        $comando = $pdo->prepare($sql);
        
        $valores = [];
        foreach ($dados as $jogos) {
            $valores[] = $jogos["titulo"] ?? null;
            $valores[] = $jogos["plataforma"] ?? null;
            $valores[] = $jogos["genero"] ?? null;
            $valores[] = $jogos["desenvolvedora"] ?? null;
            $valores[] = $jogos["ano_lancamento"] ?? null;
            $valores[] = $jogos["preco"] ?? null;
            $valores[] = $jogos["estoque"] ?? null;
        }
        
        $comando->execute($valores);

        echo json_encode([
            "mensagem" => "Jogos cadastrados com sucesso! 👍"
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            "erro" => "Dados inválidos fornecidos."
        ]);
    }
}

// GET = listar jogos
if ($metodo === "GET") {
    $sql = "SELECT * FROM jogos ORDER BY id";
    $comando = $pdo->query($sql);
    $jogos = $comando->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($jogos);
}