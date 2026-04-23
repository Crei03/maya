<template>
    <button
        class="inline-flex items-center rounded-full border border-transparent bg-[var(--maya-primary)] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-[var(--maya-primary-dark)] focus:bg-[var(--maya-primary-dark)] focus:outline-none focus:ring-2 focus:ring-[var(--maya-primary)] focus:ring-offset-2 focus:ring-offset-[var(--maya-bg-surface)] active:bg-[var(--maya-primary-dark)] disabled:opacity-60 dark:bg-[var(--maya-primary)] dark:text-white dark:hover:bg-[var(--maya-primary-dark)] dark:focus:bg-[var(--maya-primary-dark)] dark:focus:ring-offset-[var(--maya-bg-surface)] dark:active:bg-[var(--maya-primary-dark)]"
    >
        <slot />
    </button>
</template>
