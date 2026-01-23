import './bootstrap';
import '../css/app.css';
import '@fontsource/ibm-plex-sans/latin-400.css';
import '@fontsource/ibm-plex-sans/latin-500.css';
import '@fontsource/ibm-plex-sans/latin-600.css';
import '@fontsource/sora/latin-600.css';
import '@fontsource/sora/latin-700.css';
import { createApp } from 'vue';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from 'primevue/dropdown';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import ProgressSpinner from 'primevue/progressspinner';
import 'primeicons/primeicons.css';
import App from './App.vue';

const app = createApp(App);

app.use(PrimeVue, {
	ripple: true,
	theme: {
		preset: Aura,
		options: {
			darkModeSelector: '.p-dark',
		},
	},
	zIndex: {
		modal: 1100,
		overlay: 1200,
		menu: 1200,
		tooltip: 1300,
	},
});
app.use(ToastService);
app.use(ConfirmationService);

app.component('Button', Button);
app.component('DataTable', DataTable);
app.component('Column', Column);
app.component('Dropdown', Dropdown);
app.component('Toast', Toast);
app.component('ConfirmDialog', ConfirmDialog);
app.component('ProgressSpinner', ProgressSpinner);

app.mount('#app');
