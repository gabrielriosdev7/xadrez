<?php

require_once __DIR__ . '/bootstrap.php';

/**
 * POST /api/ai-move.php
 * Sem body necessário — a IA joga pela cor que está na vez agora.
 *
 * Usos:
 *  - Modo "jogador vs. IA": o front chama isto logo após o move.php
 *    ter sucesso, para a IA responder.
 *  - Modo "IA vs. IA": o front chama isto repetidamente (ex: a cada
 *    1 segundo, com setInterval) até a partida acabar.
 */

$game = loadGameFromSession();

if ($game === null) {
    jsonResponse(['success' => false, 'error' => 'Nenhuma partida em andamento.'], 400);
}

if ($game->isOver()) {
    jsonResponse([
        'success' => false,
        'error' => 'A partida já terminou.',
        'status' => $game->getStatus(),
        'winner' => $game->getWinner(),
    ], 400);
}

$difficulty = $_SESSION['difficulty'] ?? 'random';
$ai = new AI($difficulty);

$move = $ai->chooseMove($game->getBoard(), $game->getTurn());

if ($move === null) {
    // Não deveria acontecer se isOver() já filtrou xeque-mate/afogamento,
    // mas fica como rede de segurança.
    jsonResponse(['success' => false, 'error' => 'IA não encontrou jogada disponível.'], 500);
}

[$fromRow, $fromCol] = $move['from'];
[$toRow, $toCol] = $move['to'];

$result = $game->makeMove($fromRow, $fromCol, $toRow, $toCol);
$result['move'] = ['from' => $move['from'], 'to' => $move['to']]; // front precisa saber o que a IA jogou, pra animar

saveGameToSession($game);

$result['board'] = $game->getBoard()->toArray();

jsonResponse($result);