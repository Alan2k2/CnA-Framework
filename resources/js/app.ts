import '../css/app.css';
import { getCsrfToken } from './bootstrap';

console.log('⚡ CnA Frontend Engine v2.0 (Vite Asset Pipeline) Active');

document.addEventListener('DOMContentLoaded', () => {
    const token = getCsrfToken();
    if (token) {
        console.log('🔒 CSRF Protection Token synced with client');
    }
});
