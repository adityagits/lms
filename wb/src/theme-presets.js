/* Theme presets: each one just overrides design tokens from theme/theme.css. */
(window.WB = window.WB || {}).presets = [
  { name: "Indigo (default)", vars: {} },
  {
    name: "Ocean",
    vars: {
      "--wb-primary": "#0284c7", "--wb-secondary": "#0c4a6e", "--wb-accent": "#14b8a6",
      "--wb-bg-alt": "#f0f9ff", "--wb-dark-bg": "#082f49",
      "--wb-font-body": "'Inter', system-ui, sans-serif", "--wb-font-heading": "'Poppins', system-ui, sans-serif",
      "--wb-radius": "10px",
    },
  },
  {
    name: "Sunset",
    vars: {
      "--wb-primary": "#e11d48", "--wb-secondary": "#7c2d12", "--wb-accent": "#f97316",
      "--wb-bg-alt": "#fff7ed", "--wb-dark-bg": "#3b0a1a", "--wb-text": "#3f2a2a",
      "--wb-font-body": "'Nunito', system-ui, sans-serif", "--wb-font-heading": "'DM Serif Display', Georgia, serif",
      "--wb-heading-weight": "400", "--wb-radius": "20px",
    },
  },
  {
    name: "Forest",
    vars: {
      "--wb-primary": "#15803d", "--wb-secondary": "#14532d", "--wb-accent": "#ca8a04",
      "--wb-bg-alt": "#f3f8f1", "--wb-dark-bg": "#0f2a1a", "--wb-text": "#1f2a22",
      "--wb-font-body": "'Lora', Georgia, serif", "--wb-font-heading": "'Montserrat', system-ui, sans-serif",
      "--wb-radius": "6px",
    },
  },
  {
    name: "Midnight",
    vars: {
      "--wb-primary": "#a78bfa", "--wb-primary-contrast": "#0b1020", "--wb-secondary": "#312e81", "--wb-accent": "#22d3ee",
      "--wb-bg": "#0b1020", "--wb-bg-alt": "#121a33", "--wb-text": "#e2e8f0", "--wb-muted": "#94a3b8",
      "--wb-border": "#243053", "--wb-dark-bg": "#060a16",
      "--wb-font-body": "'Space Grotesk', system-ui, sans-serif", "--wb-font-heading": "'Space Grotesk', system-ui, sans-serif",
    },
  },
  {
    name: "Mono",
    vars: {
      "--wb-primary": "#111111", "--wb-primary-contrast": "#ffffff", "--wb-secondary": "#111111", "--wb-accent": "#111111",
      "--wb-bg-alt": "#f4f4f4", "--wb-text": "#111111", "--wb-muted": "#666666", "--wb-border": "#dddddd",
      "--wb-dark-bg": "#111111", "--wb-radius": "0px",
      "--wb-font-body": "'Roboto', system-ui, sans-serif", "--wb-font-heading": "'Playfair Display', Georgia, serif",
    },
  },
];
