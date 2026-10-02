import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Primary nav, shared by the desktop bar and the mobile menu. Entries are
 * either links ({ label, href, active }) or groups ({ label, children }).
 * A group left with a single visible child renders as that plain link, so
 * users without LDS content (or guests) don't get one-item dropdowns.
 */
export function useNavigation() {
    const page = usePage();

    return computed(() => {
        const user = page.props.auth?.user;
        const lds = !!page.props.userSettings?.show_lds_content;
        const current = (...names) => names.some((name) => route().current(name));

        const entries = [
            { label: 'Dashboard', href: route('dashboard'), active: current('dashboard'), show: !!user },
            { label: 'Posts', href: route('posts.index'), active: current('posts.index') },
            { label: 'Lessons/Talks', href: route('lessons.index'), active: current('lessons.index') },
            {
                label: 'Study',
                children: [
                    { label: 'Study Plans', href: route('study-plans.index'), active: current('study-plans.*'), show: !!user },
                    { label: 'Scriptures', href: route('scriptures.index'), active: current('scriptures.*'), show: !!user && lds },
                    // Guest-browsable, unlike the rest of the LDS sections.
                    { label: 'Library', href: route('talks.index'), active: current('talks.index'), show: !user || lds },
                ],
            },
            {
                label: 'Temple & Family',
                children: [
                    // Auth-only (visits/trips are personal).
                    { label: 'Temples', href: route('temples.index'), active: current('temples.*', 'temple-visits.*', 'temple-trips.*'), show: !!user && lds },
                    { label: 'Family History', href: route('family-history.index'), active: current('family-history.*'), show: !!user && lds },
                ],
            },
        ];

        return entries
            .filter((entry) => entry.show !== false)
            .map((entry) => {
                if (!entry.children) return entry;
                const children = entry.children.filter((child) => child.show !== false);
                if (children.length === 1) return children[0];
                return { ...entry, children, active: children.some((child) => child.active) };
            })
            .filter((entry) => !entry.children || entry.children.length);
    });
}
