<?php
namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetPedido
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response)
    {
        try {
            // Obter parâmetros da requisição
            $queryParams = $request->getQueryParams();
            $busca = isset($queryParams['busca']) ? $queryParams['busca'] : '';
            /*$limit = isset($queryParams['limit']) ? (int) $queryParams['limit'] : 10;
            $offset = isset($queryParams['offset']) ? (int) $queryParams['offset'] : 0;*/

            $dataInicial = isset($queryParams['data_inicial']) ? (string) $queryParams['data_inicial'] : null;
            $dataFinal = isset($queryParams['data_final']) ? (string) $queryParams['data_final'] : null;

            // Construir a consulta SQL base
            $sql = "SELECT * FROM pedidos WHERE numero_pedido_pai is NULL";
            if (!empty($busca)) {
                $sql .= " AND (cliente LIKE :busca OR nome_fantasia LIKE :busca OR numero_pedido LIKE :busca OR cnpj LIKE :busca OR vendedor LIKE :busca)";
            }

            if (!empty($dataInicial) && !empty($dataFinal)) {
                $sql .= " AND data_emissao BETWEEN :data_inicial AND :data_final";
            }
            $sql .= " ORDER BY id DESC";

            $stmt = $this->pdo->prepare($sql);

            // Vincular parâmetros
            if (!empty($busca)) {
                $stmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }
            if (!empty($dataInicial) && !empty($dataFinal)) {
                $stmt->bindValue(':data_inicial', $dataInicial, PDO::PARAM_STR);
                $stmt->bindValue(':data_final', $dataFinal, PDO::PARAM_STR);
            }
            /*$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);*/

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

            // Obter o total de registros para paginação
            $countSql = "SELECT COUNT(*) AS total FROM pedidos";
            if (!empty($busca)) {
                $countSql .= " WHERE (cliente LIKE :busca OR nome_fantasia LIKE :busca)";
            }
            $countStmt = $this->pdo->prepare($countSql);
            if (!empty($busca)) {
                $countStmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withJson([
                    'data' => $pedidos,
                    'total' => $total
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
