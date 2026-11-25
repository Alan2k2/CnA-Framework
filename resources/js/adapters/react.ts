// CnA React Adapter - Browser-side mounting workflow
export type ReactComponentMap = Record<string, any>;

export interface ReactMountOptions {
    hydrate?: boolean;
}

/**
 * Automatically discover and mount all React components rendered by CnA\FrontendReact\ReactAdapter.
 * Compatible with React 18/19 createRoot and hydrateRoot.
 */
export async function mountReactComponents(
    components: ReactComponentMap,
    options: ReactMountOptions = {}
): Promise<void> {
    const roots = document.querySelectorAll<HTMLElement>('.cna-react-root[data-cna-react-component]');
    if (roots.length === 0) return;

    let createRoot: any;
    let hydrateRoot: any;
    let createElement: any;

    try {
        // Dynamic import keeps bundle zero-dependency if React is not used
        const client = await import('react-dom/client');
        const react = await import('react');
        createRoot = client.createRoot;
        hydrateRoot = client.hydrateRoot;
        createElement = react.createElement;
    } catch {
        console.warn('[CnA React] react or react-dom is not installed. Run `npm i react react-dom` to enable React components.');
        return;
    }

    roots.forEach((container) => {
        const name = container.dataset.cnaReactComponent;
        if (!name || !components[name]) {
            console.warn(`[CnA React] Component [${name}] not found in component registry.`);
            return;
        }

        let props = {};
        try {
            props = JSON.parse(container.dataset.cnaReactProps || '{}');
        } catch (e) {
            console.error(`[CnA React] Failed to parse props for [${name}]:`, e);
        }

        const element = createElement(components[name], props);
        if (options.hydrate && hydrateRoot) {
            hydrateRoot(container, element);
        } else {
            createRoot(container).render(element);
        }
    });
}
