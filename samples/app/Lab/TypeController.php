<?php
namespace Lab;

use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Route;

class TypeController
{
    #[Route(method: 'GET')]
    public function Entry(Request $request, Response $response): void
    {
        $response->header('Content-Type', 'text/plain; charset=utf-8');
        $response->end("Lab\\TypeController::Entry\n");
    }

    #[Route(method: 'GET')]
    public function ajaxEntry(Request $request, Response $response): void
    {
        $response->header('Content-Type', 'text/plain; charset=utf-8');
        $response->end("Lab\\TypeController::ajaxEntry\n");
    }

    #[Route(methods: ['GET', 'POST'])]
    public function Preview(Request $request, Response $response): void
    {
        $response->header('Content-Type', 'text/plain; charset=utf-8');
        $response->end("Lab\\TypeController::Preview\n" . $request->getMethod() . "\n");
    }
}
