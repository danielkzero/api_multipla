<?php
// ProtectedController.php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProtectedController
{
    public function protectedRoute(Request $request, Response $response)
    {
        $token = $request->getAttribute('token');
        $response->getBody()->write('Rota Protegida! Usuário: ' . $token->username);
        return $response;
    }
}