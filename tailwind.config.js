/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.antlers.html",
        "./resources/**/*.antlers.php",
        "./resources/**/*.blade.php",
        "./resources/**/*.vue",
        "./content/**/*.md",
    ],

    theme: {
        fontFamily: {
            montserrat: ["Montserrat", "sans-serif"],
            "roboto-flex": ["Roboto Flex", "sans-serif"],
            roboto: ["Roboto Flex", "sans-serif"],
        },
        extend: {
            maxWidth: {
                maxSite: "1440px",
            },
            marginBottom: {
                mb30: "30px",
            },
            colors: {
                darkGray: "#343537",
                gray80: "#F0F0F0",
                gray70: "#EEEEEE",
                gray90: "#D9D9D9",
                gray65: "#BDBEC1",
                mediumGray: "#888888",
                limeGreen: "#67B044",
            },
            fontSize: {
                sm: ["14px", "20px"],
                md: ["16px", "24px"],
                base: ["18px", "24px"],
                lg: ["20px", "28px"],
                xl: ["24px", "32px"],
            },
            fontFamily: {
                robotoLight: [
                    'Roboto Flex", "sans-serif',
                    {
                        fontVariationSettings: '"wght" 300',
                    },
                ],
                robotoRegular: [
                    'Roboto Flex", "sans-serif',
                    {
                        fontVariationSettings: '"wght" 400',
                    },
                ],
                robotoMedium: [
                    'Roboto Flex", "sans-serif',
                    {
                        fontVariationSettings: '"wght" 500',
                    },
                ],
                robotoBold: [
                    'Roboto Flex", "sans-serif',
                    {
                        fontVariationSettings: '"wght" 700',
                    },
                ],
                robotoCondensedLight: [
                    'Roboto Flex", "sans-serif',
                    {
                        fontVariationSettings: '"wght" 200, "wdth" 70',
                    },
                ],
                robotoCondensedMedium: [
                    'Roboto Flex", "sans-serif',
                    {
                        fontVariationSettings: '"wght" 400, "wdth" 75',
                    },
                ],
                robotoCondensedSemiMedium: [
                    'Roboto Flex", "sans-serif',
                    {
                        fontVariationSettings: '"wght" 500, "wdth" 75',
                    },
                ],
                robotoCondensedBold: [
                    'Roboto Flex", "sans-serif',
                    {
                        fontVariationSettings: '"wght" 700, "wdth" 75',
                    },
                ],
            },
        },
    },

    plugins: [require("@tailwindcss/typography")],
};
