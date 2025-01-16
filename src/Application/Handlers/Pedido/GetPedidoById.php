<?php

namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetPedidoById
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];

        $stmt = $this->pdo->prepare("
            SELECT p.*, i.*, prd.multiplo, prd.id produto_id_1, prd.peso_bruto, 
            (SELECT imagem_base64 FROM produto_imagem pri WHERE pri.produto_id = prd.id LIMIT 1) as imagem_base64, 
            sts.descricao sts_descricao, p.status,
            hex_rgb
            FROM pedido p
            LEFT JOIN pedido_item i ON p.id = i.pedido_id
            LEFT JOIN pedido_status sts ON sts.status = p.status 
            LEFT JOIN produto prd ON prd.codigo = i.codigo AND prd.excluido = 0 
            WHERE p.id = :id AND p.excluido = 0 ORDER BY p.id DESC
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$pedidos) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Pedido não encontrado']);
        }



        $pedido = $pedidos[0];
        $resultado = [
            'id' => $id,
            'cliente' => $pedido['cliente'] == null ? [] : json_decode($pedido['cliente']),
            'cliente_id' => (int)$pedido['cliente_id'],
            'condicao_pagamento' => $pedido['condicao_pagamento'] == null ? [] : json_decode($pedido['condicao_pagamento']),
            'contato_cliente' => $pedido['contato_cliente'],
            'criador_id' => (int)$pedido['criador_id'],
            'data_emissao' => $pedido['data_emissao'],
            'enderecos' => $pedido['enderecos'] == null ? [] : json_decode($pedido['enderecos']),
            'excluido' => (int)$pedido['excluido'],
            'status' => (string)$pedido['status'],
            'id_tabela_preco' => (int)$pedido['id_tabela_preco'],
            'observacoes' => (string)$pedido['observacoes'],
            'representada' => $pedido['representada'] == null ? [] : json_decode($pedido['representada']),
            'status_faturamento' => (string)$pedido['status_faturamento'],
            'tabela_preco' => (string)$pedido['tabela_preco'],
            'tipo_pedido' => (string)$pedido['tipo_pedido'],
            'total' => (float)$pedido['total'],
            'vendedor' => $pedido['vendedor'] == null ? [] : json_decode($pedido['vendedor']),
            'sts_descricao' => $pedido['sts_descricao'] ?? null,
            'hex_rgb' => $pedido['hex_rgb'] ?? null,
            'itens' => []
        ];

        foreach ($pedidos as $pedido) {
            if ((string)$pedido['codigo'] !== '') {
                $resultado['itens'][] = [
                    'codigo' => (string)$pedido['codigo'],
                    'comissao' => (float)$pedido['comissao'],
                    'excluido' => (int)$pedido['excluido'],
                    'ipi' => (float)$pedido['ipi'],
                    'item_acrescimo' => ($pedido['item_acrescimo'] !== null && $pedido['item_acrescimo'] !== '') ? json_decode($pedido['item_acrescimo']) : '',
                    'item_desconto' => ($pedido['item_desconto'] !== null && $pedido['item_desconto'] !== '') ? json_decode($pedido['item_desconto']) : '',
                    'nome' => (string)$pedido['nome'],
                    'observacoes' => (string)$pedido['observacoes'],
                    'pedido_id' => (int)$pedido['pedido_id'],
                    'preco_liquido' => (float)$pedido['preco_liquido'],
                    'preco_minimo' => (float)$pedido['preco_minimo'],
                    'preco_tabela' => (float)$pedido['preco_tabela'],
                    'produto_id' => (int)$pedido['produto_id_1'],
                    'qtd_unitaria' => (int)$pedido['qtd_unitaria'],
                    'multiplo' => (int)$pedido['multiplo'],
                    'quantidade' => (int)$pedido['quantidade'],
                    'subtotal' => (float)$pedido['subtotal'],
                    'icms_destino' => (string)$pedido['st'],
                    'peso_bruto' => (string)$pedido['peso_bruto'],
                    'imagem_base64' => (string)$pedido['imagem_base64'] ?? null,
                ];
            }
        }

        return $response->withHeader('Content-Type', 'application/json')->withJson($resultado);
    }
}
