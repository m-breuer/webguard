import axios from 'axios';

interface GuestLoginComponent {
    mode: string;
    email: string;
    guestLoaded: boolean;
    init(this: GuestLoginComponent): void;
    switchMode(this: GuestLoginComponent, nextMode: string): void;
    fetchGuestCredentials(this: GuestLoginComponent): Promise<void>;
    fillLoginForm(this: GuestLoginComponent): void;
}

export default (initialMode: string = 'login'): GuestLoginComponent => ({
    mode: initialMode,
    email: '',
    guestLoaded: false,

    init(this: GuestLoginComponent): void {
        if (this.mode === 'demo') {
            this.fetchGuestCredentials();
        }
    },

    switchMode(this: GuestLoginComponent, nextMode: string): void {
        this.mode = nextMode;

        if (nextMode !== 'demo') {
            return;
        }

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
        }
    }
});
