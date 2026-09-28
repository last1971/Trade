// Те же правила, что в ModelRequest (notify-route.store/update): здесь — чтобы не гонять
// заведомо неверное на сервер, там — настоящая проверка.
export const CHANNELS = [
    {value: 'mail', text: 'почта'},
    {value: 'matrix', text: 'Matrix'},
];
export const TOPICS = ['OPS', 'MARKING', 'PRICES', 'FINANCE', 'DEV'];
export const TOPIC_RE = /^[A-Za-z_]+$/;
export const MATRIX_ROOM_RE = /^![A-Za-z0-9._=\-]+:[A-Za-z0-9.-]+$/;
