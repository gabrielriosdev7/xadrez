/**
 * board.js — cuida só do desenho do tabuleiro e da captura de
 * cliques. Não sabe NADA sobre regras de xadrez, nem chama a API
 * diretamente — quem decide o que fazer com um clique é o ui.js.
 */

const PIECE_SYMBOLS = {
    white: { K: '♔', Q: '♕', R: '♖', B: '♗', N: '♘', P: '♙' },
    black: { K: '♚', Q: '♛', R: '♜', B: '♝', N: '♞', P: '♟' },
};

const Board = {
    element: document.getElementById('board'),
    onSquareClick: null, // callback definido pelo ui.js: (row, col) => {}

    /**
     * Desenha o tabuleiro inteiro a partir do array vindo do PHP
     * (board.toArray() do lado do servidor).
     *
     * @param boardState  matriz 8x8 de {type, color} ou null
     * @param selected    [row, col] da casa selecionada, ou null
     * @param legalMoves  lista de [row, col] destino, para destacar
     */
    render(boardState, selected = null, legalMoves = []) {
        this.element.innerHTML = '';

        for (let row = 0; row < 8; row++) {
            for (let col = 0; col < 8; col++) {
                const square = document.createElement('div');
                const isLight = (row + col) % 2 === 0;
                square.className = `square ${isLight ? 'light' : 'dark'}`;

                if (selected && selected[0] === row && selected[1] === col) {
                    square.classList.add('selected');
                }
                if (legalMoves.some(([r, c]) => r === row && c === col)) {
                    square.classList.add('legal-move');
                }

                const piece = boardState[row][col];
                if (piece) {
                    square.textContent = PIECE_SYMBOLS[piece.color][piece.type];
                    square.classList.add(piece.color === 'white' ? 'piece-white' : 'piece-black');
                }

                square.addEventListener('click', () => {
                    if (this.onSquareClick) {
                        this.onSquareClick(row, col);
                    }
                });

                this.element.appendChild(square);
            }
        }
    },
};