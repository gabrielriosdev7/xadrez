<?php

require_once __DIR__ . '/Board.php';
require_once __DIR__ . '/Rules.php';

/**
 * Game representa UMA partida em andamento. Enquanto Board é só o
 * "tabuleiro" e Rules é o "juiz", Game é quem sabe a história toda:
 * de quem é a vez, o que já foi jogado, e se a partida acabou.
 */
class Game
{
    private Board $board;
    private Rules $rules;
    private string $turn = 'white'; // brancas sempre começam
    private array $history = [];    // lista de jogadas já feitas
    private ?string $status = null; // null = em andamento, ou 'checkmate', 'stalemate'
    private ?string $winner = null;

    public function __construct()
    {
        $this->board = new Board();
        $this->board->setupInitialPosition();
        $this->rules = new Rules();
    }

    public function getBoard(): Board
    {
        return $this->board;
    }

    public function getTurn(): string
    {
        return $this->turn;
    }

    public function getHistory(): array
    {
        return $this->history;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getWinner(): ?string
    {
        return $this->winner;
    }

    public function isOver(): bool
    {
        return $this->status !== null;
    }

    /**
     * Tenta executar uma jogada. Retorna um array com o resultado —
     * é isso que o endpoint move.php vai transformar em JSON.
     */
    public function makeMove(int $fromRow, int $fromCol, int $toRow, int $toCol): array
    {
        if ($this->isOver()) {
            return ['success' => false, 'error' => 'A partida já terminou.'];
        }

        $piece = $this->board->getPieceAt($fromRow, $fromCol);

        if ($piece === null) {
            return ['success' => false, 'error' => 'Não há peça nessa casa.'];
        }

        if ($piece->getColor() !== $this->turn) {
            return ['success' => false, 'error' => 'Não é a vez dessa cor.'];
        }

        if (!$this->rules->isLegalMove($this->board, $fromRow, $fromCol, $toRow, $toCol)) {
            return ['success' => false, 'error' => 'Jogada ilegal.'];
        }

        // Executa de fato — só chegamos aqui se passou por todas as validações acima
        $captured = $this->board->movePiece($fromRow, $fromCol, $toRow, $toCol);

        $this->history[] = [
            'piece' => $piece->getSymbol(),
            'color' => $piece->getColor(),
            'from' => [$fromRow, $fromCol],
            'to' => [$toRow, $toCol],
            'captured' => $captured?->getSymbol(),
        ];

        // Passa a vez
        $this->turn = $this->turn === 'white' ? 'black' : 'white';

        // Checa se a partida acabou para quem vai jogar agora
        $this->updateStatus();

        return [
            'success' => true,
            'captured' => $captured?->getSymbol(),
            'inCheck' => $this->rules->isInCheck($this->board, $this->turn),
            'status' => $this->status,
            'winner' => $this->winner,
            'turn' => $this->turn,
        ];
    }

    /**
     * Depois de cada jogada, verifica se quem vai jogar agora está
     * em xeque-mate ou afogamento — se estiver, a partida terminou.
     */
    private function updateStatus(): void
    {
        if ($this->rules->isCheckmate($this->board, $this->turn)) {
            $this->status = 'checkmate';
            // quem acabou de jogar (a cor oposta ao turno atual) venceu
            $this->winner = $this->turn === 'white' ? 'black' : 'white';
        } elseif ($this->rules->isStalemate($this->board, $this->turn)) {
            $this->status = 'stalemate';
            $this->winner = null; // empate
        }
    }
}