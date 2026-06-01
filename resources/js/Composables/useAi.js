import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export const AI_NOT_CONNECTED_MESSAGE =
    'Connect an AI account in your Profile settings to use AI features.'

/**
 * Shared helper for AI-powered components.
 *
 * Reads the per-user AI connection state shared via Inertia, and exposes a
 * guard the components can use to short-circuit with a "connect" prompt when
 * the user has not connected their own AI account.
 */
export function useAi() {
    const page = usePage()

    const aiConnected = computed(() => page.props.ai?.connected ?? false)
    const aiProvider = computed(() => page.props.ai?.provider ?? null)

    /**
     * Returns true when AI is usable. When it isn't, sets the given error ref
     * to a message pointing the user to settings.
     *
     * @param {import('vue').Ref<string>} errorRef
     * @returns {boolean}
     */
    const ensureConnected = (errorRef) => {
        if (!aiConnected.value) {
            if (errorRef) errorRef.value = AI_NOT_CONNECTED_MESSAGE
            return false
        }
        return true
    }

    return { aiConnected, aiProvider, ensureConnected, AI_NOT_CONNECTED_MESSAGE }
}
