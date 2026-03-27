import axios from 'axios';

interface GuestLoginComponent {
    mode: string;
    email: string;
    guestLoaded: boolean;
    usingDemoCredentials: boolean;
    savedEmail: string;
    savedPassword: string;
    init(this: GuestLoginComponent): void;
    switchMode(this: GuestLoginComponent, nextMode: string): void;
    fetchGuestCredentials(this: GuestLoginComponent): Promise<void>;
    fillLoginForm(this: GuestLoginComponent): void;
    captureLoginFormState(this: GuestLoginComponent): void;
    restoreLoginFormState(this: GuestLoginComponent): void;
}

export default (initialMode: string = 'login'): GuestLoginComponent => ({
    mode: initialMode,
    email: '',
    guestLoaded: false,
    usingDemoCredentials: false,
    savedEmail: '',
    savedPassword: '',

    init(this: GuestLoginComponent): void {
        if (this.mode === 'demo') {
            this.captureLoginFormState();
            this.fetchGuestCredentials();
        }
    },

    switchMode(this: GuestLoginComponent, nextMode: string): void {
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

        if (this.guestLoaded) {
            this.fillLoginForm();
            return;
        }

        this.fetchGuestCredentials();
    },

    async fetchGuestCredentials(this: GuestLoginComponent): Promise<void> {
        try {
            const response = await axios.get('/guest-login-credentials');
            this.email = response.data.email;
            this.guestLoaded = true;
            this.fillLoginForm();
        } catch (error) {
            console.error('Failed to fetch guest credentials:', error);
            alert('Could not find guest user credentials.');
        }
    },

    fillLoginForm(this: GuestLoginComponent): void {
        const emailInput = document.getElementById('email') as HTMLInputElement;
        const passwordInput = document.getElementById('password') as HTMLInputElement;

        if (emailInput && passwordInput) {
            emailInput.value = this.email;
            passwordInput.value = 'password';
            this.usingDemoCredentials = true;
        }
    },

    captureLoginFormState(this: GuestLoginComponent): void {
        const emailInput = document.getElementById('email') as HTMLInputElement;
        const passwordInput = document.getElementById('password') as HTMLInputElement;

        if (! emailInput || ! passwordInput) {
            return;
        }

        this.savedEmail = emailInput.value;
        this.savedPassword = passwordInput.value;
    },

    restoreLoginFormState(this: GuestLoginComponent): void {
        const emailInput = document.getElementById('email') as HTMLInputElement;
        const passwordInput = document.getElementById('password') as HTMLInputElement;

        if (! emailInput || ! passwordInput) {
            return;
        }

        emailInput.value = this.savedEmail;
        passwordInput.value = this.savedPassword;
    }
});
