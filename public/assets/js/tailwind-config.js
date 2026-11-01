// One Tailwind config for both the CDN (development) and the CLI build (tailwind.config.js requires this file).
// Semantic colours read CSS variables defined in input.css, which is how light/dark mode works.
(function () {
  const v = (name) => `rgb(var(--${name}) / <alpha-value>)`;
  const cfg = {
    darkMode: 'class',
    content: ['./app/Views/**/*.php', './public/assets/js/**/*.js'],
    theme: {
      extend: {
        colors: {
          // fixed brand colours
          ink:   { DEFAULT: '#11293B', 700: '#1B3A52', 600: '#2A4E6B', 100: '#E3EAF0' },
          brass: { DEFAULT: '#B98A2E', 600: '#9C7324', 100: '#F5ECD6' },
          sea:   { DEFAULT: '#1F6F78', 600: '#185A62', 100: '#DCEEF0' },
          // semantic, theme-aware colours
          canvas: v('canvas'), canvas2: v('canvas2'), surface: v('surface'), line: v('line'),
          fg: v('fg'), fg2: v('fg2'), muted: v('muted'),
          primary: v('primary'), 'primary-hover': v('primary-hover'), 'on-primary': v('on-primary'),
          side: v('side'), link: v('link'),
        },
        fontFamily: {
          display: ['"Bricolage Grotesque"', 'system-ui', 'sans-serif'],
          sans: ['"Public Sans"', 'system-ui', 'sans-serif'],
        },
      },
    },
    plugins: [],
  };
  if (typeof module !== 'undefined' && module.exports) module.exports = cfg;
  else if (typeof tailwind !== 'undefined') tailwind.config = cfg;
})();
