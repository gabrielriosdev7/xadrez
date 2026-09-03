<?php

require_once __DIR__ . '/bootstrap.php';

/**
 * POST /api/move.php
 * Body esperado (JSON): { "fromRow": 6, "fromCol": 4, "toRow": 4, "toCol": 4 }
 *
 * Este é o endpoint mais importante do sistema: é AQUI que a
 * "fonte da verdade" é aplicada. O JavaScript nunca decide sozinho
 * se uma jogada vale — ele só manda a intenção, e o PHP valida
 * usando Rules (via Game::makeMove).
 */

$game = loadGameFromSession();

if ($game === null) {
    jsonResponse(['success' => false, 'error' => 'Nenhuma partida em andamento. Crie uma em new-game.php.'], 400);
}

$body = getJsonBody();

// Validação básica de entrada — nunca confie no que vem do navegador
foreach (['fromRow', 'fromCol', 'toRow', 'toCol'] as $field) {
    if (!isset($body[$field]) || !is_int($body[$field])) {
        jsonResponse(['success' => false, 'error' => "Campo '$field' ausente ou inválido."], 400);
    }
}

$result = $game->makeMove($body['fromRow'], $body['fromCol'], $body['toRow'], $body['toCol']);

// Mesmo em caso de erro (jogada ilegal), salvamos o Game de volta —
// ele não mudou, mas isso mantém a sessão consistente.
saveGameToSession($game);

$result['board'] = $game->getBoard()->toArray();

jsonResponse($result);