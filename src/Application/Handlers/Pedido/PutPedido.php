<?php

namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PutPedido
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();

        try {
            // Begin Transaction
            $this->pdo->beginTransaction();
            // Update Pedido
            $stmt = $this->pdo->prepare("
                UPDATE pedido 
                SET 
                    cliente = :cliente, cliente_id = :cliente_id, condicao_pagamento = :condicao_pagamento, contato_cliente = :contato_cliente, criador_id = :criador_id, 
                    enderecos = :enderecos, excluido = :excluido, status = :status, id_tabela_preco = :id_tabela_preco, observacoes = :observacoes, representada = :representada, 
                    status = :status, status_faturamento = :status_faturamento, tabela_preco = :tabela_preco, tipo_pedido = :tipo_pedido, total = :total, vendedor = :vendedor
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':cliente' => ($data['cliente'] !== null && $data['cliente'] !== '') ? json_encode($data['cliente']) : null,
                ':cliente_id' => $data['cliente_id'] ?? null,
                ':condicao_pagamento' => ($data['condicao_pagamento'] !== null && $data['condicao_pagamento'] !== '') ? json_encode($data['condicao_pagamento']) : null,
                ':contato_cliente' => ($data['contato_cliente'] !== null && $data['contato_cliente'] !== '') ? json_encode($data['contato_cliente']) : null,
                ':criador_id' => $data['criador_id'] ?? null,
                ':enderecos' => ($data['enderecos'] !== null && $data['enderecos'] !== '') ? json_encode($data['enderecos']) : null,
                ':excluido' => $data['excluido'] ?? null,
                ':status' => $data['status'] ?? null,
                ':id_tabela_preco' => $data['id_tabela_preco'] ?? null,
                ':observacoes' => $data['observacoes'] ?? null,
                ':representada' => ($data['representada'] !== null && $data['representada'] !== '') ? json_encode($data['representada']) : null,
                ':status' => $data['status'] ?? 'O',
                ':status_faturamento' => $data['status_faturamento'] ?? null,
                ':tabela_preco' => $data['tabela_preco'] ?? null,
                ':tipo_pedido' => ($data['tipo_pedido'] !== null && $data['tipo_pedido'] !== '') ? $data['tipo_pedido'] : null,
                ':total' => $data['total'] ?? null,
                ':vendedor' => ($data['vendedor'] !== null && $data['vendedor'] !== '') ? json_encode($data['vendedor']) : null 
            ]);

            // Insert new Itens do Pedido
            if (isset($data['itens'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO pedido_item (id, pedido_id, qtd_unitaria, quantidade, produto_id, codigo, nome, st, ipi, 
                    comissao, item_acrescimo, item_desconto, preco_liquido, preco_minimo, preco_tabela, subtotal, excluido)
                    VALUES (:id, :pedido_id, :qtd_unitaria, :quantidade, :produto_id, :codigo, :nome, :st, :ipi, 
                    :comissao, :item_acrescimo, :item_desconto, :preco_liquido, :preco_minimo, :preco_tabela, :subtotal, :excluido) 
                    ON DUPLICATE KEY UPDATE 
                    qtd_unitaria = VALUES(qtd_unitaria), 
                    quantidade = VALUES(quantidade), 
                    produto_id = VALUES(produto_id), 
                    codigo = VALUES(codigo),
                    nome = VALUES(nome), 
                    st = VALUES(st), 
                    ipi = VALUES(ipi), 
                    comissao = VALUES(comissao), 
                    item_acrescimo = VALUES(item_acrescimo), 
                    item_desconto = VALUES(item_desconto), 
                    preco_liquido = VALUES(preco_liquido), 
                    preco_minimo = VALUES(preco_minimo), 
                    preco_tabela = VALUES(preco_tabela), 
                    subtotal = VALUES(subtotal), 
                    excluido = VALUES(excluido) 
                ");

                $stmtCheck = $this->pdo->prepare("
                    SELECT * 
                    FROM pedido_item 
                    WHERE pedido_id = :pedido_id AND produto_id = :produto_id AND excluido = 0
                ");

                foreach ($data['itens'] as $item) {
                    $stmtCheck->execute([
                        ':pedido_id' => $id,
                        ':produto_id' => $item['produto_id']
                    ]);

                    $existingItem = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                    $stmt->execute([
                        ':id' => $existingItem ? $existingItem['id'] : null,
                        ':pedido_id' => $id,
                        ':qtd_unitaria' => $item['qtd_unitaria'],
                        ':quantidade' => $item['quantidade'],
                        //':tabela_preco_id' => $item['tabela_preco_id'],
                        ':produto_id' => $item['produto_id'],
                        ':codigo' => $item['codigo'],
                        ':nome' => $item['nome'],
                        ':st' => $item['icms_destino'],
                        ':ipi' => $item['ipi'],
                        ':comissao' => $item['comissao'],
                        ':item_acrescimo' => json_encode($item['item_acrescimo'] ?? []),
                        ':item_desconto' => json_encode($item['item_desconto'] ?? []),
                        ':preco_liquido' => $item['preco_liquido'],
                        ':preco_minimo' => $item['preco_minimo'],
                        ':preco_tabela' => $item['preco_tabela'],
                        ':subtotal' => $item['subtotal'],
                        ':excluido' => $item['excluido']
                    ]);
                }
            }

            // Commit Transaction
            $this->pdo->commit();
            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Pedido atualizado com sucesso']);
        } catch (\Exception $e) {
            // Rollback Transaction
            $this->pdo->rollBack();
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao atualizar pedido', 'error' => $e->getMessage()]);
        }
    }
}
