<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Auth;

/**
 * Middleware per verificare l'autenticazione dell'utente.
 */
class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (!Auth::check()) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'error' => 'Non autenticato',
                'message' => 'Devi effettuare il login per accedere a questa risorsa'
            ], JSON_UNESCAPED_UNICODE));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
        
        // Aggiungi i dati utente alla richiesta
        $request = $request->withAttribute('user', Auth::user());
        
        return $handler->handle($request);
    }
}
