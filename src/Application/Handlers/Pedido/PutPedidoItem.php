<?php

namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PutPedidoItem
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $pedido_id = (int)$args['id'];
        $codigo = (string)$args['codigo'];
        $data = $request->getParsedBody();

        try {
            // Begin Transaction
            $this->pdo->beginTransaction();

            // Insert Itens do Pedido
            $stmt = $this->pdo->prepare("UPDATE itens_pedido 
                SET quantidade=:quantidade, preco_tabela=:preco_tabela, desconto=:desconto, preco_liquido=:preco_liquido, subtotal=:subtotal
                WHERE pedido_id=:pedido_id AND codigo=:codigo
            ");
            $stmt->execute([
                ':pedido_id' => $pedido_id,
                ':codigo' => $codigo,
                ':quantidade' => $data['quantidade'],
                ':preco_tabela' => $data['preco_tabela'],
                ':desconto' => $data['desconto'],
                ':preco_liquido' => $data['preco_liquido'],
                ':subtotal' => $data['subtotal']
            ]);

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
