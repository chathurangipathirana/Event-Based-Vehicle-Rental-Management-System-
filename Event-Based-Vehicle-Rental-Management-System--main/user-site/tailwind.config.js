module.exports = {
  content: [
    './**/*.{php,html,js}',
    '!./node_modules/**/*'
  ],
  theme: {
    extend: {
      colors: {
        'surface-variant': '#e0e3e5',
        'on-tertiary-fixed-variant': '#38485d',
        'on-error-container': '#93000a',
        'surface-container-lowest': '#ffffff',
        'on-secondary-container': '#636467',
        'surface-container': '#eceef0',
        'on-surface-variant': '#5d3f3c',
        'outline': '#916f6a',
        'primary-fixed': '#ffdad5',
        'primary': '#b1000d',
        'on-tertiary': '#ffffff',
        'error-container': '#ffdad6',
        'on-surface': '#191c1e',
        'inverse-surface': '#2d3133',
        'tertiary': '#48586d',
        'background': '#f7f9fb',
        'surface-container-low': '#f2f4f6',
        'surface-container-highest': '#e0e3e5',
        'on-secondary-fixed': '#1a1c1e',
        'secondary-fixed': '#e2e2e5',
        'on-primary-fixed': '#410001',
        'primary-container': '#d91e1e',
        'surface-dim': '#d8dadc',
        'secondary-container': '#e2e2e5',
        'primary-fixed-dim': '#ffb4aa',
        'surface-bright': '#f7f9fb',
        'secondary': '#5d5e61',
        'inverse-on-surface': '#eff1f3',
        'tertiary-container': '#607087',
        'tertiary-fixed-dim': '#b7c8e1',
        'on-error': '#ffffff',
        'on-tertiary-container': '#eef3ff',
        'on-primary-container': '#fff0ed',
        'on-secondary': '#ffffff',
        'inverse-primary': '#ffb4aa',
        'surface-tint': '#c0000f',
        'on-primary-fixed-variant': '#930009',
        'secondary-fixed-dim': '#c6c6c9',
        'surface': '#f7f9fb',
        'tertiary-fixed': '#d3e4fe',
        'surface-container-high': '#e6e8ea',
        'error': '#ba1a1a',
        'on-tertiary-fixed': '#0b1c30',
        'outline-variant': '#e6bdb8',
        'on-background': '#191c1e',
        'on-secondary-fixed-variant': '#454749'
      },
      borderRadius: {
        DEFAULT: '0.125rem',
        lg: '0.25rem',
        xl: '0.5rem',
        full: '0.75rem'
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
