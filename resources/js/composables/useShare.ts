import { computed, ref } from 'vue';

export function useShare(url: string, text: string) {
    const linkCopied = ref(false);
    const shareUrl = computed(() => url);
    const shareText = computed(() => text);

    function openShareWindow(shareLink: string) {
        window.open(shareLink, '_blank', 'width=550,height=420');
    }

    function shareToTwitter() {
        openShareWindow(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`);
    }
    function shareToFacebook() {
        openShareWindow(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`);
    }
    function shareToLinkedIn() {
        openShareWindow(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`);
    }
    function shareToWhatsApp() {
        window.open(`https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`, '_blank');
    }
    async function copyLink() {
        try {
            await navigator.clipboard.writeText(url);
            linkCopied.value = true;
            setTimeout(() => { linkCopied.value = false; }, 1500);
        } catch (e) { console.error('copy failed', e); }
    }

    return { linkCopied, shareUrl, shareText, shareToTwitter, shareToFacebook, shareToLinkedIn, shareToWhatsApp, copyLink };
}
