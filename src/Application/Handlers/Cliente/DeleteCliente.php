<?php
namespace App\Application\Handlers\Cliente;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class DeleteCliente
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $cliente_id = (int) $args['id'];

            $stmt = $this->pdo->prepare("
                DELETE FROM cliente
                WHERE id = :id
            ");
            $stmt->execute([':id' => $cliente_id]); 

            if ($stmt->execute()) {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['message' => 'Cliente excluído com sucesso'], 200);
            } else {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['error' => 'Cliente não encontrado'], 404);
            }
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}