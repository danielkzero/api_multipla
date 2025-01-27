<?php
namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class DeletePedidoItem
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

        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Delete item do pedido
            $stmt = $this->pdo->prepare("DELETE FROM itens_pedido WHERE pedido_id = :pedido_id AND codigo = :codigo");
            $stmt->execute([':pedido_id' => $pedido_id, ':codigo' => $codigo]);

            // Commit transaction
            $this->pdo->commit();

            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Item deletado com sucesso', 'pedido_id' => $pedido_id, 'codigo' => $codigo]);

        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->pdo->rollBack();
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao deletar pedido', 'error' => $e->getMessage()]);
        }
    }
}
