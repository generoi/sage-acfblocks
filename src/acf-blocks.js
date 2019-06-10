if (window.acfBlockStyles) {
  for (var block in window.acfBlockStyles) {
    if (window.acfBlockStyles.hasOwnProperty(block)) {
      var styles = window.acfBlockStyles[block];
      for (var i = 0; i < styles.length; i++) {
        window.wp.blocks.registerBlockStyle(block, styles[i]);
      }
    }
  }
}
