/**
 * api.js — a única parte do front-end que fala com o PHP.
 * Nenhum outro arquivo JS deve usar fetch() diretamente; assim,
 * se um dia mudar a URL ou o formato da API, só mexe aqui.
 */

const API = {
    async newGame(mode, difficulty) {
        return API._post('api/new-game.php', { mode, difficulty });
    },

    async move(fromRow, fromCol, toRow, toCol) {
        return API._post('api/move.php', { fromRow, fromCol, toRow, toCol });
    },

    async aiMove() {
        return API._post('api/ai-move.php', {});
    },

    async _post(url, body) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin', // garante que o cookie de sessão vai junto
            body: JSON.stringify(body),
        });
        return response.json();
    },
};