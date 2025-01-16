<?php
require __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Carregar configurações e dependências do Slim
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

$container = $app->getContainer();

// Obter a conexão PDO a partir do contêiner
$pdo = $container->get('pdo');

class PaymentChecker {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function getMercadoPagoAccessToken() {
        $stmt = $this->pdo->prepare("SELECT meta_value FROM informacoes_sistema WHERE meta_field = 'mercadopago_access_token'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return $result['meta_value'];

        } else {
            throw new Exception("MercadoPago access token not found");
        }
    }

    public function checkPixPayments() {
        try {
            // Configurar e usar a API do Mercado Pago
            $accessToken = $this->getMercadoPagoAccessToken();
            MercadoPago\SDK::setAccessToken($accessToken);

            // Buscar pagamentos pendentes no banco de dados
            $stmt = $this->pdo->query("SELECT id, referencia_pagamento FROM pagamentos WHERE status = 'pending'");
            $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($pagamentos as $pagamento) {
                $referencia = json_decode($pagamento['referencia_pagamento'], true);
                $mercadoPagoId = $referencia['mercado_pago_id'];

                // Verificar o status do pagamento no Mercado Pago
                $payment = MercadoPago\Payment::find_by_id($mercadoPagoId);
                if ($payment) {
                    $status = $payment->status;
                    // Atualizar o status do pagamento no banco de dados
                    $stmt = $this->pdo->prepare("UPDATE pagamentos SET status = :status WHERE id = :id");
                    $stmt->execute(['status' => $status, 'id' => $pagamento['id']]);
                }
            }
        } catch (Exception $e) {
            echo "Erro ao verificar pagamentos: " . $e->getMessage();
        }
    }
}

// Instanciar e executar a verificação de pagamentos
$paymentChecker = new PaymentChecker($pdo);
$paymentChecker->checkPixPayments();

echo "Verificação de pagamentos concluída.\n";
