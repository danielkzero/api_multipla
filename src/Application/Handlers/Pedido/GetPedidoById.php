<?php
namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetPedidoById
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            // Obter parâmetros da requisição
            $id = $args['id'];

            // Construir a consulta SQL base
            $sql = "SELECT * FROM pedidos WHERE numero_pedido = :numero_pedido";

            $stmt = $this->pdo->prepare($sql);

            // Vincular parâmetros
            $stmt->bindValue(':numero_pedido', $id);

            $stmt->execute();
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obter itens associados a cada pedido
            foreach ($pedidos as &$pedido) {
                $pedidoId = $pedido['numero_pedido'];

                $itemSql = "SELECT * FROM itens_pedido WHERE pedido_id = :pedido_id AND excluido = 0 UNION 
                SELECT b.* FROM pedidos a INNER JOIN itens_pedido b ON a.numero_pedido=b.pedido_id WHERE a.numero_pedido_pai = :pedido_id";
                $itemStmt = $this->pdo->prepare($itemSql);
                $itemStmt->bindValue(':pedido_id', $pedidoId, PDO::PARAM_INT);
                $itemStmt->execute();

                $pedido['itens'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withJson([
                    'data' => $pedidos
                ]);
        } catch (\Exception $e) {
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->withJson([
                    'error' => [
                        'type' => 'SERVER_ERROR',
                        'description' => $e->getMessage()
                    ]
                ]);
        }
    }
}
