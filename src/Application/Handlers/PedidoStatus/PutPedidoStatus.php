<?php
namespace App\Application\Handlers\PedidoStatus;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutPedidoStatus
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $id = (int)$args['id'];
            $data = $request->getParsedBody();

            $descricao = isset($data['descricao']) ? (bool)$data['descricao'] : false;
            $status = isset($data['status']) ? $data['status'] : null;
            $hex_rgb = isset($data['hex_rgb']) ? (bool)$data['hex_rgb'] : false;
            $auto_checked = isset($data['auto_checked']) ? (bool)$data['auto_checked'] : false;

            $stmt = $this->pdo->prepare("UPDATE pedido_status SET descricao = :descricao, status = :status, hex_rgb = :hex_rgb, auto_checked = :auto_checked WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':hex_rgb', $hex_rgb);
            $stmt->bindParam(':auto_checked', $auto_checked);

            if ($stmt->execute()) {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success']);
            } else {
                return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'error'], 500);
            }

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}
