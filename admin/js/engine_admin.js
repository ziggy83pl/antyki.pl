/**
 * Admin Panel JavaScript — Refactored for Bootstrap 5 & ES6 Modules
 * Compatible with: Bootstrap 5.3, Vanilla JS
 */

import { initAuth } from './modules/auth.js?v=1.5';
import { initForms } from './modules/forms.js';
import { initTables } from './modules/tables.js';
import { initAjaxHandlers } from './modules/ajax.js';
import { initUI } from './modules/ui.js';
import { initEditor } from './modules/editor.js';
import { initSlider } from './modules/slider.js';

document.addEventListener('DOMContentLoaded', () => {
    // 1. Inicjalizacja interfejsu (w tym dark mode jako pierwsze)
    initUI();
    
    // 2. Inicjalizacja formularzy
    initForms();
    
    // 3. Inicjalizacja autoryzacji (walidacje haseł, timer)
    initAuth();
    
    // 4. Inicjalizacja tabel (sortowanie, wyszukiwanie z debounce, masowe akcje)
    initTables();
    
    // 5. Inicjalizacja AJAX
    initAjaxHandlers();
    
    // 6. Inicjalizacja edytorów i menedżera plików
    initEditor();
    
    // 7. Inicjalizacja sliderów
    initSlider();
});