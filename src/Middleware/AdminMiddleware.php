<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Auth;

/**
 * Middleware per verificare che l'utente sia un amministratore.
 */
class AdminMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (!Auth::isAdmin()) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'error' => 'Accesso negato',
                'message' => 'Solo gli amministratori possono accedere a questa risorsa'
            ], JSON_UNESCAPED_UNICODE));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }
        
        return $handler->handle($request);
    }
}
