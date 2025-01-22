<?php

namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PutPedidoComplementoById
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
            $stmt = $this->pdo->prepare("UPDATE pedidos SET numero_pedido_pai = NULL WHERE id = :id
            ");
            $stmt->execute([':id' => $id]);

            // Commit Transaction
            $this->pdo->commit();
            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Complemento removido com sucesso']);
        } catch (\Exception $e) {
            // Rollback Transaction
            $this->pdo->rollBack();
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao atualizar pedido', 'error' => $e->getMessage()]);
        }
    }
}
