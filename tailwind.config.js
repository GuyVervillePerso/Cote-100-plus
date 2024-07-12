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
        fontSize: {
            sm: ['14px', '20px'],
            md: ['16px', '24px'],
            base: ['18px', '24px'],
            lg: ['20px', '28px'],
            xl: ['24px', '32px'],
        },
        extend: {
            maxWidth: {
                maxSite: '1440px',
            },
            colors: {
                'darkGray': '#343537',
                'gray65': '#BDBEC1',
                'gray80': '#F0F0F0',
            },

            fontFamily: {
                'roboto-flex': ['Roboto Flex', 'sans-serif'],
                'montserrat': ['Montserrat', 'sans-serif'],
            },

        },
    },


    plugins: [
        require('@tailwindcss/typography'),
    ],
};
