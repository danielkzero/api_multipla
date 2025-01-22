<?php
//routes.php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Settings\SettingsInterface;
use App\Application\Handlers\Cliente;
use App\Application\Handlers\ClienteContato;
use App\Application\Handlers\ClienteContatoEmail;
use App\Application\Handlers\ClienteContatoTelefone;
use App\Application\Handlers\ClienteEmail;
use App\Application\Handlers\ClienteEndereco;
use App\Application\Handlers\ClienteExtra;
use App\Application\Handlers\ClienteTelefone;
use App\Application\Handlers\CondicaoPagamento;
use App\Application\Handlers\FormaPagamento;
use App\Application\Handlers\ICMS_ST;
use App\Application\Handlers\Pedido;
use App\Application\Handlers\Produto;
use App\Application\Handlers\ProdutoCategoria;
use App\Application\Handlers\ProdutoImagem;
use App\Application\Handlers\TabelaPreco;
use App\Application\Handlers\TabelaProdutoPreco;
use App\Application\Handlers\TabelaPrecoCidade;
use App\Application\Handlers\Usuario;
use App\Application\Handlers\Importar;
use App\Application\Handlers\PedidoStatus;
use App\Application\Handlers\Equipe;
use App\Application\Handlers\Empresa;
use App\Application\Handlers\ConfiguracaoSistema;
use App\Application\Handlers\Relatorio;

use Psr\Container\ContainerInterface;
use Slim\Exception\HttpUnauthorizedException;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../src/Auth/auth.php';
require_once __DIR__ . '/../src/Auth/validate.php';

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$validarTokenMiddleware = function (Request $request, $handler) {
    try {
        ValidarToken($request, $this->get(ContainerInterface::class));
    } catch (Exception $e) {
        throw new HttpUnauthorizedException($request, $e->getMessage());
    }
    return $handler->handle($request);
};


return function (App $app) use ($validarTokenMiddleware) {
    // boas vindas ao sistema
    $app->get('/', function (Request $request, Response $response) {
        $response
            ->getBody()
            ->write('
            <div class="versao-display">
                <strong>API GESTOR MULTIPLA • v.1.0.0</strong>
            </div>
            <style>
                body, html {
                    margin: 0px; 
                    padding: 0px;
                    font-family: arial;
                }
                .versao-display {
                    display: flex; 
                    justify-content: center; 
                    align-items: center; 
                    height: 100vh; 
                    width: 100%;
                }
            </style>');
        return $response;
    });


    $app->post('/login', function (Request $request, Response $response) {
        $data = $request->getParsedBody();

        global $container;

        $settings = $container->get(SettingsInterface::class);

        $secret_key = $settings->get('secret_key');

        $token = authenticateUser($this->get(PDO::class), $data['usuario'], md5($data['senha']), $secret_key);

        if ($token) {
            return $response->withJson(['success' => true, 'token' => $token, 'usuario' => $data['usuario']]);
        } else {
            return $response->withStatus(401)->withJson(['error' => 'Credenciais inválidas']);
        }
    });

    $app->group('/pedidos', function ($app) {
        $app->post('', Pedido\PostPedido::class);
        $app->get('', Pedido\GetPedido::class);
        $app->get('/{id}', Pedido\GetPedidoById::class);
        $app->post('/complementos', Pedido\PostPedidoComplemento::class);
        $app->get('/complementos/{id}', Pedido\GetPedidoComplementoById::class);
        $app->delete('/complementos/{id}', Pedido\PutPedidoComplementoById::class);
    });

    $app->group('/empresa', function ($app) {
        $app->get('/{id}', Empresa\GetEmpresaById::class);
        $app->get('', Empresa\GetEmpresa::class);
        $app->post('', Empresa\PostEmpresa::class);
        $app->put('/{id}', Empresa\PutEmpresa::class);
        $app->delete('/{id}', Empresa\DeleteEmpresa::class);
    })->add($validarTokenMiddleware);

    $app->group('/usuario', function ($app) use ($validarTokenMiddleware){
        $app->get('', Usuario\GetUsuario::class);
        $app->get('/{id}', Usuario\GetUsuarioId::class);
        $app->post('', Usuario\PostUsuario::class)->add($validarTokenMiddleware);
        $app->put('/{id}', Usuario\PutUsuarioId::class)->add($validarTokenMiddleware);
        $app->delete('/{id}', Usuario\DeleteUsuarioId::class)->add($validarTokenMiddleware);
    });

    $app->group('/profile', function ($app) {
        $app->get('', Usuario\GetUsuarioByEmail::class);
    });


    $app->get('/statustoken', function (Request $request, Response $response) {
        try {
            require_once __DIR__ . '/../src/Auth/validate.php';
            ValidarToken($request);
            return $response->withHeader('Content-Type', 'application/json')->withJson(['token' => true]);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    });

    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });
};
