// CnA Vue Adapter - Browser-side mounting workflow
export type VueComponentMap = Record<string, any>;

export interface VueMountOptions {
    hydrate?: boolean;
}

/**
 * Automatically discover and mount all Vue components rendered by CnA\FrontendVue\VueAdapter.
 * Compatible with Vue 3 createApp and createSSRApp.
 */
export async function mountVueComponents(
    components: VueComponentMap,
    options: VueMountOptions = {}
): Promise<void> {
    const roots = document.querySelectorAll<HTMLElement>('.cna-vue-root[data-cna-vue-component]');
    if (roots.length === 0) return;

    let createApp: any;
    let createSSRApp: any;

    try {
        // Dynamic import keeps bundle zero-dependency if Vue is not used
        const vue = await import('vue');
        createApp = vue.createApp;
        createSSRApp = vue.createSSRApp;
    } catch {
        console.warn('[CnA Vue] vue is not installed. Run `npm i vue` to enable Vue components.');
        return;
    }

    roots.forEach((container) => {
        const name = container.dataset.cnaVueComponent;
        if (!name || !components[name]) {
            console.warn(`[CnA Vue] Component [${name}] not found in component registry.`);
            return;
        }

        let props = {};
        try {
            props = JSON.parse(container.dataset.cnaVueProps || '{}');
        } catch (e) {
            console.error(`[CnA Vue] Failed to parse props for [${name}]:`, e);
        }

        const appFactory = (options.hydrate && createSSRApp) ? createSSRApp : createApp;
        const app = appFactory(components[name], props);
        app.mount(container);
    });
}
