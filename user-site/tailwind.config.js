module.exports = {
  content: [
    './**/*.{php,html,js}',
    '!./node_modules/**/*'
  ],
  theme: {
    extend: {
      colors: {
        'surface-variant': '#e2e2e3',
        'on-tertiary-fixed-variant': '#633f1b',
        'on-error-container': '#93000a',
        'surface-container-lowest': '#ffffff',
        'on-secondary-container': '#636467',
        'surface-container': '#edeeee',
        'on-surface-variant': '#40484a',
        'outline': '#70787b',
        'primary-fixed': '#b8ebf7',
        'primary': '#02414a',
        'on-tertiary': '#ffffff',
        'error-container': '#ffdad6',
        'on-surface': '#191c1e',
        'inverse-surface': '#2d3133',
        'tertiary': '#54330f',
        'background': '#f9f9fa',
        'surface-container-low': '#f3f4f4',
        'surface-container-highest': '#e2e2e3',
        'on-secondary-fixed': '#1a1c1e',
        'secondary-fixed': '#e2e2e5',
        'on-primary-fixed': '#410001',
        'primary-container': '#245862',
        'surface-dim': '#d9dadb',
        'secondary-container': '#e2e2e5',
        'primary-fixed-dim': '#9ccfda',
        'surface-bright': '#f9f9fa',
        'secondary': '#5e5e5e',
        'inverse-on-surface': '#eff1f3',
        'tertiary-container': '#6f4924',
        'tertiary-fixed-dim': '#f1bc8e',
        'on-error': '#ffffff',
        'on-tertiary-container': '#efba8c',
        'on-primary-container': '#9acdd8',
        'on-secondary': '#ffffff',
        'inverse-primary': '#9ccfda',
        'surface-tint': '#336570',
        'on-primary-fixed-variant': '#174d57',
        'secondary-fixed-dim': '#c6c6c9',
        'surface': '#f9f9fa',
        'tertiary-fixed': '#ffdcc0',
        'surface-container-high': '#e7e8e9',
        'error': '#ba1a1a',
        'on-tertiary-fixed': '#2d1600',
        'outline-variant': '#c0c8ca',
        'on-background': '#191c1d',
        'on-secondary-fixed-variant': '#454749'
      },
      borderRadius: {
        DEFAULT: '0.25rem',
        lg: '0.5rem',
        xl: '0.75rem',
        full: '9999px'
      },
      spacing: {
        'section-gap': '64px',
        'container-max': '1440px',
        gutter: '24px',
        base: '8px',
        margin: '40px'
      },
      fontFamily: {
        'label-sm': ['Inter'],
        h1: ['Inter'],
        'label-md': ['Inter'],
        h3: ['Inter'],
        'body-lg': ['Inter'],
        h2: ['Inter'],
        'body-md': ['Inter']
      },
      fontSize: {
        'label-sm': ['12px', { lineHeight: '1.2', letterSpacing: '0.05em', fontWeight: '600' }],
        h1: ['40px', { lineHeight: '1.2', letterSpacing: '-0.02em', fontWeight: '700' }],
        'label-md': ['14px', { lineHeight: '1.2', letterSpacing: '0.02em', fontWeight: '500' }],
        h3: ['24px', { lineHeight: '1.3', letterSpacing: '0', fontWeight: '600' }],
        'body-lg': ['18px', { lineHeight: '1.6', letterSpacing: '0', fontWeight: '400' }],
        h2: ['32px', { lineHeight: '1.2', letterSpacing: '-0.01em', fontWeight: '600' }],
        'body-md': ['16px', { lineHeight: '1.5', letterSpacing: '0', fontWeight: '400' }]
      }
    }
  },
  plugins: []
};
