import { Application } from '@hotwired/stimulus';
import MapController from './controllers/map_controller.js';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = Application.start();

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
app.register('map', MapController);
