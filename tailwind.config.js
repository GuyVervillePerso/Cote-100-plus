/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.antlers.html',
        './resources/**/*.antlers.php',
        './resources/**/*.blade.php',
        './resources/**/*.vue',
        './content/**/*.md',
    ],

    theme: {
        extend: {
            maxWidth: {
                maxSite: '1440px',
            },
            colors: {
                'darkGray': '#343537',
            }
        },
    },


    plugins: [
        require('@tailwindcss/typography'),
    ],
};
