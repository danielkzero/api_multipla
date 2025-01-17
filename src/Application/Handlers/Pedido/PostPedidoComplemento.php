<?php

namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PostPedidoComplemento
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
            // Begin Transaction
            $this->pdo->beginTransaction();

            // Insert Pedido
            $stmt = $this->pdo->prepare("
                UPDATE pedidos SET numero_pedido_pai = :numero_pedido_pai WHERE numero_pedido = :numero_pedido
            ");
            $stmt->bindValue(':numero_pedido', $data['numero_pedido']);
            $stmt->bindValue(':numero_pedido_pai', $data['numero_pedido_pai']);
            $stmt->execute();

            // Commit Transaction
            $this->pdo->commit();

            return $response->withHeader('Content-Type', 'application/json')
                ->withJson(['status' => 'Pedido vinculado como complemento!']);
        } catch (\Exception $e) {
            // Rollback Transaction
            $this->pdo->rollBack();

            return $response->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->withJson(['status' => 'Erro ao criar pedido', 'error' => $e->getMessage()]);
        }
    }
}
