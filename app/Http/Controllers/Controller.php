<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Desafio Fênix API',
    description: 'API para gerenciamento de exames, questões, respostas e rankings.',
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'apiKey',
    name: 'Authorization',
    in: 'header',
    description: 'Informe o token no formato: Bearer {token}',
)]
#[OA\Server(
    url: '/api',
    description: 'API Server',
)]
abstract class Controller
{
}
