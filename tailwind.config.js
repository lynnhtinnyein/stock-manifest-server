/** @type {import('tailwindcss').Config} */
export default {
    content: ["./resources/**/*.blade.php"],
    theme: {
        extend: {
            fontFamily: {
                serif: ["AppSerif", "serif"]
            }
        }
    },
    plugins: []
};
