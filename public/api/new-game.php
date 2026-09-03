<?php

require_once __DIR__ . '/bootstrap.php';

/**
 * POST /api/new-game.php
 * Body esperado (JSON): { "mode": "player-vs-ai" | "ai-vs-ai", "difficulty": "random" | "greedy" }
 *
 * Cria uma partida nova do zero e a guarda na sessão do usuário.
 * Qualquer partida em andamento anteriormente é substituída.
 */

$body = getJsonBody();
$mode = $body['mode'] ?? 'player-vs-ai';
$difficulty = $body['difficulty'] ?? 'random';

$game = new Game();

// Guardamos o modo e a dificuldade na sessão também, para o
// endpoint move.php saber se deve ou não jogar pela IA em seguida.
$_SESSION['mode'] = $mode;
$_SESSION['difficulty'] = $difficulty;

saveGameToSession($game);

jsonResponse([
    'success' => true,
    'board' => $game->getBoard()->toArray(),
    'turn' => $game->getTurn(),
    'mode' => $mode,
]);