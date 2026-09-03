<?php

require_once __DIR__ . '/Board.php';
require_once __DIR__ . '/Rules.php';

/**
 * IA simples para jogar contra o usuário (ou contra outra IA).
 *
 * Propositalmente NÃO usa nenhum algoritmo sofisticado (nada de
 * minimax, poda alfa-beta, etc.) — o objetivo aqui é você aprender
 * PHP, não construir uma engine de xadrez competitiva. Ainda assim,
 * já dá pra ver a ideia central de "avaliar jogadas": olhar as
 * opções disponíveis e escolher a "melhor" segundo algum critério.
 */
class AI
{
    private Rules $rules;
    private string $difficulty;

    /**
     * @param string $difficulty 'random' ou 'greedy'
     *   - random: escolhe qualquer jogada legal, sem critério.
     *   - greedy: prioriza jogadas que capturam a peça inimiga de
     *     maior valor (mas "gulosa": não pensa nas consequências
     *     depois dessa jogada, só olha o ganho imediato).
     */
    public function __construct(string $difficulty = 'random')
    {
        $this->rules = new Rules();
        $this->difficulty = $difficulty;
    }

    /**
     * Escolhe e retorna uma jogada para a cor informada.
     *
     * @return array|null ['from' => [row, col], 'to' => [row, col]] ou null se não há jogadas
     */
    public function chooseMove(Board $board, string $color): ?array
    {
        $legalMoves = $this->rules->getAllLegalMoves($board, $color);

        if (empty($legalMoves)) {
            return null; // xeque-mate ou afogamento — a IA não tem o que jogar
        }

        return match ($this->difficulty) {
            'greedy' => $this->chooseGreedyMove($board, $legalMoves),
            default => $this->chooseRandomMove($legalMoves),
        };
    }

    /**
     * Modo mais simples possível: sorteia uma entre as jogadas legais.
     */
    private function chooseRandomMove(array $legalMoves): array
    {
        $index = array_rand($legalMoves);
        return $legalMoves[$index];
    }

    /**
     * Modo "guloso": entre todas as jogadas legais, prefere as que
     * capturam alguma peça — e, entre as capturas, a de maior valor.
     * Se não há nenhuma captura disponível, cai para uma jogada aleatória.
     */
    private function chooseGreedyMove(Board $board, array $legalMoves): array
    {
        $bestMove = null;
        $bestValue = -1;

        foreach ($legalMoves as $move) {
            [$toRow, $toCol] = $move['to'];
            $targetPiece = $board->getPieceAt($toRow, $toCol);

            if ($targetPiece !== null) {
                $value = $targetPiece->getValue();
                if ($value > $bestValue) {
                    $bestValue = $value;
                    $bestMove = $move;
                }
            }
        }

        return $bestMove ?? $this->chooseRandomMove($legalMoves);
    }
}