import axios from 'axios';

interface DemoLoginComponent {
    mode: string;
    email: string;
    demoLoaded: boolean;
    usingDemoCredentials: boolean;
    savedEmail: string;
    savedPassword: string;
    init(this: DemoLoginComponent): void;
    switchMode(this: DemoLoginComponent, nextMode: string): void;
    fetchDemoCredentials(this: DemoLoginComponent): Promise<void>;
    fillLoginForm(this: DemoLoginComponent): void;
    captureLoginFormState(this: DemoLoginComponent): void;
    restoreLoginFormState(this: DemoLoginComponent): void;
}

export default (initialMode: string = 'login'): DemoLoginComponent => ({
    mode: initialMode,
    email: '',
    demoLoaded: false,
    usingDemoCredentials: false,
    savedEmail: '',
    savedPassword: '',

    init(this: DemoLoginComponent): void {
        if (this.mode === 'demo') {
            this.captureLoginFormState();
            this.fetchDemoCredentials();
        }
    },

    switchMode(this: DemoLoginComponent, nextMode: string): void {
        const previousMode = this.mode;

        if (previousMode === 'demo' && nextMode !== 'demo' && this.usingDemoCredentials) {
            this.restoreLoginFormState();
            this.usingDemoCredentials = false;
        }

        this.mode = nextMode;

        if (nextMode !== 'demo') {
            return;
        }

        this.captureLoginFormState();

        if (this.demoLoaded) {
            this.fillLoginForm();
            return;
        }

        this.fetchDemoCredentials();
    },

    async fetchDemoCredentials(this: DemoLoginComponent): Promise<void> {
        try {
            const response = await axios.get('/demo-login-credentials');
            this.email = response.data.email;
            this.demoLoaded = true;
            this.fillLoginForm();
        } catch (error) {
            console.error('Failed to fetch demo credentials:', error);
            alert('Could not find demo user credentials.');
        }
    },

    fillLoginForm(this: DemoLoginComponent): void {
        const emailInput = document.getElementById('email') as HTMLInputElement;
        const passwordInput = document.getElementById('password') as HTMLInputElement;

        if (emailInput && passwordInput) {
            emailInput.value = this.email;
            passwordInput.value = 'password';
            this.usingDemoCredentials = true;
        }
    },

    captureLoginFormState(this: DemoLoginComponent): void {
        const emailInput = document.getElementById('email') as HTMLInputElement;
        const passwordInput = document.getElementById('password') as HTMLInputElement;

        if (! emailInput || ! passwordInput) {
            return;
        }

        this.savedEmail = emailInput.value;
        this.savedPassword = passwordInput.value;
    },

    restoreLoginFormState(this: DemoLoginComponent): void {
        const emailInput = document.getElementById('email') as HTMLInputElement;
        const passwordInput = document.getElementById('password') as HTMLInputElement;

        if (! emailInput || ! passwordInput) {
            return;
        }

        emailInput.value = this.savedEmail;
        passwordInput.value = this.savedPassword;
    }
});
