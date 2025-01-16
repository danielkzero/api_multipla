<?php
namespace App\Application\Handlers\Empresa;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostEmpresa
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

            // Inserindo empresa
            $stmt = $this->pdo->prepare("
                INSERT INTO empresa (cnpj, razao_social, nome_fantasia, comissao_sobre_ipi, comissao_sobre_st, comissao_parcelada, comissao_parcela_unica, controlar_estoque, bloquear_venda_sem_estoque, informacoes_adicionais)
                VALUES (:cnpj, :razao_social, :nome_fantasia, :comissao_sobre_ipi, :comissao_sobre_st, :comissao_parcelada, :comissao_parcela_unica, :controlar_estoque, :bloquear_venda_sem_estoque, :informacoes_adicionais)
            ");

            $stmt->execute([
                ':cnpj' => $data['cnpj'] ?? '',
                ':razao_social' => $data['razao_social'] ?? '',
                ':nome_fantasia' => $data['nome_fantasia'] ?? '',
                ':comissao_sobre_ipi' => $data['comissao_sobre_ipi'] === false ? 0 : 1,
                ':comissao_sobre_st' => $data['comissao_sobre_st'] === false ? 0 : 1,
                ':comissao_parcelada' => $data['comissao_parcelada'] === false ? 0 : 1,
                ':comissao_parcela_unica' => $data['comissao_parcela_unica'] === false ? 0 : 1,
                ':controlar_estoque' => $data['controlar_estoque'] === false ? 0 : 1,
                ':bloquear_venda_sem_estoque' => $data['bloquear_venda_sem_estoque'] === false ? 0 : 1,
                ':informacoes_adicionais' => $data['informacoes_adicionais'] ?? ''
            ]);
            
            $empresa_id = $this->pdo->lastInsertId();

            // Inserindo telefones
            foreach ($data['telefones'] as $telefone) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO empresa_telefone (empresa_id, numero)
                    VALUES (:empresa_id, :numero)
                ");
                $stmt->execute([
                    ':empresa_id' => $empresa_id,
                    ':numero' => $telefone['numero']
                ]);
            }

            // Inserindo emails
            foreach ($data['emails'] as $email) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO empresa_email (empresa_id, email)
                    VALUES (:empresa_id, :email)
                ");
                $stmt->execute([
                    ':empresa_id' => $empresa_id,
                    ':email' => $email['email']
                ]);
            }

            // Inserindo endereços adicionais
            /*foreach ($data['enderecos_adicionais'] as $endereco) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO empresa_endereco (empresa_id, endereco, numero, complemento, bairro, cidade, estado, cep, ultima_alteracao)
                    VALUES (:empresa_id, :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep, NOW())
                ");
                $stmt->execute([
                    ':empresa_id' => $empresa_id,
                    ':endereco' => $endereco['endereco'],
                    ':numero' => $endereco['numero'],
                    ':complemento' => $endereco['complemento'],
                    ':bairro' => $endereco['bairro'],
                    ':cidade' => $endereco['cidade'],
                    ':estado' => $endereco['estado'],
                    ':cep' => $endereco['cep']
                ]);
            }*/

            // Inserindo contatos
            foreach ($data['contatos'] as $contato) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO empresa_contato (empresa_id, nome, cargo, excluido)
                    VALUES (:empresa_id, :nome, :cargo, :excluido)
                ");
                $stmt->execute([
                    ':empresa_id' => $empresa_id,
                    ':nome' => $contato['nome'],
                    ':cargo' => $contato['cargo'],
                    ':excluido' => $contato['excluido'] ?? 0
                ]);

                $contato_id = $this->pdo->lastInsertId();

                // Inserindo telefones do contato
                foreach ($contato['telefones'] as $telefone) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO empresa_contato_telefone (contato_id, numero)
                        VALUES (:contato_id, :numero)
                    ");
                    $stmt->execute([
                        ':contato_id' => $contato_id,
                        ':numero' => $telefone['numero']
                    ]);
                }

                // Inserindo emails do contato
                foreach ($contato['emails'] as $email) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO empresa_contato_email (contato_id, email)
                        VALUES (:contato_id, :email)
                    ");
                    $stmt->execute([
                        ':contato_id' => $contato_id,
                        ':email' => $email['email']
                    ]);
                }
            }

            $this->pdo->commit();

            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success', 'empresa_id' => $empresa_id], 201);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $response->withStatus(500)->withJson(['error' => $e->getMessage()]);
        }
    }
}
