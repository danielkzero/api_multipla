<?php
// routes.php
namespace App\Application\Handlers\Usuario;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutUsuarioId
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $id = $args['id'];
            $body = $request->getParsedBody();

            // Captura os novos dados
            $nome = $body['nome'];
            $email = $body['email'];
            $usuario = $body['usuario'];
            $avatar = isset($body['avatar']) ? $body['avatar'] : null; // Verifica se o avatar está presente
            $telefone = isset($body['telefone']) ? $body['telefone'] : null; // Captura o telefone
            $tipo_permissao = $body['tipo_permissao']; // Captura o tipo de permissão
            $permissao = json_encode($body['permissao']); // Converte as permissões para JSON
            $assinatura_email = isset($body['assinatura_email']) ? $body['assinatura_email'] : null; // Captura a assinatura de email
            $senha = isset($body['senha']) ? md5($body['senha']) : null;

            // Prepara a consulta SQL para atualizar os dados do usuário
            $sql = 'UPDATE usuario SET 
                nome=:nome, 
                email=:email, 
                usuario=:usuario' . 
                ($avatar ? ', avatar=:avatar' : '') . // Adiciona a coluna de avatar somente se fornecida
                ($telefone ? ', telefone=:telefone' : '') . // Adiciona a coluna de telefone somente se fornecida
                ($assinatura_email ? ', assinatura_email=:assinatura_email' : '') . // Adiciona a coluna de assinatura_email somente se fornecida
                ($senha ? ', senha=:senha' : '') . // Adiciona a coluna de assinatura_email somente se fornecida
                ', tipo_permissao=:tipo_permissao, 
                permissao=:permissao 
            WHERE id=:id';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':usuario', $usuario);
            if ($avatar) {
                $stmt->bindParam(':avatar', $avatar);
            }
            if ($telefone) {
                $stmt->bindParam(':telefone', $telefone);
            }
            if ($assinatura_email) {
                $stmt->bindParam(':assinatura_email', $assinatura_email);
            }
            if ($senha) {
                $stmt->bindParam(':senha', $senha);
            }
            $stmt->bindParam(':tipo_permissao', $tipo_permissao); // Adiciona tipo_permissao
            $stmt->bindParam(':permissao', $permissao);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $response->withHeader('Content-Type', 'application/json')->withJson(['success' => true]);

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}
