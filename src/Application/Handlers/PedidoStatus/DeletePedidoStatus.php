<?php
namespace App\Application\Handlers\PedidoStatus;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class DeletePedidoStatus
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];

        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Delete pedido_item_desconto
            $stmt = $this->pdo->prepare("DELETE FROM pedido_status WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Commit transaction
            $this->pdo->commit();

            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Status deletado com sucesso']);

        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->pdo->rollBack();
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao deletar pedido', 'error' => $e->getMessage()]);
        }
    }
}
