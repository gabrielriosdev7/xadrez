<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Xadrez PHP</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="game-container">
        <h1>Xadrez em PHP</h1>

        <div class="controls">
            <label>
                Modo:
                <select id="mode-select">
                    <option value="player-vs-ai">Jogador vs. IA</option>
                    <option value="ai-vs-ai">IA vs. IA</option>
                </select>
            </label>

            <label>
                Dificuldade da IA:
                <select id="difficulty-select">
                    <option value="random">Aleatória</option>
                    <option value="greedy">Gulosa (prioriza capturas)</option>
                </select>
            </label>

            <button id="new-game-btn">Nova partida</button>
        </div>

        <div id="status" class="status">Clique em "Nova partida" para começar.</div>

        <div id="board" class="board"></div>
    </div>

    <script src="js/api.js"></script>
    <script src="js/board.js"></script>
    <script src="js/ui.js"></script>
</body>
</html>