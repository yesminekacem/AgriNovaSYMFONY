import { startStimulusApp } from '@symfony/stimulus-bundle';
import SearchController from './controllers/search_controller.js';

const app = startStimulusApp();
app.register('search', SearchController);

console.log('[Stimulus] SearchController registered:', app.controllers.find(c => c.identifier === 'search'));
console.log('[Stimulus] All controllers:', app.controllers.map(c => c.identifier));
