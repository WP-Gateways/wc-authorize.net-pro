import '@testing-library/jest-dom';

// NOTE: do not wrap console.error here. @wordpress/jest-preset-default installs
// its own console spy (via @wordpress/jest-console) and replacing the method
// breaks it. That preset already fails any test which logs an unexpected error.
