/**
 * ui.js — o "maestro": conecta board.js (desenho) com api.js
 * (comunicação com o PHP) e decide o que fazer com as respostas.
 *
 * Importante: aqui NÃO existe nenhuma lógica de "essa jogada é
 * válida?" — isso mora só no PHP. O front só manda a intenção do
 * clique e redesenha o que o servidor responder.
 */

const statusEl = document.getElementById('status');
const newGameBtn = document.getElementById('new-game-btn');
const modeSelect = document.getElementById('mode-select');
const difficultySelect = document.getElementById('difficulty-select');

let currentMode = 'player-vs-ai';
let selectedSquare = null; // [row, col] ou null
let aiVsAiTimer = null;

// No modo "Jogador vs. IA", o humano sempre joga de brancas.
const HUMAN_COLOR = 'white';

function setStatus(text) {
    statusEl.textContent = text;
}

function stopAiVsAiLoop() {
    if (aiVsAiTimer) {
        clearInterval(aiVsAiTimer);
        aiVsAiTimer = null;
    }
}

/**
 * Atualiza a tela inteira a partir de uma resposta da API
 * (new-game.php, move.php ou ai-move.php têm formatos parecidos).
 */
function updateFromResponse(data) {
    Board.render(data.board, selectedSquare);

    if (data.status === 'checkmate') {
        setStatus(`Xeque-mate! Vencedor: ${data.winner === 'white' ? 'Brancas' : 'Pretas'}`);
        stopAiVsAiLoop();
    } else if (data.status === 'stalemate') {
        setStatus('Empate por afogamento (stalemate).');
        stopAiVsAiLoop();
    } else {
        const turnLabel = data.turn === 'white' ? 'Brancas' : 'Pretas';
        setStatus(data.inCheck ? `${turnLabel} em xeque! Vez de ${turnLabel}.` : `Vez de: ${turnLabel}`);
    }
}

/**
 * Modo "IA vs. IA": fica pedindo jogadas automaticamente até
 * a partida acabar.
 */
function startAiVsAiLoop() {
    stopAiVsAiLoop();
    aiVsAiTimer = setInterval(async () => {
        const data = await API.aiMove();
        if (!data.success) {
            stopAiVsAiLoop();
            return;
        }
        updateFromResponse(data);
    }, 800);
}

/**
 * Clique numa casa — só faz sentido no modo "Jogador vs. IA".
 * Primeiro clique seleciona a peça, segundo clique tenta o movimento.
 */
Board.onSquareClick = async (row, col) => {
    if (currentMode !== 'player-vs-ai') {
        return; // no modo IA vs. IA, cliques não fazem nada
    }

    if (selectedSquare === null) {
        // Ainda não escolheu peça de origem — só guarda a seleção.
        // (Não validamos aqui se é peça do jogador: se escolher errado,
        // o move.php vai recusar e a gente limpa a seleção.)
        selectedSquare = [row, col];
        Board.render(window.lastBoardState, selectedSquare);
        return;
    }

    const [fromRow, fromCol] = selectedSquare;
    selectedSquare = null;

    const data = await API.move(fromRow, fromCol, toRowColFix(row), col);
    window.lastBoardState = data.board;

    if (!data.success) {
        setStatus(`Jogada inválida: ${data.error}`);
        Board.render(window.lastBoardState, null);
        return;
    }

    updateFromResponse(data);

    // Se a partida continua, é a vez da IA responder.
    if (data.status === null && data.turn !== HUMAN_COLOR) {
        setStatus('IA pensando...');
        const aiData = await API.aiMove();
        window.lastBoardState = aiData.board;
        updateFromResponse(aiData);
    }
};

// Pequena função "identidade" só para deixar explícito onde a
// conversão de coordenadas aconteceria, caso um dia o tabuleiro
// seja desenhado invertido (jogando de pretas, por exemplo).
function toRowColFix(row) {
    return row;
}

newGameBtn.addEventListener('click', async () => {
    stopAiVsAiLoop();
    selectedSquare = null;
    currentMode = modeSelect.value;
    const difficulty = difficultySelect.value;

    const data = await API.newGame(currentMode, difficulty);
    window.lastBoardState = data.board;
    Board.render(data.board);
    setStatus(`Partida iniciada — modo: ${currentMode}. Vez de: Brancas`);

    if (currentMode === 'ai-vs-ai') {
        startAiVsAiLoop();
    }
});