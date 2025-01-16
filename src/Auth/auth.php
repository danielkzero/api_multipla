<?php
//Auth/auth.php
use Firebase\JWT\JWT;

function generate_jwt_token($user_id, $secret_key) {
    $issued_at = time();
    $expiration_time = $issued_at + (60 * 60 * 8); 

    $payload = array(
        'iat' => $issued_at,
        'exp' => $expiration_time,
        'sub' => $user_id
    );

    return JWT::encode($payload, $secret_key, 'HS256');
}

function authenticateUser(PDO $pdo, string $usuario, string $senha, string $secret_key): ?string
{
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE email = :usuario AND senha = :senha");
    $stmt->bindParam(':usuario', $usuario);
    $stmt->bindParam(':senha', $senha);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        return generate_jwt_token(['usuario' => $user['email']], $secret_key);
    }

    return null;
}