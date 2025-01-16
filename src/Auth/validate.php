<?php
//Auth/validate.php

use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerInterface;
use App\Application\Settings\SettingsInterface;


function validate_jwt_token($jwt_token, $secret_key) {
    try {
        return JWT::decode($jwt_token, new Key($secret_key, 'HS256'));
    } catch (ExpiredException $e) {
        throw new Exception('Token expirado');
    } catch (SignatureInvalidException $e) {
        throw new Exception('Assinatura de token inválida');
    } catch (BeforeValidException $e) {
        throw new Exception('Token ainda não é válido');
    } catch (Exception $e) {
        throw new Exception('Token inválido');
    }
}

function ValidarToken($request) {
    global $container; // ou alguma outra forma de acessar o $container

    if (!$request->hasHeader('Authorization')) {
        throw new Exception('Acesso não autorizado!', 401);
    }
    $token = $request->getHeader('Authorization')[0];
    
    $settings = $container->get(SettingsInterface::class);    
    $secret_key = $settings->get('secret_key');

    if (empty($token)) {
        throw new Exception('Token não fornecido', 401);
    }

    $jwt_token = str_replace('Bearer ', '', $token);    

    try {
        $decoded_payload = validate_jwt_token($jwt_token, $secret_key);
        return $decoded_payload->sub;
    } catch (Exception $e) {
        throw new Exception('Token inválido', 401);
    }
}