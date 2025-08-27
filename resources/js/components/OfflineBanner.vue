<template>
    <transition name="fade">
        <div v-if="!online" class="offline-banner">
            <span>You are offline. Some features may not be available.</span>
        </div>
    </transition>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

const online = ref(navigator.onLine);

function updateStatus() {
    online.value = navigator.onLine;
}

onMounted(() => {
    window.addEventListener('online', updateStatus);
    window.addEventListener('offline', updateStatus);
});

onUnmounted(() => {
    window.removeEventListener('online', updateStatus);
    window.removeEventListener('offline', updateStatus);
});
</script>

<style scoped>
.offline-banner {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    background: #f63030;
    color: #fff;
    text-align: center;
    padding: 0.75rem 0;
    z-index: 1000;
    font-weight: 500;
    letter-spacing: 0.02em;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
