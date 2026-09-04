<?php

/**
 * Classe base para todas as peças do xadrez.
 * Cada peça concreta (Pawn, Rook, Knight, Bishop, Queen, King)
 * herda desta classe e implementa seu próprio getPossibleMoves().
 */
abstract class Piece
{
    protected string $color; // 'white' ou 'black'
    protected bool $hasMoved = false; // importante para roque e avanço duplo do peão

    public function __construct(string $color)
    {
        $this->color = $color;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function hasMoved(): bool
    {
        return $this->hasMoved;
    }

    public function markAsMoved(): void
    {
        $this->hasMoved = true;
    }

    /**
     * Cada peça sabe gerar seus movimentos "pseudo-legais":
     * respeita o padrão de movimento da peça e não passa por cima
     * de outras peças (exceto o cavalo), mas AINDA NÃO verifica
     * se o movimento deixa o próprio rei em xeque — isso é
     * responsabilidade da classe Rules, que usa este método como base.
     *
     * @return array Lista de posições [linha, coluna] possíveis
     */
    abstract public function getPossibleMoves(array $board, int $row, int $col): array;

    /**
     * Letra usada para identificar a peça (útil para notação e depuração).
     */
    abstract public function getSymbol(): string;

    /**
     * Valor da peça (usado por uma IA simples para avaliar jogadas).
     */
    abstract public function getValue(): int;

    /**
     * Verifica se uma posição está dentro do tabuleiro 8x8.
     */
    
        /**
     * Representação simples da peça, pronta para virar JSON e ser
     * consumida pelo JavaScript no navegador.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getSymbol(),
            'color' => $this->color,
        ];
    }
    
    protected function isOnBoard(int $row, int $col): bool
    {
        return $row >= 0 && $row < 8 && $col >= 0 && $col < 8;
    }

    /**
     * Verifica se a casa está vazia ou ocupada por peça inimiga
     * (ou seja, se a peça pode "entrar" ali).
     */
    protected function canMoveTo(array $board, int $row, int $col): bool
    {
        if (!$this->isOnBoard($row, $col)) {
            return false;
        }
        $occupant = $board[$row][$col] ?? null;
        return $occupant === null || $occupant->getColor() !== $this->color;
    }

    /**
     * Usado pelas peças de movimento "deslizante" (torre, bispo, dama):
     * anda numa direção até bater em outra peça ou sair do tabuleiro.
     */
    protected function slideMoves(array $board, int $row, int $col, array $directions): array
    {
        $moves = [];
        foreach ($directions as [$dRow, $dCol]) {
            $r = $row + $dRow;
            $c = $col + $dCol;
            while ($this->isOnBoard($r, $c)) {
                $occupant = $board[$r][$c] ?? null;
                if ($occupant === null) {
                    $moves[] = [$r, $c];
                } else {
                    if ($occupant->getColor() !== $this->color) {
                        $moves[] = [$r, $c]; // pode capturar, mas para aqui
                    }
                    break;
                }
                $r += $dRow;
                $c += $dCol;
            }
        }
        return $moves;
    }
}

class Pawn extends Piece
{
    public function getPossibleMoves(array $board, int $row, int $col): array
    {
        $moves = [];
        $direction = $this->color === 'white' ? -1 : 1; // brancas sobem (linha diminui), pretas descem
        $startRow = $this->color === 'white' ? 6 : 1;

        // Movimento simples para frente
        $oneStep = $row + $direction;
        if ($this->isOnBoard($oneStep, $col) && !isset($board[$oneStep][$col])) {
            $moves[] = [$oneStep, $col];

            // Avanço duplo a partir da posição inicial
            $twoStep = $row + 2 * $direction;
            if ($row === $startRow && !isset($board[$twoStep][$col])) {
                $moves[] = [$twoStep, $col];
            }
        }

        // Capturas na diagonal
        foreach ([-1, 1] as $dCol) {
            $r = $row + $direction;
            $c = $col + $dCol;
            if ($this->isOnBoard($r, $c) && isset($board[$r][$c]) && $board[$r][$c]->getColor() !== $this->color) {
                $moves[] = [$r, $c];
            }
        }

        // Nota: en passant e promoção ficam por conta da classe Rules,
        // que tem acesso ao histórico de jogadas (necessário para en passant).

        return $moves;
    }

    public function getSymbol(): string
    {
        return 'P';
    }

    public function getValue(): int
    {
        return 1;
    }
}

class Rook extends Piece
{
    public function getPossibleMoves(array $board, int $row, int $col): array
    {
        return $this->slideMoves($board, $row, $col, [
            [-1, 0], [1, 0], [0, -1], [0, 1],
        ]);
    }

    public function getSymbol(): string
    {
        return 'R';
    }

    public function getValue(): int
    {
        return 5;
    }
}

class Knight extends Piece
{
    public function getPossibleMoves(array $board, int $row, int $col): array
    {
        $moves = [];
        $offsets = [
            [-2, -1], [-2, 1], [-1, -2], [-1, 2],
            [1, -2], [1, 2], [2, -1], [2, 1],
        ];
        foreach ($offsets as [$dRow, $dCol]) {
            $r = $row + $dRow;
            $c = $col + $dCol;
            if ($this->canMoveTo($board, $r, $c)) {
                $moves[] = [$r, $c];
            }
        }
        return $moves;
    }

    public function getSymbol(): string
    {
        return 'N';
    }

    public function getValue(): int
    {
        return 3;
    }
}

class Bishop extends Piece
{
    public function getPossibleMoves(array $board, int $row, int $col): array
    {
        return $this->slideMoves($board, $row, $col, [
            [-1, -1], [-1, 1], [1, -1], [1, 1],
        ]);
    }

    public function getSymbol(): string
    {
        return 'B';
    }

    public function getValue(): int
    {
        return 3;
    }
}

class Queen extends Piece
{
    public function getPossibleMoves(array $board, int $row, int $col): array
    {
        return $this->slideMoves($board, $row, $col, [
            [-1, 0], [1, 0], [0, -1], [0, 1],
            [-1, -1], [-1, 1], [1, -1], [1, 1],
        ]);
    }

    public function getSymbol(): string
    {
        return 'Q';
    }

    public function getValue(): int
    {
        return 9;
    }
}

class King extends Piece
{
    public function getPossibleMoves(array $board, int $row, int $col): array
    {
        $moves = [];
        $offsets = [
            [-1, -1], [-1, 0], [-1, 1],
            [0, -1],           [0, 1],
            [1, -1],  [1, 0],  [1, 1],
        ];
        foreach ($offsets as [$dRow, $dCol]) {
            $r = $row + $dRow;
            $c = $col + $dCol;
            if ($this->canMoveTo($board, $r, $c)) {
                $moves[] = [$r, $c];
            }
        }

        // Nota: roque fica por conta da classe Rules, pois depende
        // de verificar se rei/torre já se moveram e se as casas
        // entre eles não estão sob ataque.

        return $moves;
    }

    public function getSymbol(): string
    {
        return 'K';
    }

    public function getValue(): int
    {
        return 0; // rei não entra na contagem de material
    }
}