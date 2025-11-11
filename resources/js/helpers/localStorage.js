// utils/localStorage.js или где-то в shared/helpers

/**
 * Универсальная функция для работы с localStorage
 * @param {string} key - ключ в localStorage
 * @returns {Object} - объект с методами get и set
 */
const createLocalStorageSync = (key) => {
    return {
        get: (defaultValue = {}) => {
            try {
                const stored = localStorage.getItem(key);
                return stored ? JSON.parse(stored) : defaultValue;
            } catch (error) {
                console.error(`Error loading ${key} from localStorage:`, error);
                return defaultValue;
            }
        },
        
        set: (value) => {
            try {
                localStorage.setItem(key, JSON.stringify(value));
            } catch (error) {
                console.error(`Error saving ${key} to localStorage:`, error);
            }
        },
        
        remove: () => {
            try {
                localStorage.removeItem(key);
            } catch (error) {
                console.error(`Error removing ${key} from localStorage:`, error);
            }
        }
    };
};

export default createLocalStorageSync;