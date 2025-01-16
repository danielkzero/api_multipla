<?php
namespace App\Application\Handlers\Empresa;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetEmpresaById
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $empresa_id = $args['id'];

            // Buscar os dados principais do empresa
            $stmt = $this->pdo->prepare("
                SELECT * 
                FROM empresa
                WHERE id = :id ORDER BY id DESC
            ");
            $stmt->execute([':id' => $empresa_id]);
            $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$empresa) {
                return $response->withStatus(404)->withJson(['error' => 'empresa não encontrado']);
            }

            // Buscar telefones do empresa
            $stmt = $this->pdo->prepare("
                SELECT id, numero, tipo
                FROM empresa_telefone
                WHERE empresa_id = :empresa_id
            ");
            $stmt->execute([':empresa_id' => $empresa_id]);
            $telefones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Buscar emails do empresa
            $stmt = $this->pdo->prepare("
                SELECT id, email, tipo
                FROM empresa_email
                WHERE empresa_id = :empresa_id
            ");
            $stmt->execute([':empresa_id' => $empresa_id]);
            $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Buscar endereços do empresa
            $stmt = $this->pdo->prepare("
                SELECT id, endereco, numero, complemento, bairro, cidade, estado, cep, ultima_alteracao
                FROM empresa_endereco
                WHERE empresa_id = :empresa_id
            ");
            $stmt->execute([':empresa_id' => $empresa_id]);
            $enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Buscar contatos do empresa
            $stmt = $this->pdo->prepare("
                SELECT id, nome, cargo, excluido
                FROM empresa_contato
                WHERE empresa_id = :empresa_id
            ");
            $stmt->execute([':empresa_id' => $empresa_id]);
            $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Para cada contato, buscar os telefones e emails associados
            foreach ($contatos as &$contato) {
                // Buscar telefones do contato
                $stmt = $this->pdo->prepare("
                    SELECT id, numero, tipo
                    FROM empresa_contato_telefone
                    WHERE contato_id = :contato_id
                ");
                $stmt->execute([':contato_id' => $contato['id']]);
                $contato['telefones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Buscar emails do contato
                $stmt = $this->pdo->prepare("
                    SELECT id, email, tipo
                    FROM empresa_contato_email
                    WHERE contato_id = :contato_id
                ");
                $stmt->execute([':contato_id' => $contato['id']]);
                $contato['emails'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Buscar dados extras do empresa
            $stmt = $this->pdo->prepare("
                SELECT id, campo_extra_id, nome, valor_texto, valor_data, nome_arquivo, valor_arquivo, valor_decimal
                FROM empresa_extra
                WHERE empresa_id = :empresa_id
            ");
            $stmt->execute([':empresa_id' => $empresa_id]);
            $extras = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Consolidar todos os dados em uma resposta JSON
            $responseData = [
                'empresa' => $empresa,
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
