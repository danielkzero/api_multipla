<?php
namespace App\Application\Handlers\PedidoStatus;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetPedidoStatus
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
            $queryParams = $request->getQueryParams();
            $busca = isset($queryParams['busca']) ? $queryParams['busca'] : '';
            $limit = isset($queryParams['limit']) ? (int) $queryParams['limit'] : 10;
            $offset = isset($queryParams['offset']) ? (int) $queryParams['offset'] : 0;

            // Validar parâmetros
            if ($limit < 1 || $limit > 100) {
                return $response->withStatus(400)
                    ->withHeader('Content-Type', 'application/json')
                    ->withJson(['status' => 'Limite inválido, deve ser entre 1 e 100']);
            }

            // Construir a consulta SQL para pedido_status
            $sql = 'SELECT * FROM pedido_status WHERE 1=1';
            if (!empty($busca)) {
                $sql .= ' AND (status LIKE :busca OR descricao LIKE :busca)';
            }
            $sql .= ' ORDER BY id DESC LIMIT :limit OFFSET :offset';

            $stmt = $this->pdo->prepare($sql);

            // Vincular os parâmetros
            if (!empty($busca)) {
                $stmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obter o total de registros para a paginação
            $countSql = 'SELECT COUNT(*) AS total FROM pedido_status WHERE 1=1';
            if (!empty($busca)) {
                $countSql .= ' AND (status LIKE :busca OR descricao LIKE :busca)';
            }

            $countStmt = $this->pdo->prepare($countSql);
            if (!empty($busca)) {
                $countStmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Retornar resposta com dados e total
            return $response->withHeader('Content-Type', 'application/json')
                ->withJson([
                    'data' => $data,
                    'total' => $total
                ]);

        } catch (Exception $e) {
            return $response->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->withJson([
                    'status' => 'Erro ao buscar pedido_status',
                    'error' => $e->getMessage()
                ]);
        }
    }
}
