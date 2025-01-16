<?php
//routes.php
namespace App\Application\Handlers\Usuario;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class DeleteUsuarioId
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {            
            $stmt = $this->pdo->prepare('UPDATE usuario SET excluido = 1 WHERE id=:id');
            $stmt->bindParam(':id', $args['id']);
            $stmt->execute();
            return $response->withHeader('Content-Type', 'application/json')->withJson(['success' => true]);

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}