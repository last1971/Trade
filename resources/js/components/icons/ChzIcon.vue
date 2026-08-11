<template>
    <!-- Знак «Честный ЗНАК»: рамка считывания кода + галочка. Монохром,
         красится через currentColor — ведёт себя как обычная mdi-иконка. -->
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
         width="100%" height="100%" role="img" aria-label="Маркировка ЧЗ"
    >
        <path d="M3 9V5a2 2 0 0 1 2-2h4v2H5v4H3z"/>
        <path d="M15 3h4a2 2 0 0 1 2 2v4h-2V5h-4V3z"/>
        <path d="M21 15v4a2 2 0 0 1-2 2h-4v-2h4v-4h2z"/>
        <path d="M9 21H5a2 2 0 0 1-2-2v-4h2v4h4v2z"/>
        <path d="M10.6 16.2 6.9 12.5l1.6-1.6 2.1 2.1 4.9-4.9 1.6 1.6-6.5 6.5z"/>
    </svg>
</template>

<script>
export default {
    name: "ChzIcon",
}
</script>
