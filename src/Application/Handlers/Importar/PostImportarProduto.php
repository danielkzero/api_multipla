<?php

namespace App\Application\Handlers\Importar;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PostImportarProduto
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $uploadedFiles = $request->getUploadedFiles();
            if (!isset($uploadedFiles['files'])) {
                throw new Exception("Nenhum arquivo enviado.", 400);
            }

            $arquivos = $uploadedFiles['files'];

            foreach ($arquivos as $uploadedFile) {
                if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                    throw new Exception("Erro ao carregar o arquivo.", 400);
                }

                $filePath = $uploadedFile->getStream()->getMetadata('uri');
                $spreadsheet = IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();

                // Captura os cabeçalhos das colunas
                $headerRow = $worksheet->getRowIterator(1, 1)->current();
                $headerCells = $headerRow->getCellIterator();
                $headerCells->setIterateOnlyExistingCells(false);

                $headers = [];
                foreach ($headerCells as $cell) {
                    $headers[] = $cell->getValue();
                }

                // Iterar pelas linhas da planilha (começando da segunda)
                foreach ($worksheet->getRowIterator(2) as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    $rowData = [];
                    foreach ($cellIterator as $cell) {
                        $rowData[] = $cell->getCalculatedValue();
                    }

                    return $response->withHeader('Content-Type', 'application/json')->withJson(
                        [
                            'linhas' => $rowData,
                            'cabeçalhos' => $headers
                        ], 201);

                    // Associar cabeçalhos às células correspondentes
                    $data = array_combine($headers, $rowData);

                    // Processar colunas de preços (nomes dinâmicos)
                    foreach ($headers as $header) {
                        if (stripos($header, 'Preço de Tabela') !== false) {
                            $nomeTabela = $header;

                            $stmt = $this->pdo->prepare("
                                INSERT INTO tabela_preco (nome)
                                SELECT :nome
                                WHERE NOT EXISTS (SELECT 1 FROM tabela_preco WHERE nome = :nome)
                            ");
                            $stmt->bindParam(':nome', $nomeTabela);
                            $stmt->execute();
                        }
                    }

                    // Processar categorias
                    $categorias = [
                        $data['categoria_principal'] ?? null,
                        $data['subcategoria_nivel2'] ?? null,
                        $data['subcategoria_nivel3'] ?? null,
                    ];

                    foreach ($categorias as $nomeCategoria) {
                        if ($nomeCategoria !== null) {
                            $stmt = $this->pdo->prepare("
                                INSERT INTO produto_categoria (nome)
                                SELECT :nome
                                WHERE NOT EXISTS (SELECT 1 FROM produto_categoria WHERE nome = :nome)
                            ");
                            $stmt->bindParam(':nome', $nomeCategoria);
                            $stmt->execute();
                        }
                    }

                    // Inserir produto
                    $codigo = $data['codigo'] ?? null;
                    $nomeProduto = $data['nome'] ?? null;

                    return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => $codigo], 201);

                    if ($codigo && $nomeProduto) {
                        $stmt = $this->pdo->prepare("
                            INSERT INTO produto (codigo, nome)
                            VALUES (:codigo, :nome)
                            ON DUPLICATE KEY UPDATE nome = :nome
                        ");
                        $stmt->bindParam(':codigo', $codigo);
                        $stmt->bindParam(':nome', $nomeProduto);
                        $stmt->execute();
                    }
                }
            }

            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success'], 201);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode() ?: 500)
                ->withHeader('Content-Type', 'application/json')
                ->withJson(['error' => $e->getMessage()]);
        }
    }
}
