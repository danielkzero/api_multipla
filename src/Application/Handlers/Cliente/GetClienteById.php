<?php
namespace App\Application\Handlers\Cliente;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetClienteById
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

            // Buscar os dados principais do cliente
            $stmt = $this->pdo->prepare("
                SELECT id, tipo, razao_social, nome_fantasia, cnpj, inscricao_estadual, rua, numero, complemento, bairro, cidade, estado, cep, suframa, observacao, ultima_alteracao, excluido, bloqueado 
                FROM cliente
                WHERE id = :id ORDER BY id DESC
            ");
            $stmt->execute([':id' => $cliente_id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cliente) {
                return $response->withStatus(404)->withJson(['error' => 'Cliente não encontrado']);
            }

            // Buscar telefones do cliente
            $stmt = $this->pdo->prepare("
                SELECT id, numero, tipo
                FROM cliente_telefone
                WHERE cliente_id = :cliente_id
            ");
            $stmt->execute([':cliente_id' => $cliente_id]);
            $telefones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Buscar emails do cliente
            $stmt = $this->pdo->prepare("
                SELECT id, email, tipo
                FROM cliente_email
                WHERE cliente_id = :cliente_id
            ");
            $stmt->execute([':cliente_id' => $cliente_id]);
            $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Buscar endereços do cliente
            $stmt = $this->pdo->prepare("
                SELECT id, endereco, numero, complemento, bairro, cidade, estado, cep, ultima_alteracao
                FROM cliente_endereco
                WHERE cliente_id = :cliente_id
            ");
            $stmt->execute([':cliente_id' => $cliente_id]);
            $enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Buscar contatos do cliente
            $stmt = $this->pdo->prepare("
                SELECT id, nome, cargo, excluido
                FROM cliente_contato
                WHERE cliente_id = :cliente_id
            ");
            $stmt->execute([':cliente_id' => $cliente_id]);
            $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Para cada contato, buscar os telefones e emails associados
            foreach ($contatos as &$contato) {
                // Buscar telefones do contato
                $stmt = $this->pdo->prepare("
                    SELECT id, numero, tipo
                    FROM cliente_contato_telefone
                    WHERE contato_id = :contato_id
                ");
                $stmt->execute([':contato_id' => $contato['id']]);
                $contato['telefones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Buscar emails do contato
                $stmt = $this->pdo->prepare("
                    SELECT id, email, tipo
                    FROM cliente_contato_email
                    WHERE contato_id = :contato_id
                ");
                $stmt->execute([':contato_id' => $contato['id']]);
                $contato['emails'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Buscar dados extras do cliente
            $stmt = $this->pdo->prepare("
                SELECT id, campo_extra_id, nome, valor_texto, valor_data, nome_arquivo, valor_arquivo, valor_decimal
                FROM cliente_extra
                WHERE cliente_id = :cliente_id
            ");
            $stmt->execute([':cliente_id' => $cliente_id]);
            $extras = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Consolidar todos os dados em uma resposta JSON
            $responseData = [
                'cliente' => $cliente,
                'telefones' => $telefones,
                'emails' => $emails,
                'enderecos' => $enderecos,
                'contatos' => $contatos,
                'extras' => $extras,
            ];

            return $response->withHeader('Content-Type', 'application/json')->withJson($responseData, 200);

        } catch (Exception $e) {
            return $response->withStatus(500)->withJson(['error' => $e->getMessage()]);
        }
    }
}
