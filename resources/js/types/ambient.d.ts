// Ambient type declarations for optional frontend framework adapters
declare module 'react-dom/client' {
    export const createRoot: any;
    export const hydrateRoot: any;
}

declare module 'react' {
    export const createElement: any;
}

declare module 'vue' {
    export const createApp: any;
    export const createSSRApp: any;
}
