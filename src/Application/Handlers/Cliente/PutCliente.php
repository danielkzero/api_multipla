<?php
namespace App\Application\Handlers\Cliente;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutCliente
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $cliente_id = $args['id'];
            $data = $request->getParsedBody();

            // Atualizar informações principais do cliente
            $stmt = $this->pdo->prepare("
                UPDATE cliente
                SET tipo = :tipo, razao_social = :razao_social, nome_fantasia = :nome_fantasia,
                    cnpj = :cnpj, inscricao_estadual = :inscricao_estadual, rua = :rua, numero = :numero,
                    complemento = :complemento, bairro = :bairro, cidade = :cidade, estado = :estado,
                    cep = :cep, suframa = :suframa, observacao = :observacao, ultima_alteracao = NOW(),
                    excluido = :excluido, bloqueado = :bloqueado, motivo_bloqueio_id = :motivo_bloqueio_id
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $cliente_id,
                ':tipo' => $data['tipo'],
                ':razao_social' => $data['razao_social'],
                ':nome_fantasia' => $data['nome_fantasia'],
                ':cnpj' => $data['cnpj'],
                ':inscricao_estadual' => $data['inscricao_estadual'],
                ':rua' => $data['rua'],
                ':numero' => $data['numero'],
                ':complemento' => $data['complemento'],
                ':bairro' => $data['bairro'],
                ':cidade' => $data['cidade'],
                ':estado' => $data['estado'],
                ':cep' => $data['cep'],
                ':suframa' => $data['suframa'],
                ':observacao' => $data['observacao'],
                ':excluido' => $data['excluido'],
                ':bloqueado' => $data['bloqueado'],
                ':motivo_bloqueio_id' => $data['motivo_bloqueio_id']
            ]);

            // Atualizar telefones
            if (!empty($data['telefones'])) {
                foreach ($data['telefones'] as $telefone) {
                    if (isset($telefone['id'])) {
                        // Atualizar telefone existente
                        $stmt = $this->pdo->prepare("
                            UPDATE cliente_telefone
                            SET numero = :numero, tipo = :tipo
                            WHERE id = :id AND cliente_id = :cliente_id
                        ");
                        $stmt->execute([
                            ':id' => $telefone['id'],
                            ':cliente_id' => $cliente_id,
                            ':numero' => $telefone['numero'],
                            ':tipo' => $telefone['tipo']
                        ]);
                    } else {
                        // Inserir novo telefone
                        $stmt = $this->pdo->prepare("
                            INSERT INTO cliente_telefone (cliente_id, numero, tipo)
                            VALUES (:cliente_id, :numero, :tipo)
                        ");
                        $stmt->execute([
                            ':cliente_id' => $cliente_id,
                            ':numero' => $telefone['numero'],
                            ':tipo' => $telefone['tipo']
                        ]);
                    }
                }
            }

            // Atualizar e-mails
            if (!empty($data['emails'])) {
                foreach ($data['emails'] as $email) {
                    if (isset($email['id'])) {
                        // Atualizar e-mail existente
                        $stmt = $this->pdo->prepare("
                            UPDATE cliente_email
                            SET email = :email, tipo = :tipo
                            WHERE id = :id AND cliente_id = :cliente_id
                        ");
                        $stmt->execute([
                            ':id' => $email['id'],
                            ':cliente_id' => $cliente_id,
                            ':email' => $email['email'],
                            ':tipo' => $email['tipo']
                        ]);
                    } else {
                        // Inserir novo e-mail
                        $stmt = $this->pdo->prepare("
                            INSERT INTO cliente_email (cliente_id, email, tipo)
                            VALUES (:cliente_id, :email, :tipo)
                        ");
                        $stmt->execute([
                            ':cliente_id' => $cliente_id,
                            ':email' => $email['email'],
                            ':tipo' => $email['tipo']
                        ]);
                    }
                }
            }

            // Atualizar outros dados, como contatos, endereços, extras, da mesma maneira.

            return $response->withHeader('Content-Type', 'application/json')->withJson(['message' => 'Cliente atualizado com sucesso'], 200);

        } catch (Exception $e) {
            return $response->withStatus(500)->withJson(['error' => $e->getMessage()]);
        }
    }
}
