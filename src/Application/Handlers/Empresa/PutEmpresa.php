<?php
namespace App\Application\Handlers\Empresa;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutEmpresa
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $empresa_id = $args['id']; // ID da empresa recebido nos parâmetros da URL
        $this->pdo->beginTransaction();

        try {
            // Dados recebidos
            $data = $request->getParsedBody();

            // Atualizando empresa
            $stmt = $this->pdo->prepare("
                UPDATE empresa 
                SET cnpj = :cnpj, 
                    razao_social = :razao_social, 
                    nome_fantasia = :nome_fantasia, 
                    comissao_sobre_ipi = :comissao_sobre_ipi, 
                    comissao_sobre_st = :comissao_sobre_st, 
                    comissao_parcelada = :comissao_parcelada, 
                    comissao_parcela_unica = :comissao_parcela_unica, 
                    controlar_estoque = :controlar_estoque, 
                    bloquear_venda_sem_estoque = :bloquear_venda_sem_estoque, 
                    informacoes_adicionais = :informacoes_adicionais
                WHERE id = :empresa_id
            ");
            
            $stmt->execute([
                ':empresa_id' => $empresa_id,
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

            // Atualizando telefones
            foreach ($data['telefones'] as $telefone) {
                if (isset($telefone['id'])) {
                    // Atualiza telefone existente
                    $stmt = $this->pdo->prepare("
                        UPDATE empresa_telefone 
                        SET numero = :numero
                        WHERE id = :id AND empresa_id = :empresa_id
                    ");
                    $stmt->execute([
                        ':id' => $telefone['id'],
                        ':empresa_id' => $empresa_id,
                        ':numero' => $telefone['numero']
                    ]);
                } else {
                    // Insere novo telefone
                    $stmt = $this->pdo->prepare("
                        INSERT INTO empresa_telefone (empresa_id, numero)
                        VALUES (:empresa_id, :numero)
                    ");
                    $stmt->execute([
                        ':empresa_id' => $empresa_id,
                        ':numero' => $telefone['numero']
                    ]);
                }
            }

            // Atualizando emails
            foreach ($data['emails'] as $email) {
                if (isset($email['id'])) {
                    // Atualiza email existente
                    $stmt = $this->pdo->prepare("
                        UPDATE empresa_email 
                        SET email = :email
                        WHERE id = :id AND empresa_id = :empresa_id
                    ");
                    $stmt->execute([
                        ':id' => $email['id'],
                        ':empresa_id' => $empresa_id,
                        ':email' => $email['email']
                    ]);
                } else {
                    // Insere novo email
                    $stmt = $this->pdo->prepare("
                        INSERT INTO empresa_email (empresa_id, email)
                        VALUES (:empresa_id, :email)
                    ");
                    $stmt->execute([
                        ':empresa_id' => $empresa_id,
                        ':email' => $email['email']
                    ]);
                }
            }

            /*
            // Atualizando endereços adicionais
            foreach ($data['enderecos_adicionais'] as $endereco) {
                if (isset($endereco['id'])) {
                    // Atualiza endereço existente
                    $stmt = $this->pdo->prepare("
                        UPDATE empresa_endereco 
                        SET endereco = :endereco, numero = :numero, complemento = :complemento, 
                            bairro = :bairro, cidade = :cidade, estado = :estado, cep = :cep, 
                            ultima_alteracao = NOW()
                        WHERE id = :id AND empresa_id = :empresa_id
                    ");
                    $stmt->execute([
                        ':id' => $endereco['id'],
                        ':empresa_id' => $empresa_id,
                        ':endereco' => $endereco['endereco'],
                        ':numero' => $endereco['numero'],
                        ':complemento' => $endereco['complemento'],
                        ':bairro' => $endereco['bairro'],
                        ':cidade' => $endereco['cidade'],
                        ':estado' => $endereco['estado'],
                        ':cep' => $endereco['cep']
                    ]);
                } else {
                    // Insere novo endereço
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
                }
            }*/

            // Atualizando contatos
            foreach ($data['contatos'] as $contato) {
                if (isset($contato['id'])) {
                    // Atualiza contato existente
                    $stmt = $this->pdo->prepare("
                        UPDATE empresa_contato 
                        SET nome = :nome, cargo = :cargo, excluido = :excluido 
                        WHERE id = :id AND empresa_id = :empresa_id
                    ");
                    $stmt->execute([
                        ':id' => $contato['id'],
                        ':empresa_id' => $empresa_id,
                        ':nome' => $contato['nome'],
                        ':cargo' => $contato['cargo'],
                        ':excluido' => $contato['excluido'] ?? 0
                    ]);

                    $contato_id = $contato['id'];

                    // Atualizando telefones do contato
                    foreach ($contato['telefones'] as $telefone) {
                        if (isset($telefone['id'])) {
                            // Atualiza telefone existente do contato
                            $stmt = $this->pdo->prepare("
                                UPDATE empresa_contato_telefone 
                                SET numero = :numero 
                                WHERE id = :id AND contato_id = :contato_id
                            ");
                            $stmt->execute([
                                ':id' => $telefone['id'],
                                ':contato_id' => $contato_id,
                                ':numero' => $telefone['numero']
                            ]);
                        } else {
                            // Insere novo telefone do contato
                            $stmt = $this->pdo->prepare("
                                INSERT INTO empresa_contato_telefone (contato_id, numero)
                                VALUES (:contato_id, :numero)
                            ");
                            $stmt->execute([
                                ':contato_id' => $contato_id,
                                ':numero' => $telefone['numero']
                            ]);
                        }
                    }

                    // Atualizando emails do contato
                    foreach ($contato['emails'] as $email) {
                        if (isset($email['id'])) {
                            // Atualiza email existente do contato
                            $stmt = $this->pdo->prepare("
                                UPDATE empresa_contato_email 
                                SET email = :email 
                                WHERE id = :id AND contato_id = :contato_id
                            ");
                            $stmt->execute([
                                ':id' => $email['id'],
                                ':contato_id' => $contato_id,
                                ':email' => $email['email']
                            ]);
                        } else {
                            // Insere novo email do contato
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
                } else {
                    // Insere novo contato
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

                    // Insere telefones do contato
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

                    // Insere emails do contato
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
            }

            $this->pdo->commit();
            return $response->withStatus(200)->write(json_encode(['status' => 'success']));
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $response->withStatus(500)->write(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }
}
