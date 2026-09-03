<?php

/**
 * Arquivo carregado por TODOS os endpoints em api/.
 * Responsável por:
 *  - iniciar a sessão PHP (é aqui que guardamos a partida em andamento)
 *  - carregar as classes do jogo
 *  - fornecer funções auxiliares para salvar/recuperar o Game da sessão
 *
 * Por que sessão e não banco por enquanto? Porque sessão é mais simples
 * pra desenvolver e testar localmente. Mais pra frente, quando entrarmos
 * em login/ranking, vamos migrar o histórico definitivo pro banco —
 * mas o estado "vivo" da partida pode continuar na sessão.
 */

session_start();

require_once __DIR__ . '/../../src/Piece.php';
require_once __DIR__ . '/../../src/Board.php';
require_once __DIR__ . '/../../src/Rules.php';
require_once __DIR__ . '/../../src/Game.php';
require_once __DIR__ . '/../../src/AI.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Recupera a partida atual da sessão (ou null se não há nenhuma).
 * Usamos serialize/unserialize do PHP para guardar o objeto Game
 * inteiro — por isso é essencial que as classes já estejam
 * carregadas (require_once acima) ANTES do unserialize.
 */
function loadGameFromSession(): ?Game
{
    if (!isset($_SESSION['game'])) {
        return null;
    }
    return unserialize($_SESSION['game']);
}

/**
 * Salva a partida atual na sessão, para a próxima requisição
 * conseguir recuperá-la.
 */
function saveGameToSession(Game $game): void
{
    $_SESSION['game'] = serialize($game);
}

/**
 * Helper para responder em JSON e encerrar o script.
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Lê o corpo da requisição (JSON enviado pelo fetch do JS) e
 * devolve como array associativo.
 */
function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}