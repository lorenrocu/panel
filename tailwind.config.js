module.exports = {
    theme: {
        extend: {
            colors: {
                primary: '#4a90e2', // Cambia este color por el que quieras usar como primario
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'), // Asegúrate de tener esto si estás usando Filament
    ],
};
