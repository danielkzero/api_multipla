<?php
namespace App\Application\Handlers\Empresa;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetEmpresa
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            // Pegar parâmetros de paginação
            $queryParams = $request->getQueryParams();
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 1; // Default: 100
            $offset = isset($queryParams['offset']) ? (int)$queryParams['offset'] : 0; // Default: 0
            $busca = isset($queryParams['busca']) ? trim($queryParams['busca']) : ''; // Parâmetro de busca

            // Certificar que o limit não é maior que 100
            if ($limit > 100) {
                $limit = 100;
            }

            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM empresa");
            $stmt->execute();
            $totalRegistros = $stmt->fetchColumn();

            // Construir a query de busca para os dados principais dos empresas
            $sql = "
                SELECT * 
                FROM empresa
                WHERE id = id
            ";

            // Se houver um termo de busca, adicionar filtros
            if (!empty($busca)) {
                $sql .= " AND (razao_social LIKE :busca OR nome_fantasia LIKE :busca OR cnpj LIKE :busca OR cidade LIKE :busca)";
            }

            // Adicionar limit e offset
            $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

            // Preparar e executar a query
            $stmt = $this->pdo->prepare($sql);

            // Se houver um termo de busca, passar o valor do parâmetro
            if (!empty($busca)) {
                $buscaParam = "%$busca%";
                $stmt->bindParam(':busca', $buscaParam, PDO::PARAM_STR);
            }
            
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$empresas) {
                return $response->withStatus(404)->withJson(['error' => 'Nenhum empresa encontrado']);
            }

            // Iterar sobre os empresas para buscar telefones, emails, endereços e contatos associados
            foreach ($empresas as &$empresa) {
                // Buscar telefones do empresa
                $stmt = $this->pdo->prepare("
                    SELECT id, numero
                    FROM empresa_telefone
                    WHERE empresa_id = :empresa_id
                ");
                $stmt->execute([':empresa_id' => $empresa['id']]);
                $empresa['telefones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Buscar emails do empresa
                $stmt = $this->pdo->prepare("
                    SELECT id, email
                    FROM empresa_email
                    WHERE empresa_id = :empresa_id
                ");
                $stmt->execute([':empresa_id' => $empresa['id']]);
                $empresa['emails'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Buscar endereços do empresa
                $stmt = $this->pdo->prepare("
                    SELECT id, endereco, numero, complemento, bairro, cidade, estado, cep, ultima_alteracao
                    FROM empresa_endereco
                    WHERE empresa_id = :empresa_id
                ");
                $stmt->execute([':empresa_id' => $empresa['id']]);
                $empresa['enderecos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Buscar contatos do empresa
                $stmt = $this->pdo->prepare("
                    SELECT id, nome, cargo, excluido
                    FROM empresa_contato
                    WHERE empresa_id = :empresa_id
                ");
                $stmt->execute([':empresa_id' => $empresa['id']]);
                $empresa['contatos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Para cada contato, buscar os telefones e emails associados
                foreach ($empresa['contatos'] as &$contato) {
                    // Buscar telefones do contato
                    $stmt = $this->pdo->prepare("
                        SELECT id, numero
                        FROM empresa_contato_telefone
                        WHERE contato_id = :contato_id
                    ");
                    $stmt->execute([':contato_id' => $contato['id']]);
                    $contato['telefones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Buscar emails do contato
                    $stmt = $this->pdo->prepare("
                        SELECT id, email
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
                $stmt->execute([':empresa_id' => $empresa['id']]);
                $empresa['extras'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $resultado = [
                'data' => $empresas,
                'total' => (int)$totalRegistros
            ];

            // Retornar os dados dos empresas em formato JSON
            return $response->withHeader('Content-Type', 'application/json')->withJson($resultado, 200);

        } catch (Exception $e) {
            return $response->withStatus(500)->withJson(['error' => $e->getMessage()]);
        }
    }
}
