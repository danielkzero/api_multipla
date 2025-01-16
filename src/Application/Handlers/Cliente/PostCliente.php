<?php
namespace App\Application\Handlers\Cliente;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostCliente
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $this->pdo->beginTransaction();

        try {
            // Dados recebidos
            $data = $request->getParsedBody();

            // Inserindo cliente
            $stmt = $this->pdo->prepare("
                INSERT INTO cliente (tipo, razao_social, nome_fantasia, cnpj, inscricao_estadual, rua, numero, complemento, bairro, cidade, estado, cep, suframa, observacao, ultima_alteracao, excluido, bloqueado, motivo_bloqueio_id)
                VALUES (:tipo, :razao_social, :nome_fantasia, :cnpj, :inscricao_estadual, :rua, :numero, :complemento, :bairro, :cidade, :estado, :cep, :suframa, :observacao, NOW(), :excluido, :bloqueado, :motivo_bloqueio_id)
            ");
            
            $stmt->execute([
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
            
            $cliente_id = $this->pdo->lastInsertId();

            // Inserindo telefones
            foreach ($data['telefones'] as $telefone) {
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

            // Inserindo emails
            foreach ($data['emails'] as $email) {
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

            // Inserindo endereços adicionais
            foreach ($data['enderecos_adicionais'] as $endereco) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO cliente_endereco (cliente_id, endereco, numero, complemento, bairro, cidade, estado, cep, ultima_alteracao)
                    VALUES (:cliente_id, :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep, NOW())
                ");
                $stmt->execute([
                    ':cliente_id' => $cliente_id,
                    ':endereco' => $endereco['endereco'],
                    ':numero' => $endereco['numero'],
                    ':complemento' => $endereco['complemento'],
                    ':bairro' => $endereco['bairro'],
                    ':cidade' => $endereco['cidade'],
                    ':estado' => $endereco['estado'],
                    ':cep' => $endereco['cep']
                ]);
            }

            // Inserindo contatos
            foreach ($data['contatos'] as $contato) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO cliente_contato (cliente_id, nome, cargo, excluido)
                    VALUES (:cliente_id, :nome, :cargo, :excluido)
                ");
                $stmt->execute([
                    ':cliente_id' => $cliente_id,
                    ':nome' => $contato['nome'],
                    ':cargo' => $contato['cargo'],
                    ':excluido' => $contato['excluido']
                ]);

                $contato_id = $this->pdo->lastInsertId();

                // Inserindo telefones do contato
                foreach ($contato['telefones'] as $telefone) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO cliente_contato_telefone (contato_id, numero, tipo)
                        VALUES (:contato_id, :numero, :tipo)
                    ");
                    $stmt->execute([
                        ':contato_id' => $contato_id,
                        ':numero' => $telefone['numero'],
                        ':tipo' => $telefone['tipo']
                    ]);
                }

                // Inserindo emails do contato
                foreach ($contato['emails'] as $email) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO cliente_contato_email (contato_id, email, tipo)
                        VALUES (:contato_id, :email, :tipo)
                    ");
                    $stmt->execute([
                        ':contato_id' => $contato_id,
                        ':email' => $email['email'],
                        ':tipo' => $email['tipo']
                    ]);
                }
            }

            $this->pdo->commit();

            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success', 'cliente_id' => $cliente_id], 201);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $response->withStatus(500)->withJson(['error' => $e->getMessage()]);
        }
    }
}
