import { ref } from 'vue';

const message = ref('');
const kind = ref('success');
let timeout;

export function useToast() {
    function show(text, type = 'success') {
        message.value = text;
        kind.value = type;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            message.value = '';
        }, 3500);
    }

    function dismiss() {
        clearTimeout(timeout);
        message.value = '';
    }

    return { message, kind, show, dismiss };
}
