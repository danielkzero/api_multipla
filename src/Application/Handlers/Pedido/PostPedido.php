<?php

namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PostPedido
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        try {
            if (isset($data['cabecalho']['Data de Emissão']) && !empty($data['cabecalho']['Data de Emissão'])) {
                $date = \DateTime::createFromFormat('d/m/Y', $data['cabecalho']['Data de Emissão']);
                if ($date) {
                    $data['cabecalho']['Data de Emissão'] = $date->format('Y-m-d');
                } else {
                    throw new \Exception("Formato inválido para Data de Emissão: {$data['cabecalho']['Data de Emissão']}");
                }
            }

            // Begin Transaction
            $this->pdo->beginTransaction();

            // Insert Pedido
            $stmt = $this->pdo->prepare("
                INSERT INTO pedidos (
                    numero_pedido, numero_pedido_pai, condicao_pagamento, data_emissao, vendedor, tipo_pedido,
                    logo_esquerda, titulo, logo_direita, representada, cliente, nome_fantasia, cnpj,
                    inscricao_estadual, endereco, bairro, cep, cidade, estado, telefone, email, informacoes_adicionais
                ) VALUES (
                    :numero_pedido, :numero_pedido_pai, :condicao_pagamento, :data_emissao, :vendedor, :tipo_pedido,
                    :logo_esquerda, :titulo, :logo_direita, :representada, :cliente, :nome_fantasia, :cnpj,
                    :inscricao_estadual, :endereco, :bairro, :cep, :cidade, :estado, :telefone, :email, :informacoes_adicionais
                )
                ON DUPLICATE KEY UPDATE
                    numero_pedido_pai = VALUES(numero_pedido_pai),
                    condicao_pagamento = VALUES(condicao_pagamento),
                    data_emissao = VALUES(data_emissao),
                    vendedor = VALUES(vendedor),
                    tipo_pedido = VALUES(tipo_pedido),
                    logo_esquerda = VALUES(logo_esquerda),
                    titulo = VALUES(titulo),
                    logo_direita = VALUES(logo_direita),
                    representada = VALUES(representada),
                    cliente = VALUES(cliente),
                    nome_fantasia = VALUES(nome_fantasia),
                    cnpj = VALUES(cnpj),
                    inscricao_estadual = VALUES(inscricao_estadual),
                    endereco = VALUES(endereco),
                    bairro = VALUES(bairro),
                    cep = VALUES(cep),
                    cidade = VALUES(cidade),
                    estado = VALUES(estado),
                    telefone = VALUES(telefone),
                    email = VALUES(email),
                    informacoes_adicionais = VALUES(informacoes_adicionais);
            ");
            $stmt->bindValue(':bairro', $data['cabecalho']['Bairro']);
            $stmt->bindValue(':cep', $data['cabecalho']['CEP'] ?? null);
            $stmt->bindValue(':cnpj', $data['cabecalho']['CNPJ'] ?? null);
            $stmt->bindValue(':cidade', $data['cabecalho']['Cidade']);
            $stmt->bindValue(':cliente', $data['cabecalho']['Cliente']);
            $stmt->bindValue(':condicao_pagamento', $data['cabecalho']['Condição de Pagamento'] ?? null);
            $stmt->bindValue(':data_emissao', $data['cabecalho']['Data de Emissão']);
            $stmt->bindValue(':email', $data['cabecalho']['E-mail']);
            $stmt->bindValue(':endereco', $data['cabecalho']['Endereço']);
            $stmt->bindValue(':estado', $data['cabecalho']['Estado'] ?? null);
            $stmt->bindValue(':nome_fantasia', $data['cabecalho']['Nome Fantasia'] ?? null);
            $stmt->bindValue(':representada', $data['cabecalho']['Representada']);
            $stmt->bindValue(':telefone', $data['cabecalho']['Telefone']);
            $stmt->bindValue(':tipo_pedido', $data['cabecalho']['Tipo de pedido'] ?? null);
            $stmt->bindValue(':vendedor', $data['cabecalho']['Vendedor'] ?? null);
            $stmt->bindValue(':logo_direita', $data['cabecalho']['logo_direita'] ?? null);
            $stmt->bindValue(':logo_esquerda', $data['cabecalho']['logo_esquerda'] ?? null);
            $stmt->bindValue(':numero_pedido', $data['cabecalho']['numero_pedido']);
            $stmt->bindValue(':titulo', $data['cabecalho']['titulo'] ?? null);


            $stmt->bindValue(':informacoes_adicionais', isset($data['cabecalho']['Informações Adicionais']) ? trim($data['cabecalho']['Informações Adicionais']) : null, PDO::PARAM_STR);
            $stmt->bindValue(':numero_pedido_pai', isset($data['cabecalho']['numero_pedido_pai']) ? $data['cabecalho']['numero_pedido_pai'] : null);
            $stmt->bindValue(':inscricao_estadual', isset($data['cabecalho']['Inscrição Estadual']) ? $data['cabecalho']['Inscrição Estadual'] : null);
            
            
            $stmt->execute();

            // Insert Itens do Pedido
            if (!empty($data['detalhes'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO itens_pedido (
                        pedido_id, codigo, produto, quantidade, preco_tabela, desconto, preco_liquido, subtotal
                    ) VALUES (
                        :pedido_id, :codigo, :produto, :quantidade, :preco_tabela, :desconto, :preco_liquido, :subtotal
                    )
                    ON DUPLICATE KEY UPDATE
                        codigo = VALUES(codigo),
                        produto = VALUES(produto),
                        quantidade = VALUES(quantidade),
                        preco_tabela = VALUES(preco_tabela),
                        desconto = VALUES(desconto),
                        preco_liquido = VALUES(preco_liquido),
                        subtotal = VALUES(subtotal);
                ");

                foreach ($data['detalhes'] as $item) {
                    $stmt->execute([
                        ':pedido_id' => $data['cabecalho']['numero_pedido'],
                        ':codigo' => $item['codigo'],
                        ':produto' => $item['produto'],
                        ':quantidade' => $item['quantidade'],
                        ':preco_tabela' => $item['preco_tabela'],
                        ':desconto' => $item['desconto'],
                        ':preco_liquido' => $item['preco_liquido'],
                        ':subtotal' => $item['subtotal']
                    ]);
                }
            }

            // Commit Transaction
            $this->pdo->commit();

            return $response->withHeader('Content-Type', 'application/json')
                ->withJson(['status' => 'Pedido criado com sucesso']);
        } catch (\Exception $e) {
            // Rollback Transaction
            $this->pdo->rollBack();

            return $response->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->withJson(['status' => 'Erro ao criar pedido', 'error' => $e->getMessage()]);
        }
    }
}
