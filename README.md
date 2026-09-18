# API REST de Gestão de Jogos (PHP & PostgreSQL)

Este repositório contém a implementação de uma API REST em PHP para cadastro e listagem de jogos, utilizando PostgreSQL e a biblioteca PDO. O projeto foi desenhado para ser seguro, rápido e capaz de processar inserções de múltiplos registros em lote (batch insert).

---

## Tecnologias Utilizadas

* **PHP 8+**: Linguagem de programação backend.
* **PostgreSQL**: Banco de dados relacional.
* **PDO (PHP Data Objects)**: Interface de conexão segura entre o PHP e o banco de dados.
* **JSON**: Formato leve de troca de dados entre cliente (Postman/Insomnia) e servidor.

---

## Estrutura do Banco de Dados

A tabela `jogos` foi estruturada para armazenar as principais informações sobre cada título:

```sql
CREATE TABLE jogos (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    plataforma VARCHAR(100) NOT NULL,
    genero VARCHAR(100) NOT NULL,
    desenvolvedora VARCHAR(100) NOT NULL,
    ano_lancamento INT NOT NULL,
    preco NUMERIC(10, 2) NOT NULL,
    estoque INT NOT NULL
);

```

---

## Explicação Detalhada do Código (jogos.php)

Abaixo está o passo a passo de como o código foi construído e o motivo de cada função.

### 1. Definição do Cabeçalho e Conexão

```php
header("Content-Type: application/json; charset=utf-8");
require "conexao.php";
$metodo = $_SERVER["REQUEST_METHOD"];

```

* **`header(...)`**: Informa ao navegador ou cliente que a resposta será enviada no formato JSON.
* **`require "conexao.php"`**: Importa a variável `$pdo` com a conexão ativa ao PostgreSQL.
* **`$_SERVER["REQUEST_METHOD"]`**: Identifica o verbo HTTP utilizado na requisição (`GET` para leitura, `POST` para criação).

---

### 2. Rota POST — Cadastro de Jogos (Único ou em Lote)

```php
if ($metodo === "POST") {
    $json = file_get_contents("php://input");
    $dados = json_decode($json, true);

    if (is_array($dados) && isset($dados["titulo"])) {
        $dados = [$dados];
    }

```

* **`file_get_contents("php://input")`**: Lê o corpo bruto (*body*) da requisição enviada no formato JSON.
* **`json_decode($json, true)`**: Converte o texto JSON em um *array* navegável do PHP.
* **Normalização**: Se for enviado apenas um jogo (objeto único), o código o envolve dentro de uma lista para padronizar o processamento.

#### Montagem Dinâmica e Execução Segura (PDO)

```php
    if (is_array($dados) && !empty($dados)) {
        $linhas = array_fill(0, count($dados), "(?, ?, ?, ?, ?, ?, ?)");
        $sql = "INSERT INTO jogos (titulo, plataforma, genero, desenvolvedora, ano_lancamento, preco, estoque) VALUES " . implode(", ", $linhas);
        
        $comando = $pdo->prepare($sql);
        
        $valores = [];
        foreach ($dados as $jogos) {
            $valores[] = $jogos["titulo"] ?? null;
            $valores[] = $jogos["plataforma"] ?? null;
            $valores[] = $jogos["genero"] ?? null;
            $valores[] = $jogos["desenvolvedora"] ?? null;
            $valores[] = $jogos["ano_lancamento"] ?? $jogos["ano_publicacao"] ?? null;
            $valores[] = $jogos["preco"] ?? null;
            $valores[] = $jogos["estoque"] ?? null;
        }
        
        $comando->execute($valores);

        echo json_encode(["mensagem" => "Jogos cadastrados com sucesso!"]);
    }
}

```

---

### 3. Rota GET — Listagem de Jogos

```php
if ($metodo === "GET") {
    $sql = "SELECT * FROM jogos ORDER BY id ASC";
    $comando = $pdo->query($sql);
    $jogos = $comando->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($jogos);
}

```

* **`ORDER BY id ASC`**: Garante que os registros sejam retornados em ordem crescente de ID.
* **`fetchAll(PDO::FETCH_ASSOC)`**: Converte todas as linhas da tabela em um *array* associativo formatado.

---

## Conceitos Fundamentais para Leigos

| Conceito | O que é? | Por que usamos? |
| --- | --- | --- |
| **PDO** | Uma ponte universal entre o PHP e o Banco de Dados. | Permite conectar a qualquer banco (PostgreSQL, MySQL, SQLite) usando a mesma estrutura de código. |
| **Marcador `?**` | Um espaço reservado no comando SQL. | Evita colocar variáveis direto no comando, garantindo segurança contra ataques de SQL Injection. |
| **`prepare()`** | A criação do modelo do comando SQL no banco. | O banco lê a estrutura antes de receber os dados, garantindo que nenhum texto malicioso altere a lógica da consulta. |
| **`execute()`** | O ato de preencher as lacunas (`?`) com os valores reais. | Insere os dados de forma limpa e rápida na tabela. |

---

## Correções e Ajustes Realizados no Projeto

1. **Correção de Colunas e Interrogações**:
Ajustou-se a quantidade de marcadores `?` para coincidir exatamente com as 7 colunas da tabela (`titulo`, `plataforma`, `genero`, `desenvolvedora`, `ano_lancamento`, `preco`, `estoque`).
2. **Remoção de Colunas Duplicadas**:
Foi removida a coluna redundante `ano_publicacao` da tabela via banco de dados:
```sql
ALTER TABLE jogos DROP COLUMN ano_publicacao;

```


3. **Reorganização de IDs e Ajuste de Sequência**:
Para reordenar os registros e ajustar o contador automático do PostgreSQL para continuar a partir do próximo ID correto:
```sql
-- Subtrai os IDs para ajustar a numeração para iniciar em 1
UPDATE jogos SET id = id - 6;

-- Sincroniza a sequência automática para o próximo ID
SELECT setval(pg_get_serial_sequence('jogos', 'id'), (SELECT MAX(id) FROM jogos));

```



---

## Como Testar a API

### Cadastrar Jogos (`POST /jogos.php`)

Envie uma requisição com o corpo no formato JSON:

```json
[
  {
    "titulo": "Minecraft",
    "plataforma": "PC",
    "genero": "Sandbox",
    "desenvolvedora": "Mojang",
    "ano_lancamento": 2011,
    "preco": 99.90,
    "estoque": 25
  }
]

```

### Consultar Jogos (`GET /jogos.php`)

Realize uma requisição `GET` para receber a lista completa de jogos em formato JSON.