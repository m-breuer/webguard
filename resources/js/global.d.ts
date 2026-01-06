declare global {
    interface Window {
        setTheme: (theme: string) => void;
        Alpine: any; // Using 'any' for simplicity, can be more specific if Alpine.js types are available
        Chart: any; // Adding Chart to the Window interface
        axios: any; // Adding axios to the Window interface
        App: {
            locale: string;
        };
    }
}

export {}; // This is needed to make it a module and not global script