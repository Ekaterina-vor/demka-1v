/**
 * DemkaAuth - Система авторизации пользователей
 * Модуль валидации и обработки форм
 */

// Настройки валидации
const ValidationConfig = {
    // Минимальная длина пароля
    PASSWORD_MIN_LENGTH: 6,
    // Задержка для сообщений об ошибках и успехе (в мс)
    MESSAGE_TIMEOUT: 5000
};

/**
 * Утилиты для работы с формами
 */
class FormUtils {
    /**
     * Показывает сообщение об ошибке для поля ввода
     * @param {HTMLElement} inputElement - Элемент ввода
     * @param {string} message - Текст сообщения
     */
    static showError(inputElement, message) {
        const formGroup = inputElement.closest('.form-group');
        
        // Удаляем существующее сообщение об ошибке, если оно есть
        this.clearError(inputElement);
        
        // Создаем элемент сообщения об ошибке
        const errorDiv = document.createElement('div');
        errorDiv.className = 'form-error';
        errorDiv.textContent = message;
        
        // Добавляем класс ошибки и сообщение
        inputElement.classList.add('error');
        formGroup.appendChild(errorDiv);
    }
    
    /**
     * Очищает сообщение об ошибке
     * @param {HTMLElement} inputElement - Элемент ввода
     */
    static clearError(inputElement) {
        const formGroup = inputElement.closest('.form-group');
        const existingError = formGroup.querySelector('.form-error');
        
        if (existingError) {
            existingError.remove();
        }
        
        inputElement.classList.remove('error');
    }
    
    /**
     * Очищает все ошибки в форме
     * @param {HTMLFormElement} form - Элемент формы
     */
    static clearAllErrors(form) {
        const errorElements = form.querySelectorAll('.form-error');
        const errorInputs = form.querySelectorAll('.error');
        
        errorElements.forEach(error => error.remove());
        errorInputs.forEach(input => input.classList.remove('error'));
    }
    
    /**
     * Добавляет сообщение в контейнер
     * @param {HTMLElement} container - Контейнер для сообщения
     * @param {string} message - Текст сообщения
     * @param {string} type - Тип сообщения (success, error, info)
     * @param {boolean} autoHide - Автоматически скрывать сообщение
     */
    static showMessage(container, message, type = 'info', autoHide = true) {
        // Удаляем существующее сообщение, если оно есть
        const existingMessage = container.querySelector('.message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Создаем элемент сообщения
        const messageElement = document.createElement('div');
        messageElement.className = `message message-${type} fadeIn`;
        messageElement.innerHTML = `
            <span class="message-icon">${this.getIconForType(type)}</span>
            ${message}
            <span class="message-close">&times;</span>
        `;
        
        // Добавляем сообщение в контейнер
        container.insertBefore(messageElement, container.firstChild);
        
        // Добавляем обработчик для закрытия сообщения
        const closeButton = messageElement.querySelector('.message-close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                messageElement.remove();
            });
        }
        
        // Автоматически скрываем сообщение через указанное время
        if (autoHide) {
            setTimeout(() => {
                if (messageElement.parentNode) {
                    messageElement.classList.add('fadeOut');
                    setTimeout(() => {
                        if (messageElement.parentNode) {
                            messageElement.remove();
                        }
                    }, 500);
                }
            }, ValidationConfig.MESSAGE_TIMEOUT);
        }
    }
    
    /**
     * Возвращает иконку для типа сообщения
     * @param {string} type - Тип сообщения
     * @return {string} HTML-код иконки
     */
    static getIconForType(type) {
        switch (type) {
            case 'success':
                return '✓';
            case 'error':
                return '✕';
            case 'warning':
                return '⚠';
            case 'info':
            default:
                return 'ℹ';
        }
    }
}

/**
 * Валидатор форм
 */
class FormValidator {
    /**
     * Валидирует форму авторизации
     * @param {HTMLFormElement} form - Форма авторизации
     * @return {boolean} Результат валидации
     */
    static validateLoginForm(form) {
        const loginInput = form.querySelector('#login');
        const passwordInput = form.querySelector('#password');
        let isValid = true;
        
        // Очистка предыдущих ошибок
        FormUtils.clearAllErrors(form);
        
        // Проверка логина
        if (!loginInput.value.trim()) {
            FormUtils.showError(loginInput, 'Логин обязателен для заполнения');
            isValid = false;
        }
        
        // Проверка пароля
        if (!passwordInput.value.trim()) {
            FormUtils.showError(passwordInput, 'Пароль обязателен для заполнения');
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Валидирует форму смены пароля
     * @param {HTMLFormElement} form - Форма смены пароля
     * @return {boolean} Результат валидации
     */
    static validateChangePasswordForm(form) {
        const currentPasswordInput = form.querySelector('#current_password');
        const newPasswordInput = form.querySelector('#new_password');
        const confirmPasswordInput = form.querySelector('#confirm_password');
        let isValid = true;
        
        // Очистка предыдущих ошибок
        FormUtils.clearAllErrors(form);
        
        // Проверка текущего пароля
        if (!currentPasswordInput.value.trim()) {
            FormUtils.showError(currentPasswordInput, 'Текущий пароль обязателен для заполнения');
            isValid = false;
        }
        
        // Проверка нового пароля
        if (!newPasswordInput.value.trim()) {
            FormUtils.showError(newPasswordInput, 'Новый пароль обязателен для заполнения');
            isValid = false;
        } else if (newPasswordInput.value.length < ValidationConfig.PASSWORD_MIN_LENGTH) {
            FormUtils.showError(newPasswordInput, `Пароль должен содержать не менее ${ValidationConfig.PASSWORD_MIN_LENGTH} символов`);
            isValid = false;
        }
        
        // Проверка подтверждения пароля
        if (!confirmPasswordInput.value.trim()) {
            FormUtils.showError(confirmPasswordInput, 'Подтверждение пароля обязательно для заполнения');
            isValid = false;
        } else if (newPasswordInput.value !== confirmPasswordInput.value) {
            FormUtils.showError(confirmPasswordInput, 'Пароли не совпадают');
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Валидирует форму добавления пользователя
     * @param {HTMLFormElement} form - Форма добавления пользователя
     * @return {boolean} Результат валидации
     */
    static validateAddUserForm(form) {
        const loginInput = form.querySelector('#new_login');
        const passwordInput = form.querySelector('#new_password');
        const roleSelect = form.querySelector('#role');
        let isValid = true;
        
        // Очистка предыдущих ошибок
        FormUtils.clearAllErrors(form);
        
        // Проверка логина
        if (!loginInput.value.trim()) {
            FormUtils.showError(loginInput, 'Логин обязателен для заполнения');
            isValid = false;
        }
        
        // Проверка пароля
        if (!passwordInput.value.trim()) {
            FormUtils.showError(passwordInput, 'Пароль обязателен для заполнения');
            isValid = false;
        } else if (passwordInput.value.length < ValidationConfig.PASSWORD_MIN_LENGTH) {
            FormUtils.showError(passwordInput, `Пароль должен содержать не менее ${ValidationConfig.PASSWORD_MIN_LENGTH} символов`);
            isValid = false;
        }
        
        // Проверка роли
        if (!roleSelect.value) {
            FormUtils.showError(roleSelect, 'Роль обязательна для выбора');
            isValid = false;
        }
        
        return isValid;
    }
}

/**
 * Обработчик модальных окон
 */
class ModalHandler {
    /**
     * Открывает модальное окно
     * @param {string} modalId - ID модального окна
     */
    static openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            
            // Добавляем обработчик для закрытия модального окна при клике вне содержимого
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    this.closeModal(modalId);
                }
            });
            
            // Добавляем обработчик для закрытия модального окна при нажатии клавиши Esc
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    this.closeModal(modalId);
                }
            });
        }
    }
    
    /**
     * Закрывает модальное окно
     * @param {string} modalId - ID модального окна
     */
    static closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    }
}

/**
 * Инициализация форм и обработчиков событий при загрузке DOM
 */
document.addEventListener('DOMContentLoaded', function() {
    // Обработка формы авторизации
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (!FormValidator.validateLoginForm(this)) {
                e.preventDefault();
            }
        });
    }
    
    // Обработка формы смены пароля
    const changePasswordForm = document.getElementById('change-password-form');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function(e) {
            if (!FormValidator.validateChangePasswordForm(this)) {
                e.preventDefault();
            }
        });
    }
    
    // Обработка формы добавления пользователя
    const addUserForm = document.getElementById('add-user-form');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            if (!FormValidator.validateAddUserForm(this)) {
                e.preventDefault();
            }
        });
    }
    
    // Установка порядка табуляции для форм
    document.querySelectorAll('form').forEach(form => {
        const elements = Array.from(form.querySelectorAll('input, select, button'));
        elements.forEach((element, index) => {
            element.tabIndex = index + 1;
        });
    });
    
    // Преобразуем старые модальные окна в новые
    document.querySelectorAll('[id^="change-role-modal-"], [id^="reset-password-modal-"]').forEach(modal => {
        // Добавляем класс modal если его нет
        if (!modal.classList.contains('modal')) {
            modal.classList.add('modal');
        }
        
        // Находим кнопку закрытия и добавляем обработчик
        const closeButton = modal.querySelector('.close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }
    });
    
    // Получаем сообщения из URL (для обработки редиректов)
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const messageType = urlParams.get('type');
    
    if (message) {
        const container = document.querySelector('.container');
        if (container) {
            FormUtils.showMessage(container, decodeURIComponent(message), messageType || 'info', true);
        }
    }
}); 