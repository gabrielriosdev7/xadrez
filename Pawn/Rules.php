<?php

require_once __DIR__ . '/Board.php';
require_once __DIR__ . '/Piece.php';

/**
 * Rules é o "juiz" da partida. Enquanto Piece sabe seus movimentos
 * "de padrão" (pseudo-legais) e Board só executa jogadas sem
 * questionar, é aqui que perguntamos: "essa jogada é REALMENTE
 * permitida pelas regras do xadrez?"
 */
class Rules
{
    /**
     * Retorna todas as jogadas LEGAIS de uma peça em (row, col) —
     * ou seja, jogadas que não deixam o próprio rei em xeque.
     *
     * @return array Lista de [row, col] de destino, todas legais
     */
    public function getLegalMoves(Board $board, int $row, int $col): array
    {
        $piece = $board->getPieceAt($row, $col);
        if ($piece === null) {
            return [];
        }

        $pseudoLegalMoves = $piece->getPossibleMoves($board->getGrid(), $row, $col);
        $legalMoves = [];

        foreach ($pseudoLegalMoves as [$toRow, $toCol]) {
            if (!$this->moveLeavesOwnKingInCheck($board, $row, $col, $toRow, $toCol, $piece->getColor())) {
                $legalMoves[] = [$toRow, $toCol];
            }
        }

        return $legalMoves;
    }

    /**
     * O truque central: clona o tabuleiro, simula a jogada nessa
     * cópia, e verifica se o rei da cor que jogou ficou em xeque.
     * Como trabalhamos numa CÓPIA, o tabuleiro real nunca é afetado
     * por essa simulação.
     */
    private function moveLeavesOwnKingInCheck(
        Board $board,
        int $fromRow,
        int $fromCol,
        int $toRow,
        int $toCol,
        string $color
    ): bool {
        $simulation = $board->clone();
        $simulation->movePiece($fromRow, $fromCol, $toRow, $toCol);

        return $this->isInCheck($simulation, $color);
    }

    /**
     * Verifica se o rei de uma cor está em xeque: para isso,
     * olhamos se ALGUMA peça adversária tem, entre seus movimentos
     * pseudo-legais, a casa onde o rei está.
     *
     * Repare: aqui usamos getPossibleMoves() (pseudo-legal), não
     * getLegalMoves() — se usássemos getLegalMoves() aqui, teríamos
     * uma recursão infinita (legal depende de xeque, que dependeria
     * de legal...).
     */
    public function isInCheck(Board $board, string $color): bool
    {
        $kingPosition = $board->findKing($color);
        if ($kingPosition === null) {
            return false; // não deveria acontecer, mas evita erro
        }
        [$kingRow, $kingCol] = $kingPosition;

        $enemyColor = $color === 'white' ? 'black' : 'white';
        $grid = $board->getGrid();

        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $piece = $grid[$row][$col];
                if ($piece !== null && $piece->getColor() === $enemyColor) {
                    $attackMoves = $piece->getPossibleMoves($grid, $row, $col);
                    foreach ($attackMoves as [$attackRow, $attackCol]) {
                        if ($attackRow === $kingRow && $attackCol === $kingCol) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Reúne TODAS as jogadas legais de uma cor, olhando peça por
     * peça. É a base para: detectar xeque-mate, afogamento, e
     * também é o que uma IA usa para saber suas opções.
     */
    public function getAllLegalMoves(Board $board, string $color): array
    {
        $allMoves = [];
        $grid = $board->getGrid();

        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $piece = $grid[$row][$col];
                if ($piece !== null && $piece->getColor() === $color) {
                    $legalMoves = $this->getLegalMoves($board, $row, $col);
                    foreach ($legalMoves as $destination) {
                        $allMoves[] = [
                            'from' => [$row, $col],
                            'to' => $destination,
                        ];
                    }
                }
            }
        }

        return $allMoves;
    }

    /**
     * Xeque-mate = está em xeque E não tem nenhuma jogada legal
     * que resolva isso.
     */
    public function isCheckmate(Board $board, string $color): bool
    {
        return $this->isInCheck($board, $color)
            && empty($this->getAllLegalMoves($board, $color));
    }

    /**
     * Afogamento (stalemate) = NÃO está em xeque, mas também não
     * tem nenhuma jogada legal disponível. Resultado: empate.
     */
    public function isStalemate(Board $board, string $color): bool
    {
        return !$this->isInCheck($board, $color)
            && empty($this->getAllLegalMoves($board, $color));
    }

    /**
     * Verifica se uma jogada específica (from -> to) está entre as
     * jogadas legais da peça. É isso que o endpoint move.php vai
     * chamar para aceitar ou rejeitar a jogada vinda do navegador.
     */
    public function isLegalMove(Board $board, int $fromRow, int $fromCol, int $toRow, int $toCol): bool
    {
        $legalMoves = $this->getLegalMoves($board, $fromRow, $fromCol);
        foreach ($legalMoves as [$row, $col]) {
            if ($row === $toRow && $col === $toCol) {
                return true;
            }
        }
        return false;
    }
}