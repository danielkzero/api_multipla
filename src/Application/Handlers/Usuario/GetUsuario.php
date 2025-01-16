<?php
namespace App\Application\Handlers\Usuario;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetUsuario
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
            $limit = isset($queryParams['limit']) ? (int) $queryParams['limit'] : 10;
            $offset = isset($queryParams['offset']) ? (int) $queryParams['offset'] : 0;

            // Construir a consulta SQL com base nos parâmetros
            $sql = 'SELECT * FROM usuario WHERE excluido = 0';
            if (!empty($busca)) {
                $sql .= ' AND (nome LIKE :busca OR email LIKE :busca)';
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
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obter o total de registros para a paginação
            $countSql = 'SELECT COUNT(*) AS total FROM usuario WHERE excluido = 0';
            if (!empty($busca)) {
                $countSql .= ' AND (nome LIKE :busca OR email LIKE :busca)';
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
                    'data' => $result,
                    'total' => $total
                ]);
        } catch (\Exception $e) {
            // Adicionar um código de status mais apropriado para erros
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
