<?php
namespace App\Application\Handlers\Empresa;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class DeleteEmpresa
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $empresa_id = (int) $args['id'];

            $stmt = $this->pdo->prepare("DELETE FROM empresa WHERE id = :id");
            $stmt->execute([':id' => $empresa_id]); 

            if ($stmt->execute()) {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['message' => 'Empresa excluído com sucesso'], 200);
            } else {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['error' => 'Empresa não encontrado'], 404);
            }
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}