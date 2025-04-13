import { Application } from '@hotwired/stimulus';
import MapController from './controllers/map_controller.js';
import ContactController from './controllers/contact_controller.js';
import MenuBurgerController from './controllers/menu-burger_controller.js';
import HeaderScrollController from './controllers/header_scroll_controller.js';
import FaqController from './controllers/faq_controller.js';
import StudioController from './controllers/studio_controller.js';
import NotificationsController from './controllers/notifications_controller.js';
import FieldToggleController from './controllers/field_toggle_controller.js';
import DiscussionsController from './controllers/discussions_controller.js';
import ReservationController from './controllers/reservation_controller.js';
import AuthController from './controllers/auth_controller.js';
import PhoneToggleController from './controllers/phone-toggle_controller.js';
import AccordionController from './controllers/accordion_controller.js';
// Le contrôleur de vérification des messages est maintenant intégré dans NotificationsController

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = Application.start();

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
app.register('map', MapController);
app.register('contact', ContactController);
app.register('menu-burger', MenuBurgerController);
app.register('header-scroll', HeaderScrollController);
app.register('faq', FaqController);
app.register('studio', StudioController);
app.register('notifications', NotificationsController);
app.register('field-toggle', FieldToggleController);
app.register('discussions', DiscussionsController);
app.register('reservation', ReservationController);
app.register('auth', AuthController);
app.register('phone-toggle', PhoneToggleController);
app.register('accordion', AccordionController);
// Le contrôleur de vérification des messages est maintenant intégré dans NotificationsController
