{{flutter_js}}
{{flutter_build_config}}

// CanvasKit can leak WebGL contexts across repeated hot restarts. Once the
// browser's context limit is reached, Flutter attempts to render a disposed
// view and subsequent restarts fail. Keep GPU rendering for deployed builds,
// but use the CPU backend while developing locally to make restarts reliable.
const isLocalDevelopment =
  window.location.hostname === 'localhost' ||
  window.location.hostname === '127.0.0.1';

_flutter.loader.load({
  config: isLocalDevelopment ? { canvasKitForceCpuOnly: true } : {},
});
