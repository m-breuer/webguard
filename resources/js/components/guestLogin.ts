import axios from 'axios';

interface GuestLoginComponent {
    email: string;
    init(this: GuestLoginComponent): void;
    fetchGuestCredentials(this: GuestLoginComponent): Promise<void>;
    fillLoginForm(this: GuestLoginComponent): void;
}

export default (): GuestLoginComponent => ({
    email: '',

    init(this: GuestLoginComponent): void {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('guest')) {
            this.fetchGuestCredentials();
        }
    },

    async fetchGuestCredentials(this: GuestLoginComponent): Promise<void> {
        try {
            const response = await axios.get('/guest-login-credentials');
            this.email = response.data.email;
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
