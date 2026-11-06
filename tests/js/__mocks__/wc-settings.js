/**
 * Faithful stand-in for @woocommerce/settings.
 *
 * The real getSetting() looks the key up in a module-level `allSettings`
 * object and returns `allSettings[key]` directly, so a configured key yields a
 * STABLE object reference across calls. Only a missing key returns the caller's
 * default, which is a fresh object literal each time. Reproducing that
 * distinction matters: a test that returned a new object every call would make
 * referential-stability bugs look unfixable, and one that always returned a
 * cached object would hide them.
 */
const allSettings = {};

export const setMockSetting = ( key, value ) => {
	allSettings[ key ] = value;
};

export const resetMockSettings = () => {
	Object.keys( allSettings ).forEach( ( key ) => delete allSettings[ key ] );
};

export const getSetting = ( key, fallback = false ) =>
	key in allSettings ? allSettings[ key ] : fallback;
